<?php
/**
 * Updater Factory Class File.
 *
 * @package    StorePress/AdminUtils
 * @since      1.0.0
 * @version    1.0.0
 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Factory;

	use StorePress\AdminUtils\Abstracts\AbstractUpdater;
	use StorePress\AdminUtils\Services\Internal\Updater\Rollback;
	use StorePress\AdminUtils\Traits\SingletonTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Factory\UpdaterFactory' ) ) {
	/**
	 * Updater Factory Class.
	 *
	 * Creates updater-related service instances (Rollback) for a given updater owner.
	 *
	 * @name UpdaterFactory
	 *
	 * @since 1.0.0
	 */
	class UpdaterFactory {

		use SingletonTrait;

		/**
		 * Create a Rollback instance for the given updater.
		 *
		 * @param AbstractUpdater $updater Updater-capable plugin instance.
		 *
		 * @return Rollback
		 *
		 * @since 1.0.0
		 */
		public function create_rollback( AbstractUpdater $updater ): Rollback {
			return new Rollback( $updater );
		}
	}
}
