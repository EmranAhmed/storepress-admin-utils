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

trait Plugin {

	/**
	 * Plugin Version.
	 *
	 * @var string $plugin_version Plugin version.
	 */
	private string $plugin_version = '';

	/**
	 * Plugin Name.
	 *
	 * @var string $plugin_name Plugin Name.
	 */
	private string $plugin_name = '';

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
}
