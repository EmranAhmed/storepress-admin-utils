<?php
	/**
	 * Service Provider Interface File.
	 *
	 * @package      StorePress/AdminUtils
	 * @since        3.9.0
	 * @version      1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Interfaces;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! interface_exists( '\StorePress\AdminUtils\Interfaces\ServiceProviderInterface' ) ) {

	/**
	 * Contract for singleton service providers with a register / boot lifecycle.
	 *
	 * Implementations are consumed by the service provider loader, which resolves
	 * every provider through instance(), calls register() on each of them and then
	 * calls boot().
	 *
	 * @name    ServiceProviderInterface
	 * @package StorePress/AdminUtils
	 * @since   3.9.0
	 *
	 * @see     \StorePress\AdminUtils\Abstracts\AbstractServiceProvider
	 * @see     \StorePress\AdminUtils\ServiceProviders\ServiceProviderLoader
	 *
	 * @example class BlocksServiceProvider extends AbstractServiceProvider implements ServiceProviderInterface {}
	 */
	interface ServiceProviderInterface {

		/**
		 * Return the singleton instance of the service provider.
		 *
		 * The instance will be created if it does not exist yet.
		 *
		 * @return static
		 * @phpstan-return static
		 *
		 * @since  3.9.0
		 *
		 * @see    \StorePress\AdminUtils\Traits\SingletonTrait::instance()
		 * @TODO: After Minimaum PHP Requrement is finalize from WP and WC We will change return type to static. PHP 7.4 Does not support return type static now.
		 */
		public static function instance();

		/**
		 * Register services with the container.
		 *
		 * Runs on every provider before any provider is booted. Bind services here
		 * only, avoid resolving them as the remaining providers may not have been
		 * registered yet.
		 *
		 * @return void
		 *
		 * @since  3.9.0
		 *
		 * @see    self::boot()
		 */
		public function register(): void;

		/**
		 * Bootstrap services after all providers are registered.
		 *
		 * Runs once every provider has been registered, so services can safely be
		 * resolved from the container and hooked into WordPress actions and filters.
		 *
		 * @return void
		 *
		 * @since  3.9.0
		 *
		 * @see    self::register()
		 */
		public function boot(): void;
	}
}
