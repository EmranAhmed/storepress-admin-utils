<?php
/**
 * Rollback Factory Class File.
 *
 * @package    StorePress/AdminUtils
 * @since      1.0.0
 * @version    1.0.0
 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Factory;

	use StorePress\AdminUtils\Services\Internal\Updater\Dialog;
	use StorePress\AdminUtils\Services\Internal\Updater\Rollback;
	use StorePress\AdminUtils\Services\Internal\Updater\Upgrader;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use WP_Ajax_Upgrader_Skin;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Factory\RollbackFactory' ) ) {
	/**
	 * Rollback Factory Class.
	 *
	 * Creates rollback-related service instances and executes plugin rollback via the upgrader.
	 *
	 * @name RollbackFactory
	 *
	 * @since 1.0.0
	 */
	class RollbackFactory {

		use SingletonTrait;

		/**
		 * Shared upgrader skin instance.
		 *
		 * @var WP_Ajax_Upgrader_Skin|null
		 */
		protected ?WP_Ajax_Upgrader_Skin $skin = null;

		/**
		 * Create a rollback dialog instance.
		 *
		 * @param Rollback $rollback Rollback-capable plugin instance.
		 *
		 * @return Dialog
		 *
		 * @since 1.0.0
		 */
		public function create_dialog( Rollback $rollback ): Dialog {

			return new Dialog( $rollback );
		}

		/**
		 * Get a shared WP_Ajax_Upgrader_Skin instance.
		 *
		 * Lazily instantiates and reuses a single skin instance across calls.
		 *
		 * @return WP_Ajax_Upgrader_Skin
		 *
		 * @since 1.0.0
		 */
		public function get_upgrader_skin(): WP_Ajax_Upgrader_Skin {

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			return $this->skin ??= new WP_Ajax_Upgrader_Skin();
		}


		/**
		 * Execute a plugin rollback using the upgrader.
		 *
		 * @param Rollback $rollback Rollback-capable plugin instance.
		 * @param string   $package  URL or path to the rollback package.
		 *
		 * @return bool|\WP_Error True on success, false or WP_Error on failure.
		 *
		 * @see   get_upgrader_skin()
		 * @since 1.0.0
		 */
		public function run_rollback( Rollback $rollback, string $package ) {

			$skin = $this->get_upgrader_skin();

			$upgrader        = new Upgrader( $skin );
			$plugin_basename = $rollback->get_plugin_basename();

			return $upgrader->rollback( $plugin_basename, $package );
		}
	}
}
