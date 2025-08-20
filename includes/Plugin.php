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
	 * Get plugin file absolute or relative path.
	 *
	 * @return string
	 */
	abstract public function plugin_file(): string;

	/**
	 * Get absolute plugin file path.
	 *
	 * @return string
	 */
	public function get_plugin_absolute_file(): string {
		$file   = wp_normalize_path( $this->plugin_file() );
		$plugin = plugin_basename( $file );

		return trailingslashit( WP_PLUGIN_DIR ) . $plugin;
	}

	/**
	 * Get Plugin absolute file
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->get_plugin_absolute_file();
	}

	/**
	 * Plugin Directory Name Only.
	 *
	 * @return string
	 * @example xyz-plugin
	 */
	public function get_plugin_dir_path(): string {
		return untrailingslashit( plugin_dir_path( $this->get_plugin_file() ) );
	}

	/**
	 * Plugin Slug.
	 *
	 * @return string
	 * @example xyz-plugin
	 */
	public function get_plugin_slug(): string {
		return wp_basename( dirname( $this->get_plugin_file() ) );
	}

	/**
	 * Plugin Basename Like "plugin-directory/plugin-file.php"
	 *
	 * @return string
	 * @example xyz-plugin/xyz-plugin.php
	 */
	public function get_plugin_basename(): string {
		return plugin_basename( $this->get_plugin_file() );
	}

	/**
	 * Plugin Dir URL.
	 *
	 * @return string
	 */
	public function get_plugin_dir_url(): string {
		return untrailingslashit( plugin_dir_url( $this->get_plugin_file() ) );
	}

	/**
	 * Get Plugin Version.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_plugin_version(): string {
		static $versions;

		if ( is_null( $versions ) ) {
			$versions = get_file_data(
				$this->get_plugin_file(),
				array( 'version' => 'Version' )
			);
		}

		return $versions['version'] ?? '';
	}
}
