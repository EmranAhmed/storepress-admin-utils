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

	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractCache' ) ) {

	/**
	 * Base class for object cache and versioned transient storage.
	 *
	 * @name AbstractCache
	 *
	 * @since 3.5.0
	 */
	abstract class AbstractCache {

		use HelperMethodsTrait;
		use InternalPackageTrait;

		/**
		 * Constructor. Registers the cache group as global when an external object cache is in use.
		 *
		 * @since 3.5.0
		 */
		public function __construct() {
			if ( function_exists( 'wp_cache_add_global_groups' ) && $this->is_global_cache_group() && wp_using_ext_object_cache() ) {
				wp_cache_add_global_groups( array( $this->get_cache_group() ) );
			}
		}

		/**
		 * Get cache group name.
		 *
		 * @return string
		 * @since 3.5.0
		 */
		public function get_cache_group(): string {
			return $this->get_plugin_dirname();
		}

		/**
		 * Whether cache entries are shared network-wide. Default false (per-site isolation).
		 *
		 * @since 3.5.0
		 */
		protected function is_global_cache_group(): bool {
			return false;
		}

		/**
		 * Cache Prefix key.
		 *
		 * @param string $key Cache Prefix Key.
		 *
		 * @return string
		 */
		private function prefixed_key( string $key ): string {
			return $this->get_cache_group() . '_' . $key;
		}

		// =====================================================================
		// Transient Cache Methods
		// =====================================================================

		/**
		 * Store a value in the WP object cache.
		 *
		 * @param string                         $key        Cache key.
		 * @param string|array<array-key, mixed> $data      Value to store.
		 * @param int                            $expiration Optional. Expiration in seconds. Default YEAR_IN_SECONDS.
		 *
		 * @return bool
		 * @since 3.5.0
		 *
		 * @see Cache::get()
		 */
		public function set( string $key, $data, int $expiration = YEAR_IN_SECONDS ): bool {

			if ( wp_using_ext_object_cache() ) {
				return wp_cache_set( $key, $data, $this->get_cache_group(), $expiration ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
			}

			return $this->set_transient( $key, $data, $expiration );
		}

		/**
		 * Store a value as a versioned transient.
		 *
		 * @param string                         $key        Cache key.
		 * @param string|array<array-key, mixed> $value      Value to store.
		 * @param int                            $expiration Optional. Expiration in seconds. Default YEAR_IN_SECONDS. 0 = no expiry.
		 *
		 * @return bool
		 * @since 3.5.0
		 *
		 * @see Cache::get_transient()
		 * @see Cache::get_version()
		 */
		public function set_transient( string $key, $value, int $expiration = YEAR_IN_SECONDS ): bool {
			$transient_version = $this->get_version();
			$transient_value   = array(
				'version' => $transient_version,
				'value'   => $value,
			);

			return set_transient( $this->prefixed_key( $key ), $transient_value, $expiration );
		}

		/**
		 * Return the current version string for the plugin's transient group.
		 *
		 * @return string
		 * @since 1.0.0
		 *
		 * @see Cache::get_transient_version()
		 */
		public function get_version(): string {
			return $this->get_transient_version( $this->get_cache_group() );
		}

		/**
		 * Return (or generate) a version token used to mass-invalidate a group of transients.
		 *
		 * When using transients with unpredictable names, e.g. those containing an md5
		 * hash in the name, we need a way to invalidate them all at once.
		 *
		 * When using default WP transients we're able to do this with a DB query to
		 * delete transients manually.
		 *
		 * With external cache however, this isn't possible. Instead, this function is used
		 * to append a unique string (based on time()) to each transient. When transients
		 * are invalidated, the transient version will increment and data will be regenerated.
		 *
		 * Raised in issue https://github.com/woocommerce/woocommerce/issues/5777.
		 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/.
		 *
		 * @param string $group   Name for the group of transients we need to invalidate.
		 * @param bool   $refresh true to force a new version.
		 *
		 * @return string transient version based on time(), 10 digits.
		 * @since 3.5.0
		 *
		 * @see Cache::get_version()
		 * @see Cache::clear_all_transient()
		 */
		public function get_transient_version( string $group, bool $refresh = false ): string {
			$transient_name  = $group . '_transient_version';
			$transient_value = get_transient( $transient_name );

			if ( ! is_string( $transient_value ) ) {
				$transient_value = false;
			}

			if ( false === $transient_value || true === $refresh ) {
				$transient_value = (string) time();

				set_transient( $transient_name, $transient_value );
			}

			return $transient_value;
		}

		/**
		 * Retrieve a value from the WP object cache.
		 *
		 * @param string $key        Cache key.
		 *
		 * @return string|array<array-key, mixed>|object|bool Cached value, or false on miss / unavailable object cache.
		 * @since 1.0.0
		 *
		 * @see Cache::set()
		 */
		public function get( string $key ) {

			if ( wp_using_ext_object_cache() ) {
				return wp_cache_get( $key, $this->get_cache_group() );
			}

			return $this->get_transient( $key );
		}

		/**
		 * Retrieve a versioned transient; returns false when stale or missing.
		 *
		 * @param string $key Cache key.
		 *
		 * @return string|array<array-key, mixed>|object|bool Cached value or null on miss/version mismatch.
		 * @since 3.5.0
		 *
		 * @see Cache::set_transient()
		 * @see Cache::get_version()
		 */
		public function get_transient( string $key ) {
			$transient_version = $this->get_version();
			$transient_value   = get_transient( $key );

			if ( isset( $transient_value['value'], $transient_value['version'] ) && $transient_value['version'] === $transient_version ) {
				return $transient_value['value'];
			}

			return false;
		}

		/**
		 * Has cache data or not.
		 *
		 * @param string|array<array-key, mixed>|object|bool $cache_response Cache response for verify.
		 *
		 * @return bool
		 */
		public function has( $cache_response ): bool {

			return ! $this->is_empty( $cache_response );
		}

		/**
		 * Is cache empty
		 *
		 * @param string|array<array-key, mixed>|object|bool $cache_response Cache response for verify.
		 *
		 * @return bool
		 */
		public function is_empty( $cache_response ): bool {
			return false === $cache_response;
		}

		/**
		 * Delete Cache.
		 *
		 * @param string $key Cache key.
		 *
		 * @return bool
		 */
		public function delete( string $key ): bool {

			if ( wp_using_ext_object_cache() ) {
				return wp_cache_delete( $key, $this->get_cache_group() );
			}

			return $this->delete_transient( $key );
		}

		// =====================================================================
		// Object Cache Methods
		// =====================================================================

		/**
		 * Delete a transient by key.
		 *
		 * @param string $key Cache key.
		 *
		 * @return bool
		 * @since 3.5.0
		 */
		public function delete_transient( string $key ): bool {
			return delete_transient( $key );
		}

		/**
		 * Flush all entries in a WP object cache group.
		 *
		 * @return bool False when the object cache backend does not support group flushing.
		 * @since 3.5.0
		 *
		 * @see Cache::clear_all_transient()
		 */
		public function flush(): bool {

			if ( wp_using_ext_object_cache() && wp_cache_supports( 'flush_group' ) ) {
				return wp_cache_flush_group( $this->get_cache_group() );
			}

			return $this->clear_all_transient();
		}

		/**
		 * Invalidate all versioned transients by bumping the group version.
		 *
		 * @return bool
		 * @since 1.0.0
		 *
		 * @see Cache::get_transient_version()
		 */
		public function clear_all_transient(): bool {
			$this->get_transient_version( $this->get_cache_group(), true );
			return true;
		}

		/**
		 * Flash All Cache
		 *
		 * @return bool
		 */
		public function flush_all(): bool {

			if ( wp_using_ext_object_cache() ) {
				return wp_cache_flush();
			}

			return $this->clear_all_transient();
		}

		/**
		 * Generate cache key by array or object.
		 *
		 * @param array<array-key, mixed>|object $data Cache data.
		 *
		 * @return string
		 */
		public function create_cache_key( $data ): string {

			$this->sort_data( $data );

			return md5( wp_json_encode( $data ) );
		}

		/**
		 * Sort Array or Object
		 *
		 * @param array<array-key, mixed>|object $data Cache data.
		 *
		 * @return void
		 */
		public function sort_data( &$data ): void {
			if ( is_array( $data ) ) {
				if ( array_is_list( $data ) ) {
					sort( $data );
				} else {
					ksort( $data );
				}
				foreach ( $data as &$value ) {
					$this->sort_data( $value );
				}
			} elseif ( is_object( $data ) ) {
				// Sort object properties by name.
				$vars = get_object_vars( $data );
				ksort( $vars );
				foreach ( $vars as $key => $value ) {
					unset( $data->$key );
				}
				foreach ( $vars as $key => $value ) {
					$this->sort_data( $value );
					$data->$key = $value;
				}
			}
		}
	}
}
