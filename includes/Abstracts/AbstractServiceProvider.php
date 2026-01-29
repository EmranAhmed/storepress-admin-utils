<?php
	/**
	 * Abstract Service Provider Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Traits\CallerTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractServiceProvider' ) ) {

	/**
	 * Abstract Service Provider Class.
	 *
	 * Base class for service providers that register services with the container.
	 * Service providers are responsible for binding services into the container
	 * and bootstrapping any required functionality.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractServiceProvider {

		use CallerTrait;

		/**
		 * Create a new service provider instance.
		 *
		 * @since 1.0.0
		 *
		 * @param object $caller The service container instance.
		 */
		public function __construct( object $caller ) {
			$this->set_caller( $caller );
			$this->init();
		}

		/**
		 * Init method
		 *
		 * @return void
		 */
		public function init(): void {}

		/**
		 * Container.
		 *
		 * @return object
		 */
		public function get_container(): object {
			return $this->get_caller()->get_container();
		}

		/**
		 * Register services with the container.
		 *
		 * This method is called when the provider is registered with the container.
		 * Use this method to bind services, factories, or other dependencies.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		abstract public function register(): void;

		/**
		 * Bootstrap any application services.
		 *
		 * This method is called after all providers have been registered.
		 * Use this method to perform any actions that require other services
		 * to be available in the container.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		abstract public function boot(): void;
	}
}
