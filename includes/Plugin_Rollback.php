<?php
	/**
	 * Plugin Rollback API Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use Plugin_Upgrader;

if ( ! class_exists( '\StorePress\AdminUtils\Plugin_Rollback' ) ) {

	/**
	 * Plugin Updater API Class.
	 *
	 * @name Plugin_Rollback
	 */
	class Plugin_Rollback extends Plugin_Upgrader {

		/**
		 * Upgrades a plugin.
		 *
		 * @param string               $plugin             Path to the plugin file relative to the plugins directory.
		 * @param string               $package            Path to the plugin zip file.
		 * @param array<string, mixed> $args               {
		 *   Optional. Other arguments for upgrading a plugin package. Default empty array.
		 *
		 *     @type bool $clear_update_cache Whether to clear the plugin updates cache if successful.
		 *                                    Default true.
		 * }
		 * @return bool|\WP_Error True if the upgrade was successful, false or a WP_Error object otherwise.
		 * @since 1.13.0
		 *
		 * @see bulk_upgrade
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
