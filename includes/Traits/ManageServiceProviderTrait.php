<?php
	/**
	 * Internal ServiceProvider Common Trait File.
	 *
	 * This file contains the ManageServiceProviderTrait which provides methods
	 * for managing service provider registration, retrieval, and lifecycle
	 * (register/boot) operations.
	 *
	 * @package      StorePress/AdminUtils
	 * @since        1.11.1
	 * @version      1.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Traits;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! trait_exists( '\StorePress\AdminUtils\Traits\ManageServiceProviderTrait' ) ) {
	/**
	 * Trait for managing service provider registration.
	 *
	 * Provides a standardized interface for registering, retrieving, and managing
	 * service provider instances. Service providers handle the registration and
	 * bootstrapping of services within the application.
	 *
	 * @since 1.11.1
	 *
	 * @template TProvider of object The service provider type that will be created and managed.
	 * @template TCaller of object The caller type that requests the service provider.
	 *
	 * @phpstan-template TProvider of object
	 * @phpstan-template TCaller of object
	 *
	 * @example Using the trait in a class:
	 *          ```php
	 *          class MyContainer {
	 *              use ManageServiceProviderTrait;
	 *
	 *              public function service_provider( object $caller ): object {
	 *                  return new MyServiceProvider( $caller );
	 *              }
	 *          }
	 *          ```
	 *
	 * @example Registering and booting services:
	 *          ```php
	 *          $container = new MyContainer();
	 *          $container->register_service_provider( $this );
	 *          $container->register_services();
	 *          $container->boot_services();
	 *          ```
	 */
	trait ManageServiceProviderTrait {

		// =====================================================================
		// Properties
		// =====================================================================

		/**
		 * The service provider instance.
		 *
		 * Holds the instantiated service provider object that manages
		 * service registration and bootstrapping.
		 *
		 * @since 1.11.1
		 *
		 * @var object
		 *
		 * @phpstan-var TProvider
		 */
		protected object $provider;

		// =====================================================================
		// Service Provider Registration Methods
		// =====================================================================

		/**
		 * Set the service provider for the given caller.
		 *
		 * Initializes and stores the service provider instance by calling
		 * the abstract service_provider() method with the provided caller.
		 *
		 * @since 1.11.1
		 *
		 * @param object $caller The caller instance requesting the service provider.
		 *
		 * @phpstan-param TCaller $caller
		 *
		 * @return void
		 *
		 * @see ManageServiceProviderTrait::service_provider() For provider creation.
		 * @see ManageServiceProviderTrait::get_service_provider() For provider retrieval.
		 *
		 * @example
		 *          ```php
		 *          $container->register_service_provider( $this );
		 *          ```
		 */
		public function register_service_provider( object $caller ): void {
			$this->provider = $this->service_provider( $caller );
		}

		/**
		 * Get the current service provider instance.
		 *
		 * Returns the previously registered service provider instance.
		 * Must be called after register_service_provider().
		 *
		 * @since 1.11.1
		 *
		 * @return object The service provider instance.
		 *
		 * @phpstan-return TProvider
		 *
		 * @see ManageServiceProviderTrait::register_service_provider() For provider registration.
		 *
		 * @example
		 *          ```php
		 *          $provider = $container->get_service_provider();
		 *          $provider->doSomething();
		 *          ```
		 */
		public function get_service_provider(): object {
			return $this->provider;
		}

		/**
		 * Create and return a service provider for the given caller.
		 *
		 * Abstract method that must be implemented to create the specific
		 * service provider instance for the implementing class.
		 *
		 * @since 1.11.1
		 *
		 * @param object $caller The caller instance requesting the service provider.
		 *
		 * @phpstan-param TCaller $caller
		 *
		 * @return object The created service provider instance.
		 *
		 * @phpstan-return TProvider
		 *
		 * @example Implementation example:
		 *          ```php
		 *          public function service_provider( object $caller ): object {
		 *              return new MyServiceProvider( $caller );
		 *          }
		 *          ```
		 */
		abstract public function service_provider( object $caller ): object;

		// =====================================================================
		// Service Lifecycle Methods
		// =====================================================================

		/**
		 * Register services.
		 *
		 * Calls the register() method on the service provider to register
		 * all services. Should be called after register_service_provider().
		 *
		 * @since 1.11.1
		 *
		 * @return void
		 *
		 * @see ManageServiceProviderTrait::boot_services() For booting services after registration.
		 *
		 * @example
		 *          ```php
		 *          $container->register_service_provider( $this );
		 *          $container->register_services(); // Registers all services
		 *          $container->boot_services();     // Boots all services
		 *          ```
		 */
		public function register_services(): void {
			$this->get_service_provider()->register();
		}

		/**
		 * Boot services.
		 *
		 * Calls the boot() method on the service provider to bootstrap
		 * all registered services. Should be called after register_services().
		 *
		 * @since 1.11.1
		 *
		 * @return void
		 *
		 * @see ManageServiceProviderTrait::register_services() For registering services first.
		 *
		 * @example
		 *          ```php
		 *          $container->register_services(); // Register first
		 *          $container->boot_services();     // Then boot
		 *          ```
		 */
		public function boot_services(): void {
			$this->get_service_provider()->boot();
		}
	}
}
