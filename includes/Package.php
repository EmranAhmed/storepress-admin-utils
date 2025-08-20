<?php
	/**
	 * Plugin Common Methods for Classes.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

trait Package {
	use Plugin;

	const string ASSET_PATH = 'vendor/storepress/admin-utils/build';

	/**
	 * Get script handle.
	 *
	 * @param string $filename Filename without ext.
	 *
	 * @return string
	 */
	public function get_package_script_handle( string $filename ): string {
		$filename = str_ireplace( array( '.css', '.js' ), '', $filename );
		return sprintf( 'storepress-admin-utils-%s', $filename );
	}
	/**
	 * Get script l10 object name. Like: "STOREPRESS_ADMIN_UTILS_FILENAME_PARAMS".
	 *
	 * @param string $filename Filename without ext.
	 *
	 * @return string
	 */
	public function get_package_l10_object_name( string $filename ): string {
		$filename = str_ireplace( array( '.css', '.js' ), '', $filename );
		return sprintf( 'STOREPRESS_ADMIN_UTILS_%s_PARAMS', strtoupper( $filename ) );
	}

	/**
	 * Register Package Scripts.
	 *
	 * @param string               $filename Filename without ext.
	 * @param array<string, mixed> $l10 localize strings.
	 * @return string
	 */
	public function register_package_scripts( string $filename, array $l10 = array() ): string {

		$filename = str_ireplace( array( '.css', '.js' ), '', $filename );

		$url  = untrailingslashit( plugin_dir_url( $this->get_plugin_file() ) );
		$path = untrailingslashit( plugin_dir_path( $this->get_plugin_file() ) );

		$js_file    = sprintf( '%s/%s/%s.js', $path, self::ASSET_PATH, $filename );
		$asset_file = sprintf( '%s/%s/%s.asset.php', $path, self::ASSET_PATH, $filename );
		$js_url     = sprintf( '%s/%s/%s.js', $url, self::ASSET_PATH, $filename );

		$css_file = sprintf( '%s/%s/%s.css', $path, self::ASSET_PATH, $filename );
		$css_url  = sprintf( '%s/%s/%s.css', $url, self::ASSET_PATH, $filename );

		$handle = $this->get_package_script_handle( $filename );

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
	 * Enqueue Package Scripts.
	 *
	 * @param string               $filename Filename without ext.
	 * @param array<string, mixed> $l10 localize strings.
	 *
	 * @return string
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
	 * Register Admin Utilities Scripts for Global.
	 *
	 * @return string
	 */
	public function register_package_admin_utils_script(): string {
		// Admin Utils.
		$url  = untrailingslashit( plugin_dir_url( $this->get_plugin_file() ) );
		$path = untrailingslashit( plugin_dir_path( $this->get_plugin_file() ) );

		$js_file    = sprintf( '%s/%s/storepress-utils.js', $path, self::ASSET_PATH );
		$asset_file = sprintf( '%s/%s/storepress-utils.asset.php', $path, self::ASSET_PATH );
		$js_url     = sprintf( '%s/%s/storepress-utils.js', $url, self::ASSET_PATH );

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
