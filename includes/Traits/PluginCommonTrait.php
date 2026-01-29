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
	 * Plugin Trait.
	 *
	 * @name PluginCommonTrait
	 */

	trait PluginCommonTrait {

		/**
		 * Plugin Version.
		 *
		 * @var string $plugin_version Plugin version.
		 */
		protected string $plugin_version = '';

		/**
		 * Plugin Name.
		 *
		 * @var string $plugin_name Plugin Name.
		 */
		protected string $plugin_name = '';

		/**
		 * Get plugin file absolute or relative path.
		 *
		 * @return string
		 */
		abstract public function plugin_file(): string;

		/**
		 * Get absolute plugin file path.
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 */
		public function get_plugin_absolute_file( string $plugin_file = '' ): string {
			$file   = '' === $plugin_file ? wp_normalize_path( $this->plugin_file() ) : wp_normalize_path( $plugin_file );
			$plugin = plugin_basename( $file );

			return trailingslashit( WP_PLUGIN_DIR ) . $plugin;
		}

		/**
		 * Get Plugin absolute file
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 */
		public function get_plugin_file( string $plugin_file = '' ): string {
			return $this->get_plugin_absolute_file( $plugin_file );
		}

		/**
		 * Plugin Directory Name Only.
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 * @example xyz-plugin
		 */
		public function get_plugin_dir_path( string $plugin_file = '' ): string {
			return untrailingslashit( plugin_dir_path( $this->get_plugin_file( $plugin_file ) ) );
		}

		/**
		 * Plugin Slug.
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 * @example xyz-plugin
		 */
		public function get_plugin_slug( string $plugin_file = '' ): string {
			return wp_basename( dirname( $this->get_plugin_file( $plugin_file ) ) );
		}

		/**
		 * Plugin Basename Like "plugin-directory/plugin-file.php"
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 * @example xyz-plugin/xyz-plugin.php
		 */
		public function get_plugin_basename( string $plugin_file = '' ): string {
			return plugin_basename( $this->get_plugin_file( $plugin_file ) );
		}

		/**
		 * Plugin Dir URL.
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 */
		public function get_plugin_dir_url( string $plugin_file = '' ): string {
			return untrailingslashit( plugin_dir_url( $this->get_plugin_file( $plugin_file ) ) );
		}

		/**
		 * Get Plugin Version.
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function get_plugin_version( string $plugin_file = '' ): string {

			if ( '' === $this->plugin_version ) {
				$versions             = get_file_data( $this->get_plugin_file( $plugin_file ), array( 'version' => 'Version' ) );
				$this->plugin_version = $versions['version'] ?? '';
			}

			return $this->plugin_version;
		}

		/**
		 * Get Plugin Name.
		 *
		 * @param string $plugin_file Optional. The plugin file path to convert. Can be relative
		 *                            or absolute. If empty, defaults to the current plugin's
		 *                            main file path retrieved via $this->plugin_file().
		 *                            Default empty string.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function get_plugin_name( string $plugin_file = '' ): string {

			if ( '' === $this->plugin_name ) {
				$names             = get_file_data( $this->get_plugin_file( $plugin_file ), array( 'name' => 'Plugin Name' ) );
				$this->plugin_name = $names['name'] ?? '';
			}

			return $this->plugin_name;
		}

		/**
		 * Get Plugin image url
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function images_url(): string {
			return $this->get_plugin_dir_url() . '/images';
		}

		/**
		 * Get Asset URL
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function assets_url(): string {
			return $this->get_plugin_dir_url() . '/assets';
		}

		/**
		 * Get Asset path
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function assets_path(): string {
			return $this->get_plugin_dir_path() . '/assets';
		}

		/**
		 * Get Asset version
		 *
		 * @param string $file Asset file name.
		 *
		 * @return false|int asset file make time.
		 * @since 1.0.0
		 */
		public function assets_version( string $file ) {
			return filemtime( $this->assets_path() . $file );
		}

		/**
		 * Get Build URL
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function build_url(): string {
			return $this->get_plugin_dir_url() . '/build';
		}

		/**
		 * Get Build path
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function build_path(): string {
			return $this->get_plugin_dir_path() . '/build';
		}

		/**
		 * Get Include path.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function include_path(): string {
			return $this->get_plugin_dir_path() . '/includes';
		}

		/**
		 * Get Templates path.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function template_path(): string {
			return $this->get_plugin_dir_path() . '/templates';
		}

		/**
		 * Get Vendor path.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function vendor_path(): string {
			return $this->get_plugin_dir_path() . '/vendor';
		}

		/**
		 * Get Vendor url.
		 *
		 * @return string
		 * @since 1.0.0
		 */
		public function vendor_url(): string {
			return $this->get_plugin_dir_url() . '/vendor';
		}

		/**
		 * Register StorePress Utils script.
		 *
		 * @return string
		 */
		public function register_storepress_utils_script(): string {

			if ( ! file_exists( $this->build_path() . '/storepress-utils.js' ) ) {
				return '';
			}

			$file_url   = $this->build_url() . '/storepress-utils.js';
			$asset_path = $this->build_path() . '/storepress-utils.asset.php';
			$asset      = include $asset_path;

			wp_register_script( 'storepress-utils', $file_url, $asset['dependencies'], $asset['version'], array( 'strategy' => 'defer' ) );
			return 'storepress-utils';
		}
	}
}
