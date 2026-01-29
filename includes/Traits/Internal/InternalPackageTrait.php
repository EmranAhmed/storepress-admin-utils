<?php
	/**
	 * Internal Package Trait File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Traits\Internal;

	use StorePress\AdminUtils\Traits\PluginCommonTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! trait_exists( '\StorePress\AdminUtils\Traits\Internal\InternalPackageTrait' ) ) {
	/**
	 * Internal Package Trait.
	 *
	 * Provides methods for registering and enqueuing the admin-utils package
	 * assets (JavaScript, CSS, templates, and images). Used internally by
	 * the library to load its own assets.
	 *
	 * @name InternalPackageTrait
	 *
	 * @see PluginCommonTrait For vendor path methods.
	 *
	 * @example Usage in a class:
	 *          ```php
	 *          class MyClass {
	 *              use InternalPackageTrait;
	 *
	 *              public function plugin_file(): string {
	 *                  return MY_PLUGIN_FILE;
	 *              }
	 *
	 *              public function load_assets(): void {
	 *                  $this->register_package_scripts( 'settings', array( 'key' => 'value' ) );
	 *                  $this->enqueue_package_scripts( 'settings' );
	 *              }
	 *          }
	 *          ```
	 *
	 * @since 1.0.0
	 */
	trait InternalPackageTrait {
		use PluginCommonTrait;

		/**
		 * Composer package name.
		 *
		 * The package identifier used to locate the admin-utils assets
		 * in the vendor directory.
		 *
		 * @var string
		 */
		protected string $package_name = 'storepress/admin-utils';

		/**
		 * Get the package build directory path.
		 *
		 * Returns the absolute filesystem path to the compiled assets directory.
		 *
		 * @return string The build directory path.
		 *
		 * @see vendor_path()
		 *
		 * @since 1.0.0
		 */
		public function get_package_build_path(): string {
			return sprintf( '%s/%s/build', $this->vendor_path(), $this->package_name );
		}

		/**
		 * Get the package build directory URL.
		 *
		 * Returns the URL to the compiled assets directory.
		 *
		 * @return string The build directory URL.
		 *
		 * @see vendor_url()
		 *
		 * @since 1.0.0
		 */
		public function get_package_build_url(): string {
			return sprintf( '%s/%s/build', $this->vendor_url(), $this->package_name );
		}

		/**
		 * Get the package templates directory path.
		 *
		 * Returns the absolute filesystem path to the templates directory.
		 *
		 * @return string The templates directory path.
		 *
		 * @see vendor_path()
		 *
		 * @since 1.0.0
		 */
		public function get_package_template_path(): string {
			return sprintf( '%s/%s/templates', $this->vendor_path(), $this->package_name );
		}

		/**
		 * Get the package images directory path.
		 *
		 * Returns the absolute filesystem path to the images directory.
		 *
		 * @return string The images directory path.
		 *
		 * @see vendor_path()
		 *
		 * @since 1.0.0
		 */
		public function get_package_image_path(): string {
			return sprintf( '%s/%s/images', $this->vendor_path(), $this->package_name );
		}

		/**
		 * Get the package images directory URL.
		 *
		 * Returns the URL to the images directory.
		 *
		 * @return string The images directory URL.
		 *
		 * @see vendor_url()
		 *
		 * @since 1.0.0
		 */
		public function get_package_image_url(): string {
			return sprintf( '%s/%s/images', $this->vendor_url(), $this->package_name );
		}

		/**
		 * Get the WordPress script/style handle for a package asset.
		 *
		 * Generates a unique handle name for use with wp_register_script/style.
		 *
		 * @param string $filename The filename without extension.
		 *
		 * @return string The script handle (e.g., 'storepress-admin-utils-settings').
		 *
		 * @example Get handle:
		 *          ```php
		 *          $handle = $this->get_package_script_handle( 'settings' );
		 *          // Returns: 'storepress-admin-utils-settings'
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function get_package_script_handle( string $filename ): string {
			$filename = str_ireplace( array( '.css', '.js' ), '', $filename );
			return sprintf( 'storepress-admin-utils-%s', $filename );
		}

		/**
		 * Get the localization object name for a package script.
		 *
		 * Generates the JavaScript global variable name for localized data.
		 *
		 * @param string $filename The filename without extension.
		 *
		 * @return string The l10n object name (e.g., 'STOREPRESS_ADMIN_UTILS_SETTINGS_PARAMS').
		 *
		 * @example Get object name:
		 *          ```php
		 *          $name = $this->get_package_l10_object_name( 'settings' );
		 *          // Returns: 'STOREPRESS_ADMIN_UTILS_SETTINGS_PARAMS'
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function get_package_l10_object_name( string $filename ): string {
			$filename = str_ireplace( array( '.css', '.js' ), '', $filename );
			return sprintf( 'STOREPRESS_ADMIN_UTILS_%s_PARAMS', strtoupper( $filename ) );
		}

		/**
		 * Register package scripts and styles with WordPress.
		 *
		 * Registers both JavaScript and CSS files for a given filename,
		 * along with optional localization data.
		 *
		 * @param string               $filename The filename without extension (e.g., 'settings').
		 * @param array<string, mixed> $l10      Optional. Localization strings to pass to JavaScript.
		 *
		 * @return string The registered script handle, or empty string on failure.
		 *
		 * @see wp_register_script()
		 * @see wp_register_style()
		 * @see wp_localize_script()
		 *
		 * @example Register settings assets:
		 *          ```php
		 *          $handle = $this->register_package_scripts( 'settings', array(
		 *              'saveText' => __( 'Save Settings' ),
		 *          ) );
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function register_package_scripts( string $filename, array $l10 = array() ): string {

			$filename = str_ireplace( array( '.css', '.js' ), '', $filename );

			$js_file    = sprintf( '%s/%s.js', $this->get_package_build_path(), $filename );
			$asset_file = sprintf( '%s/%s.asset.php', $this->get_package_build_path(), $filename );
			$js_url     = sprintf( '%s/%s.js', $this->get_package_build_url(), $filename );

			$css_file = sprintf( '%s/%s.css', $this->get_package_build_path(), $filename );
			$css_url  = sprintf( '%s/%s.css', $this->get_package_build_url(), $filename );

			$handle = $this->get_package_script_handle( $filename );

			$this->register_package_storepress_utils_script();

			if ( ! file_exists( $asset_file ) ) {
				$message = sprintf( 'File: "%s" not found.', $asset_file );
				wp_trigger_error( __METHOD__, $message );
				return '';
			}

			$assets = include $asset_file;

			if ( file_exists( $js_file ) ) {
				wp_register_script(
					$handle,
					$js_url,
					$assets['dependencies'],
					$assets['version'],
					array(
						'in_footer' => true,
					)
				);

				if ( ! $this->is_empty_array( $l10 ) ) {
					$l10_name = $this->get_package_l10_object_name( $filename );
					wp_localize_script( $handle, $l10_name, $l10 );
				}
			}

			if ( file_exists( $css_file ) ) {
				wp_register_style( $handle, $css_url, array(), $assets['version'] );
			}

			return $handle;
		}

		/**
		 * Enqueue previously registered package scripts and styles.
		 *
		 * Enqueues both JavaScript and CSS files for a given filename.
		 * Optionally adds additional localization data.
		 *
		 * @param string               $filename The filename without extension (e.g., 'settings').
		 * @param array<string, mixed> $l10      Optional. Additional localization strings.
		 *
		 * @return string The enqueued script handle.
		 *
		 * @see wp_enqueue_script()
		 * @see wp_enqueue_style()
		 * @see register_package_scripts()
		 *
		 * @example Enqueue settings assets:
		 *          ```php
		 *          $this->enqueue_package_scripts( 'settings' );
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function enqueue_package_scripts( string $filename, array $l10 = array() ): string {

			$filename = str_ireplace( array( '.css', '.js' ), '', $filename );
			$handle   = $this->get_package_script_handle( $filename );

			wp_enqueue_script( $handle );

			if ( ! $this->is_empty_array( $l10 ) ) {
				$l10_name = $this->get_package_l10_object_name( $filename );
				wp_localize_script( $handle, $l10_name, $l10 );
			}

			wp_enqueue_style( $handle );

			return $handle;
		}

		/**
		 * Register the global StorePress utilities script.
		 *
		 * Registers the shared 'storepress-utils' JavaScript file that provides
		 * common utilities used by other package scripts.
		 *
		 * @return string The registered script handle ('storepress-utils'), or empty string on failure.
		 *
		 * @see wp_register_script()
		 *
		 * @since 1.0.0
		 */
		public function register_package_storepress_utils_script(): string {

			$js_file    = sprintf( '%s/storepress-utils.js', $this->get_package_build_path() );
			$asset_file = sprintf( '%s/storepress-utils.asset.php', $this->get_package_build_path() );
			$js_url     = sprintf( '%s/storepress-utils.js', $this->get_package_build_url() );

			if ( ! file_exists( $asset_file ) ) {
				$message = sprintf( 'File: "%s" not found.', $asset_file );
				wp_trigger_error( __METHOD__, $message );
				return '';
			}

			$assets = include $asset_file;

			wp_register_script(
				'storepress-utils',
				$js_url,
				$assets['dependencies'],
				$assets['version'],
				array(
					'in_footer' => true,
				)
			);

			return 'storepress-utils';
		}
	}
}
