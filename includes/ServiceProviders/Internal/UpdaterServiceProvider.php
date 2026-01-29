<?php
	/**
	 * Updater Service Provider File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceProviders\Internal;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractServiceProvider;
	use StorePress\AdminUtils\Abstracts\AbstractUpdater;
	use StorePress\AdminUtils\Services\Internal\Updater\Rollback;
	use StorePress\AdminUtils\Traits\Internal\InternalServiceProviderCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\ServiceProviders\Internal\UpdaterServiceProvider' ) ) {

	/**
	 * Updater Service Provider Class.
	 *
	 * Provides services for plugin update functionality including rollback support.
	 * Registers the Rollback service with the dependency injection container.
	 *
	 * @name UpdaterServiceProvider
	 *
	 * @method AbstractUpdater get_caller() Returns the parent AbstractUpdater instance.
	 *
	 * @see AbstractServiceProvider For base service provider implementation.
	 * @see AbstractUpdater For plugin updater integration.
	 * @see Rollback For plugin rollback functionality.
	 *
	 * @example Usage in AbstractUpdater:
	 *          ```php
	 *          public function service_provider( object $caller ): UpdaterServiceProvider {
	 *              return new UpdaterServiceProvider( $caller );
	 *          }
	 *          ```
	 *
	 * @since 2.0.0
	 */
	class UpdaterServiceProvider extends AbstractServiceProvider {

		use InternalServiceProviderCommonTrait;

		/**
		 * Register services with the container.
		 *
		 * Registers the Rollback service as a factory function that creates
		 * a new instance with the parent updater as the caller.
		 *
		 * @return void
		 *
		 * @see Rollback
		 *
		 * @since 2.0.0
		 */
		public function register(): void {
			// Register Rollback service with factory callback.
			$this->get_container()->register(
				Rollback::class,
				function () {
					return new Rollback( $this->get_caller() );
				}
			);
		}

		/**
		 * Bootstrap services after all providers are registered.
		 *
		 * Resolves and initializes the Rollback service to enable
		 * plugin rollback functionality.
		 *
		 * @return void
		 *
		 * @see Rollback
		 *
		 * @since 2.0.0
		 */
		public function boot(): void {
			// Resolve Rollback service to initialize it.
			$this->get_container()->get( Rollback::class );
		}
	}
}
