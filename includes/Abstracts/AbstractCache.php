<?php
	/**
	 * Abstract Cache Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      3.5.0
	 * @version    3.5.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use Psr\SimpleCache\CacheInterface;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractCache' ) ) {

	/**
	 * Base class for object cache and versioned transient storage.
	 *
	 * Transparently switches between an external object cache (e.g. Redis/Memcached)
	 * and WordPress transients so callers never need to know which backend is active.
	 * Cache invalidation is version-based: bumping the version key instantly
	 * orphans all entries without touching individual records.
	 *
	 * @name AbstractCache
	 *
	 * @since 3.5.0
	 */
	abstract class AbstractCache {

		use HelperMethodsTrait;
		use InternalPackageTrait;

		// =====================================================================
		// Properties
		// =====================================================================

		/**
		 * Transient option name prefix used when purging by group.
		 *
		 * @since 3.5.0
		 * @var   string
		 */
		private string $transient_option_key = '_transient_';
		/**
		 * Transient timeout option name prefix used when purging by group.
		 *
		 * @since 3.5.0
		 * @var   string
		 */
		private string $transient_timeout_key = '_transient_timeout_';

		// =====================================================================
		// Service Lifecycle Methods
		// =====================================================================

		/**
		 * Registers the cache group as a global group when using an external object cache.
		 *
		 * @since 3.5.0
		 * @see   self::is_global_cache_group()
		 * @see   self::is_object_cache()
		 */
		public function __construct() {
			if ( function_exists( 'wp_cache_add_global_groups' ) && $this->is_global_cache_group() && $this->is_object_cache() ) {
				wp_cache_add_global_groups( array( $this->get_cache_group() ) );
			}
		}

		// =====================================================================
		// Cache Configuration Methods
		// =====================================================================

		/**
		 * Returns the cache group name (defaults to the plugin directory name).
		 *
		 * @since  3.5.0
		 * @return string
		 */
		public function get_cache_group(): string {
			return $this->get_plugin_dirname();
		}

		/**
		 * Whether to register this group as a global cache group across sites.
		 *
		 * @since  3.5.0
		 * @return bool
		 */
		public function is_global_cache_group(): bool {
			return false;
		}

		/**
		 * Returns the TTL in seconds for the group version key (0 = no expiry).
		 *
		 * @since  3.5.0
		 * @return int
		 */
		public function get_group_expiration(): int {
			return 0; // You can use YEAR_IN_SECONDS, DAY_IN_SECONDS.
		}

		/**
		 * Whether an external persistent object cache is active.
		 *
		 * @since  3.5.0
		 * @return bool
		 */
		public function is_object_cache(): bool {
			return (bool) wp_using_ext_object_cache();
		}

		// =====================================================================
		// Key & Version Management Methods
		// =====================================================================

		/**
		 * Builds a versioned, sanitized, length-capped cache key.
		 *
		 * @since  3.5.0
		 * @param  string $key Raw cache key.
		 * @return string      Versioned key, max 150 characters.
		 * @see    self::get_version()
		 */
		public function get_key( string $key ): string {

			$version = sanitize_key( $this->get_version() );

			$generated_key = sprintf( '%s:v%s_%s', $this->get_cache_group(), $version, sanitize_key( $key ) );

			if ( strlen( $generated_key ) > 150 ) {
				$generated_key = md5( $generated_key );
			}

			return $generated_key;
		}

		/**
		 * Returns the option/transient key used to store the current cache version.
		 *
		 * @since  3.5.0
		 * @return string
		 * @see    self::get_version()
		 * @see    self::set_version()
		 */
		public function get_version_key(): string {
			return $this->get_cache_group() . '-cache-version';
		}

		/**
		 * Returns the current cache version, creating one if none exists.
		 *
		 * @since  3.5.0
		 * @return string Timestamp-based version string.
		 * @see    self::set_version()
		 */
		public function get_version(): string {

			// Object cache.
			if ( $this->is_object_cache() ) {
				$version = wp_cache_get( $this->get_version_key(), $this->get_cache_group() );

				if ( ! is_string( $version ) ) {
					$version = $this->set_version();
				}

				return $version;
			}

			// Fallback to transient cache.
			$version = get_transient( $this->get_version_key() );

			if ( ! is_string( $version ) ) {
				$version = $this->set_version();
			}

			return $version;
		}

		/**
		 * Stores a new timestamp-based version string and returns it.
		 *
		 * @since  3.5.0
		 * @return string The version string that was stored.
		 * @see    self::get_version()
		 */
		public function set_version(): string {

			$version = (string) time();

			if ( $this->is_object_cache() ) {
				wp_cache_set( $this->get_version_key(), $version, $this->get_cache_group(), $this->get_group_expiration() );
				return $version;
			}

			set_transient( $this->get_version_key(), $version, $this->get_group_expiration() );

			return $version;
		}

		/**
		 * Deletes the version key, effectively invalidating all versioned cache entries.
		 *
		 * @since  3.5.0
		 * @return bool True on success, false on failure.
		 * @see    self::flush()
		 */
		public function delete_version(): bool {

			if ( $this->is_object_cache() ) {
				return wp_cache_delete( $this->get_version_key(), $this->get_cache_group() );
			}

			return delete_transient( $this->get_version_key() );
		}

		// =====================================================================
		// Cache Read Methods
		// =====================================================================

		/**
		 * Retrieves a cached value by key.
		 *
		 * @since  3.5.0
		 * @param string $key Cache key.
		 * @param mixed  $default_value Default value to return if the key does not exist.
		 * @return mixed The value of the item from the cache, or $default_value in case of cache miss.
		 * @phpstan-return mixed
		 * @see    self::get_transient()
		 */
		public function get( string $key, $default_value = null ) {

			if ( $this->is_object_cache() ) {

				$found = false;
				$value = wp_cache_get( $this->get_key( $key ), $this->get_cache_group(), false, $found );

				if ( $found ) {
					return $value;
				}

				return $default_value;
			}

			return $this->get_transient( $key ) ?? $default_value;
		}

		/**
		 * Retrieves a transient value, normalizing empty/missing results to false.
		 *
		 * @since  3.5.0
		 * @param  string $key Cache key.
		 * @return mixed       Cached value, or false on a miss or empty value.
		 */
		public function get_transient( string $key ) {

			$data = get_transient( $this->get_key( $key ) );

			if ( false === $data ) {
				return null;
			}

			return $data;
		}

		/**
		 * Returns true when a cache response contains a real value.
		 *
		 * @since  3.5.0
		 * @param  string $key Value returned from a cache read.
		 * @return bool
		 * @see    self::is_empty()
		 */
		public function has( string $key ): bool {
			return ! is_null( $this->get( $key ) );
		}

		/**
		 * Returns true when a cache response is null or false (i.e. a miss).
		 *
		 * Note: storing a literal `false` value is not supported — it is
		 * indistinguishable from a cache miss. Wrap falsy values in an array
		 * or object before passing to set()/add().
		 *
		 * @param string $key Value returned from a cache read.
		 * @return bool
		 * @since  3.5.0
		 * @see    self::has()
		 */
		public function is_empty( string $key ): bool {

			return is_null( $this->get( $key ) );
		}

		// =====================================================================
		// Cache Write Methods
		// =====================================================================

		/**
		 * Adds a value only when the key does not already exist in cache.
		 *
		 * @since  3.5.0
		 * @param  string   $key        Cache key.
		 * @param  mixed    $value      Value to cache.
		 * @param  int|null $ttl TTL in seconds.
		 * @return bool               True if stored, false if key already existed.
		 * @see    self::add_transient()
		 */
		public function add( string $key, $value, $ttl = null ): bool {

			$expire = $ttl ?? YEAR_IN_SECONDS;

			if ( $this->is_object_cache() ) {
				return wp_cache_add( $this->get_key( $key ), $value, $this->get_cache_group(), $expire ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
			}

			return $this->add_transient( $key, $value, $expire );
		}

		/**
		 * Stores a value in cache, overwriting any existing entry.
		 *
		 * @since  3.5.0
		 * @param  string   $key        Cache key.
		 * @param  mixed    $value      Value to cache.
		 * @param  int|null $ttl TTL in seconds.
		 * @return bool
		 * @see    self::set_transient()
		 */
		public function set( string $key, $value, $ttl = null ): bool {

			$expire = $ttl ?? YEAR_IN_SECONDS;

			if ( $this->is_object_cache() ) {
				return wp_cache_set( $this->get_key( $key ), $value, $this->get_cache_group(), $expire ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
			}

			return $this->set_transient( $key, $value, $expire );
		}

		/**
		 * Adds a transient only when the versioned key is not already cached.
		 *
		 * @since  3.5.0
		 * @param  string $key        Cache key.
		 * @param  mixed  $value      Value to cache.
		 * @param  int    $expiration TTL in seconds.
		 * @return bool               True if stored, false if key already existed.
		 * @see    self::set_transient()
		 */
		public function add_transient( string $key, $value, int $expiration = YEAR_IN_SECONDS ): bool {

			$data = $this->get_transient( $key );

			if ( $this->is_empty( $data ) ) {
				return $this->set_transient( $key, $value, $expiration );
			}

			return false;
		}

		/**
		 * Stores a value as a WordPress transient under the versioned key.
		 *
		 * @since  3.5.0
		 * @param  string $key        Cache key.
		 * @param  mixed  $value      Value to cache.
		 * @param  int    $expiration TTL in seconds.
		 * @return bool
		 */
		public function set_transient( string $key, $value, int $expiration = YEAR_IN_SECONDS ): bool {
			return set_transient( $this->get_key( $key ), $value, $expiration );
		}

		// =====================================================================
		// Cache Delete Methods
		// =====================================================================

		/**
		 * Deletes a single cache entry by key.
		 *
		 * @since  3.5.0
		 * @param  string $key Cache key.
		 * @return bool
		 * @see    self::delete_transient()
		 */
		public function delete( string $key ): bool {

			if ( $this->is_object_cache() ) {
				return wp_cache_delete( $this->get_key( $key ), $this->get_cache_group() );
			}

			return $this->delete_transient( $key );
		}

		/**
		 * Deletes a single transient by its versioned key.
		 *
		 * @since  3.5.0
		 * @param  string $key Cache key.
		 * @return bool
		 */
		public function delete_transient( string $key ): bool {
			return delete_transient( $this->get_key( $key ) );
		}

		/**
		 * Flushes all entries in this cache group.
		 *
		 * Uses group flush when the object cache supports it; otherwise bumps
		 * the version key to orphan all existing versioned entries.
		 *
		 * @since  3.5.0
		 * @return bool
		 * @see    self::delete_version()
		 */
		public function clear(): bool {

			// Flash By Group.
			if ( $this->is_object_cache() && wp_cache_supports( 'flush_group' ) ) {
				return wp_cache_flush_group( $this->get_cache_group() );
			}

			return $this->delete_version();
		}

		/**
		 * Removes all transient rows for this cache group directly from the database.
		 *
		 * @since  3.5.0
		 * @return bool True when at least one row was deleted.
		 * @see    self::flush_all()
		 */
		private function delete_all_transient(): bool {

			global $wpdb;

			$group_like = $wpdb->esc_like( $this->get_cache_group() ) . '%';

			$option_key  = $wpdb->esc_like( $this->transient_option_key ) . $group_like;
			$timeout_key = $wpdb->esc_like( $this->transient_timeout_key ) . $group_like;

			$id = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$option_key,
					$timeout_key
				)
			);

			return absint( $id ) > 0;
		}

		/**
		 * Flushes the entire cache backend (all groups).
		 *
		 * @since  3.5.0
		 * @return bool
		 * @see    self::delete_all_transient()
		 */
		public function flush(): bool {

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( $this->is_object_cache() ) {
				return wp_cache_flush();
			}

			return $this->delete_all_transient();
		}

		// =====================================================================
		// Key Generation Utilities
		// =====================================================================

		/**
		 * Generate cache key by array or object.
		 *
		 * @since  3.5.2
		 * @param  array<array-key, mixed>|object $data Cache data.
		 * @return string                               MD5 hash of the normalised, sorted data.
		 * @see    self::sort_data()
		 */
		public function create_key( $data ): string {

			$this->sort_data( $data );

			return md5( wp_json_encode( $data ) );
		}

		/**
		 * Recursively sorts arrays and objects so equivalent structures always produce the same JSON.
		 *
		 * @since  3.5.0
		 * @param  array<array-key, mixed>|object $data  Data to sort, passed by reference.
		 * @param  int                            $depth Current recursion depth; aborts at 32 to prevent stack overflow.
		 * @return void
		 * @see    self::create_key()
		 */
		public function sort_data( &$data, int $depth = 0 ): void {

			if ( $depth > 32 ) {
				return;
			}

			if ( is_array( $data ) ) {
				if ( array_is_list( $data ) ) {
					sort( $data );
				} else {
					ksort( $data );
				}
				foreach ( $data as &$value ) {
					$this->sort_data( $value, $depth + 1 );
				}
			} elseif ( is_object( $data ) ) {
				// Sort object properties by name.
				$vars = get_object_vars( $data );
				ksort( $vars );
				foreach ( $vars as $key => $value ) {
					unset( $data->$key );
				}
				foreach ( $vars as $key => $value ) {
					$this->sort_data( $value, $depth + 1 );
					$data->$key = $value;
				}
			}
		}
	}
}
