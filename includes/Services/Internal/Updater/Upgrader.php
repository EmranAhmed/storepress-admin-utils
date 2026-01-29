<?php
	/**
	 * Plugin Upgrader Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\Updater;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use Plugin_Upgrader;

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Updater\Upgrader' ) ) {

	/**
	 * Plugin Upgrader Class for Rollback Functionality.
	 *
	 * Extends WordPress Plugin_Upgrader to provide rollback capability.
	 * Allows installing a specific version of a plugin from a provided
	 * package URL instead of the latest version.
	 *
	 * @name Upgrader
	 *
	 * @see \Plugin_Upgrader For base upgrader functionality.
	 * @see Rollback For the rollback service that uses this class.
	 *
	 * @example Usage:
	 *          ```php
	 *          $skin     = new WP_Ajax_Upgrader_Skin();
	 *          $upgrader = new Upgrader( $skin );
	 *          $result   = $upgrader->rollback(
	 *              'plugin-folder/plugin-file.php',
	 *              'https://example.com/plugin-1.0.0.zip'
	 *          );
	 *          ```
	 *
	 * @since 1.0.0
	 */
	class Upgrader extends Plugin_Upgrader {

		/**
		 * Rollback a plugin to a specific version.
		 *
		 * Performs a plugin "upgrade" using a specific package URL instead of
		 * fetching the latest version. This allows rolling back to any available
		 * previous version.
		 *
		 * The process:
		 * 1. Connects to filesystem
		 * 2. Deactivates plugin before upgrade
		 * 3. Deletes old plugin files
		 * 4. Installs the specified package
		 * 5. Reactivates the plugin
		 * 6. Clears plugin cache
		 *
		 * @param string               $plugin  Path to the plugin file relative to the plugins directory.
		 * @param string               $package URL or path to the plugin zip file for the target version.
		 * @param array<string, mixed> $args    {
		 *     Optional. Arguments for the rollback operation. Default empty array.
		 *
		 *     @type bool $clear_update_cache Whether to clear the plugin updates cache if successful.
		 *                                    Default true.
		 * }
		 *
		 * @return bool|\WP_Error True if rollback successful, false or WP_Error on failure.
		 *
		 * @see \Plugin_Upgrader::bulk_upgrade() For similar upgrade logic.
		 * @see \WP_Upgrader::run() For the core upgrade process.
		 *
		 * @since 1.0.0
		 */
		public function rollback( string $plugin, string $package, array $args = array() ) {
			$defaults = array(
				'clear_update_cache' => true,
			);

			$parsed_args = wp_parse_args( $args, $defaults );

			$this->init();
			$this->bulk = false;
			$this->upgrade_strings();

			// Connect to the filesystem first.
			$res = $this->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR, trailingslashit( WP_PLUGIN_DIR ) . $plugin ) );
			if ( ! $res ) {
				return false;
			}

			add_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ), 10, 2 );
			add_filter( 'upgrader_pre_install', array( $this, 'active_before' ), 10, 2 );
			add_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ), 10, 4 );
			add_filter( 'upgrader_post_install', array( $this, 'active_after' ), 10, 2 );

			$this->run(
				array(
					'package'           => $package,
					'destination'       => WP_PLUGIN_DIR,
					'clear_destination' => true,
					'clear_working'     => true,
					'hook_extra'        => array(
						'plugin'      => $plugin,
						'type'        => 'plugin',
						'action'      => 'update',
						'bulk'        => false,
						'temp_backup' => array(
							'slug' => dirname( $plugin ),
							'src'  => WP_PLUGIN_DIR,
							'dir'  => 'plugins',
						),
					),
				)
			);

			// Cleanup our hooks, in case something else does an upgrade on this connection.
			remove_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9 );
			remove_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ) );
			remove_filter( 'upgrader_pre_install', array( $this, 'active_before' ) );
			remove_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ) );
			remove_filter( 'upgrader_post_install', array( $this, 'active_after' ) );

			if ( ! $this->result || is_wp_error( $this->result ) ) {
				return $this->result;
			}

			$activate = activate_plugin( $plugin );

			if ( is_wp_error( $activate ) ) {
				return $activate;
			}

			// Force refresh of plugin update information.
			wp_clean_plugins_cache();

			return true;
		}
	}
}
