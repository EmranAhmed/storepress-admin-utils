<?php
	/**
	 * Plugin Common Trait File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Traits;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! trait_exists( '\StorePress\AdminUtils\Traits\PluginCommonTrait' ) ) {

	/**
	 * Plugin Common Trait.
	 *
	 * Provides shared plugin utility methods for resolving file paths, URLs,
	 * slugs, basenames, versions, and asset locations relative to a plugin's main file.
	 *
	 * @name PluginCommonTrait
	 *
	 * @since 1.0.0
	 */
	trait PluginCommonTrait {

		/**
		 * Cached plugin version string.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected string $plugin_version = '';

		/**
		 * Cached plugin name string.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected string $plugin_name = '';

		/**
		 * Get plugin main file path (absolute or relative).
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		abstract public function plugin_file(): string;

		/**
		 * Get the pro plugin file path. Override to provide a pro plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @return string Pro plugin file path, or empty string if no pro version.
		 */
		public function pro_plugin_file(): string {
			return '';
		}

		/**
		 * Get the absolute pro plugin file path.
		 *
		 * @since 1.0.0
		 *
		 * @return string Absolute file path, or empty string if no pro version.
		 *
		 * @see PluginCommonTrait::pro_plugin_file()
		 */
		public function get_pro_plugin_file(): string {
			$file = $this->pro_plugin_file();

			if ( '' === $file ) {
				return '';
			}

			return $this->get_plugin_absolute_file( $this->pro_plugin_file() );
		}

		/**
		 * Get absolute file paths for all plugin files (free + pro).
		 *
		 * @since 1.0.0
		 *
		 * @return string[] Array of absolute plugin file paths.
		 *
		 * @see PluginCommonTrait::get_plugin_file()
		 * @see PluginCommonTrait::get_pro_plugin_file()
		 */
		public function get_plugin_files(): array {
			$plugin_files   = array();
			$plugin_files[] = $this->get_plugin_file();
			$plugin_files[] = $this->get_pro_plugin_file();

			return array_map( array( $this, 'get_plugin_file' ), array_filter( $plugin_files ) );
		}

		/**
		 * Get basename for all plugins files (free + pro).
		 *
		 * @since 1.0.0
		 *
		 * @return string[] Array of plugin basenames (e.g. 'my-plugin/my-plugin.php').
		 *
		 * @see PluginCommonTrait::get_plugin_files()
		 */
		public function get_plugins_basename(): array {
			return array_map( array( $this, 'get_plugin_basename' ), $this->get_plugin_files() );
		}

		/**
		 * Get absolute plugin file path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string
		 */
		public function get_plugin_absolute_file( string $plugin_file = '' ): string {
			$file   = '' === $plugin_file ? wp_normalize_path( $this->plugin_file() ) : wp_normalize_path( $plugin_file );
			$plugin = plugin_basename( $file );

			return trailingslashit( WP_PLUGIN_DIR ) . $plugin;
		}

		/**
		 * Get validated absolute plugin file path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string
		 */
		public function get_plugin_file( string $plugin_file = '' ): string {
			return $this->get_plugin_absolute_file( $plugin_file );
		}

		/**
		 * Is Valid plugin.
		 *
		 * @param string $plugin_file Plugin file.
		 *
		 * @return bool
		 */
		public function is_valid_plugin( string $plugin_file = '' ): bool {
			if ( ! function_exists( 'validate_plugin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$response = validate_plugin( $this->get_plugin_basename( $plugin_file ) );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			return 0 === $response;
		}

		/**
		 * Get plugin directory path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Untrailed directory path (e.g. '/var/www/wp-content/plugins/xyz-plugin').
		 */
		public function get_plugin_dir_path( string $plugin_file = '' ): string {
			return untrailingslashit( plugin_dir_path( $this->get_plugin_file( $plugin_file ) ) );
		}

		/**
		 * Get plugin slug (directory name).
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Plugin slug (e.g. 'xyz-plugin').
		 */
		public function get_plugin_slug( string $plugin_file = '' ): string {
			return wp_basename( dirname( $this->get_plugin_file( $plugin_file ) ) );
		}

		/**
		 * Get plugin basename.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Plugin basename (e.g. 'xyz-plugin/xyz-plugin.php').
		 */
		public function get_plugin_basename( string $plugin_file = '' ): string {
			return plugin_basename( $this->get_plugin_file( $plugin_file ) );
		}

		/**
		 * Get Plugin Directory name.
		 *
		 * @param string $plugin_file Plugin file.
		 *
		 * @return string
		 */
		public function get_plugin_dirname( string $plugin_file = '' ): string {
			return dirname( $this->get_plugin_basename( $plugin_file ) );
		}

		/**
		 * Get plugin directory URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Untrailed plugin directory URL.
		 */
		public function get_plugin_dir_url( string $plugin_file = '' ): string {
			return untrailingslashit( plugin_dir_url( $this->get_plugin_file( $plugin_file ) ) );
		}

		/**
		 * Get plugin version from file header, with caching.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Plugin version string.
		 */
		public function get_plugin_version( string $plugin_file = '' ): string {

			$headers = array( 'version' => 'Version' );
			$file    = $this->get_plugin_file( $plugin_file );

			if ( ! $this->is_valid_plugin( $file ) ) {
				return '';
			}

			if ( '' === trim( $this->plugin_version ) && '' === trim( $plugin_file ) ) {
				$versions             = get_file_data( $file, $headers );
				$this->plugin_version = $versions['version'] ?? '';
			}

			if ( ! $this->is_empty_string( $plugin_file ) ) {
				$versions = get_file_data( $file, $headers );
				return $versions['version'] ?? '';
			}

			return $this->plugin_version;
		}

		/**
		 * Get plugin name from file header, with caching.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Plugin name string.
		 */
		public function get_plugin_name( string $plugin_file = '' ): string {

			$headers = array( 'name' => 'Plugin Name' );
			$file    = $this->get_plugin_file( $plugin_file );

			if ( ! $this->is_valid_plugin( $file ) ) {
				return '';
			}

			if ( '' === trim( $this->plugin_name ) && '' === trim( $plugin_file ) ) {
				$names             = get_file_data( $file, $headers );
				$this->plugin_name = $names['name'] ?? '';
			}

			if ( ! $this->is_empty_string( $plugin_file ) ) {
				$names = get_file_data( $file, $headers );
				return $names['name'] ?? '';
			}

			return $this->plugin_name;
		}

		/**
		 * Get plugin images directory URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Images directory URL.
		 */
		public function images_url( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_url( $plugin_file ) . '/images';
		}

		/**
		 * Get plugin assets directory URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Assets directory URL.
		 */
		public function assets_url( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_url( $plugin_file ) . '/assets';
		}

		/**
		 * Get plugin assets directory path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Assets directory path.
		 */
		public function assets_path( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_path( $plugin_file ) . '/assets';
		}

		/**
		 * Get asset file modification time (for cache-busting).
		 *
		 * @since 1.0.0
		 *
		 * @param string $file        Asset file name relative to the assets directory.
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return int|false File modification time as Unix timestamp, or false on failure.
		 */
		public function assets_version( string $file, string $plugin_file = '' ) {
			return filemtime( $this->assets_path( $plugin_file ) . $file );
		}

		/**
		 * Get plugin build directory URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Build directory URL.
		 */
		public function build_url( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_url( $plugin_file ) . '/build';
		}

		/**
		 * Get plugin build directory path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Build directory path.
		 */
		public function build_path( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_path( $plugin_file ) . '/build';
		}

		/**
		 * Get plugin includes directory path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Includes directory path.
		 */
		public function includes_path( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_path( $plugin_file ) . '/includes';
		}

		/**
		 * Get plugin templates directory path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Templates directory path.
		 */
		public function templates_path( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_path( $plugin_file ) . '/templates';
		}

		/**
		 * Get plugin languages directory path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Languages directory path.
		 */
		public function languages_path( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_path( $plugin_file ) . '/languages';
		}

		/**
		 * Get plugin vendor directory path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Vendor directory path.
		 */
		public function vendor_path( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_path( $plugin_file ) . '/vendor';
		}

		/**
		 * Get plugin vendor directory URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Vendor directory URL.
		 */
		public function vendor_url( string $plugin_file = '' ): string {
			return $this->get_plugin_dir_url( $plugin_file ) . '/vendor';
		}

		/**
		 * Register StorePress Utils script from plugin build directory.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Optional. Relative or absolute plugin file path. Default empty (current plugin).
		 *
		 * @return string Registered script handle, or empty string if build file not found.
		 */
		public function register_storepress_utils_script( string $plugin_file = '' ): string {

			if ( ! file_exists( $this->build_path( $plugin_file ) . '/storepress-utils.js' ) ) {
				return '';
			}

			$file_url   = $this->build_url( $plugin_file ) . '/storepress-utils.js';
			$asset_path = $this->build_path( $plugin_file ) . '/storepress-utils.asset.php';
			$asset      = include $asset_path;

			wp_register_script( 'storepress-utils', $file_url, $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
			return 'storepress-utils';
		}

		/**
		 * Enable Debug Log.
		 *
		 * @return bool
		 */
		public function is_log_enabled(): bool {
			return true;
		}

		/**
		 * Debug Log Instruction.
		 *
		 * @return string
		 */
		public function log_instruction(): string {
			if ( function_exists( 'wc_get_logger' ) ) {
				return '';
			}

			if ( defined( 'WP_DEBUG_LOG' ) && true === WP_DEBUG_LOG ) {
				return '';
			}

			return ' Add <code>define(\'WP_DEBUG_LOG\', true);</code> in <code>wp-config.php</code> file.';
		}

		/**
		 * Writes a log entry via WooCommerce logger when WP_DEBUG is enabled.
		 *
		 * @param string                  $title   log title.
		 * @param array<array-key, mixed> $message log message.
		 *
		 * @return void
		 * @since 3.4.0
		 */
		public function log( string $title, array $message = array() ): void {

			if ( ! $this->is_log_enabled() ) {
				return;
			}

			$source = $this->get_plugin_dirname();

			// If WooCommerce Installed.
			if ( function_exists( 'wc_get_logger' ) ) {
				$context = array(
					'source' => $source,
				);

				wc_get_logger()->info( $title, array_merge( $message, $context ) );

				return;
			}

			if ( defined( 'WP_DEBUG_LOG' ) && true === WP_DEBUG_LOG ) {
				$msg          = print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				$message_text = sprintf( "[ %s ] => %s :\n%s\n", $source, $title, $msg );
				error_log( $message_text ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		/**
		 * Returns the WooCommerce log file URL for this plugin.
		 *
		 * @return string
		 * @since 3.4.0
		 */
		public function get_log_file_url(): string {

			$source = $this->get_plugin_dirname();

			if ( function_exists( 'wc_get_logger' ) ) {
				$query_args = array(
					'page'     => 'wc-status',
					'tab'      => 'logs',
					'log_file' => sprintf( '%s-%s.log', $source, sanitize_file_name( wp_hash( $source ) ) ),
				);

				return add_query_arg( $query_args, admin_url( 'admin.php' ) );
			}

			if ( defined( 'WP_DEBUG_LOG' ) && true === WP_DEBUG_LOG ) {
				return trailingslashit( content_url() ) . 'debug.log';
			}

			return '';
		}

		/**
		 * Load Template File.
		 *
		 * @param string               $template_name Template File Name with Extenstion.
		 * @param array<string, mixed> $args          Arguments.
		 * @param bool                 $once          Include once or multiple time.
		 *
		 * @return void
		 * @since 3.4.0
		 * @example
		 * <pre>
		 *
		 *  // Basic usage — render a settings page with data
		 * $this->load_template( 'settings-page.php', [
		 *     'page_title'  => 'My Plugin Settings',
		 *     'description' => 'Configure your plugin options below.',
		 *     'notices'     => [[ 'type' => 'success', 'message' => 'Settings saved.' ]],
		 * ] );
		 *
		 *
		 * // Render a simple header partial — use $once = true to avoid
		 *
		 * // duplicate output if called from multiple hooks in one request
		 * $this->load_template( 'partials/admin-header.php', [
		 *     'plugin_name' => 'My Plugin',
		 * ], true );
		 *
		 * // No args needed — template uses globals like $post directly*
		 *
		 * $this->load_template( 'partials/empty-state.php' );
		 * </pre>
		 */
		public function load_template( string $template_name, array $args = array(), bool $once = false ): void {
			$template_data = $args;
			$path          = trailingslashit( $this->templates_path() ) . $template_name;
			if ( $once ) {
				include_once $path;
			} else {
				include $path;
			}
		}

		/**
		 * Is dev environment.
		 *
		 * @return bool
		 */
		public function is_development_environment(): bool {

			if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
				return true;
			}

			$environments = array( 'local', 'development' );

			return in_array( wp_get_environment_type(), $environments, true );
		}

		/**
		 * Is dev environment.
		 *
		 * @return bool
		 */
		public function is_production_environment(): bool {

			if ( defined( 'WP_DEBUG' ) && false === WP_DEBUG ) {
				return true;
			}

			$environments = array( 'staging', 'production' );

			return in_array( wp_get_environment_type(), $environments, true );
		}

		/**
		 * Get URL Origin.
		 *
		 * @param string $url Site URL.
		 *
		 * @return string
		 * @since 3.5.1
		 */
		public function get_site_origin( string $url = '' ): string {

			// Get the full site URL.
			$site_url = $this->is_empty_string( $url ) ? site_url() : $url;

			// Parse the URL to get its components.
			$parsed_url = wp_parse_url( $site_url );

			// Construct the origin (protocol + host).
			$site_origin = $parsed_url['scheme'] . '://' . $parsed_url['host'];

			// If there's a non-standard port, include it.
			if ( isset( $parsed_url['port'] ) ) {
				$site_origin .= ':' . $parsed_url['port'];
			}

			return $site_origin;
		}

		/**
		 * Get URL Host Name.
		 *
		 * @param string $url Site URL.
		 *
		 * @return string
		 * @since 3.5.1
		 */
		public function get_site_hostname( string $url = '' ): string {
			// Get the full site URL.
			$site_url = $this->is_empty_string( $url ) ? site_url() : $url;

			return wp_parse_url( sanitize_url( $site_url ), PHP_URL_HOST );
		}
	}
}
