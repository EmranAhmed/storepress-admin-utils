<?php
	/**
	 * Rollback Service Provider File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceProviders\Internal;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractServiceProvider;
	use StorePress\AdminUtils\Services\Internal\Updater\Dialog;
	use StorePress\AdminUtils\Services\Internal\Updater\Rollback;
	use StorePress\AdminUtils\Traits\Internal\InternalServiceProviderCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\ServiceProviders\Internal\RollbackServiceProvider' ) ) {

	/**
	 * Rollback Service Provider Class.
	 *
	 * Provides services for the plugin rollback changelog dialog functionality.
	 * Registers the Dialog service for displaying version changelogs when
	 * a user initiates a plugin rollback.
	 *
	 * @name RollbackServiceProvider
	 *
	 * @method Rollback get_caller() Returns the parent Rollback service instance.
	 *
	 * @see AbstractServiceProvider For base service provider implementation.
	 * @see Rollback For plugin rollback functionality.
	 * @see Dialog For changelog dialog UI.
	 *
	 * @example Usage in Rollback service:
	 *          ```php
	 *          public function service_provider( object $caller ): RollbackServiceProvider {
	 *              return new RollbackServiceProvider( $caller );
	 *          }
	 *          ```
	 *
	 * @since 2.0.0
	 */
	class RollbackServiceProvider extends AbstractServiceProvider {

		use InternalServiceProviderCommonTrait;

		/**
		 * Register services with the container.
		 *
		 * Registers the Dialog service as a factory function that creates
		 * a new instance with the parent Rollback service as the caller.
		 *
		 * @return void
		 *
		 * @see Dialog
		 *
		 * @since 2.0.0
		 */
		public function register(): void {
			// Register Dialog service with factory callback.
			$this->get_container()->register(
				Dialog::class,
				function () {
					return new Dialog( $this->get_caller() );
				}
			);
		}

		/**
		 * Bootstrap services after all providers are registered.
		 *
		 * Resolves and initializes the Dialog service to enable the
		 * changelog dialog for plugin rollback.
		 *
		 * @return void
		 *
		 * @see Dialog
		 *
		 * @since 2.0.0
		 */
		public function boot(): void {
			// Resolve Dialog service to initialize it.
			$this->get_container()->get( Dialog::class );
		}
	}
}
