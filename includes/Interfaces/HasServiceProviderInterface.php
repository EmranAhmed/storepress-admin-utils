<?php
	/**
	 * Has Service Provider Interface File.
	 *
	 * Defines the contract for classes that manage service providers through
	 * a dependency injection lifecycle. Implementing classes can register,
	 * boot, and resolve service providers for modular architecture.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      3.1.0
	 * @version    3.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Interfaces;

	use StorePress\AdminUtils\Abstracts\AbstractServiceProvider;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! interface_exists( '\StorePress\AdminUtils\Interfaces\HasServiceProviderInterface' ) ) {

	/**
	 * Has Service Provider Interface.
	 *
	 * Provides the contract for classes that support service provider management.
	 * This interface enables a standardized lifecycle for service providers:
	 *
	 * 1. **Create**: Instantiate a service provider via `service_provider()`.
	 * 2. **Register**: Bind the provider using `register_service_provider()`.
	 * 3. **Register Services**: Call `register()` on all providers via `register_services()`.
	 * 4. **Boot Services**: Call `boot()` on all providers via `boot_services()`.
	 *
	 * Implementing classes should maintain a container and collection of providers
	 * to support this lifecycle pattern.
	 *
	 * @name HasServiceProviderInterface
	 *
	 * @since 3.1.0
	 *
	 * @see AbstractServiceProvider For the service provider base class.
	 * @see ContainerInterface For the service container contract.
	 * @see \StorePress\AdminUtils\Traits\RegisterServiceProviderTrait For default implementation.
	 *
	 * @example Implementing the interface:
	 *          ```php
	 *          class MyPlugin implements HasServiceProviderInterface {
	 *              use RegisterServiceProviderTrait;
	 *
	 *              public function init(): void {
	 *                  $this->register_service_provider( $this );
	 *                  $this->register_services();
	 *                  $this->boot_services();
	 *              }
	 *
	 *              public function service_provider( object $caller ): AbstractServiceProvider {
	 *                  return new MyServiceProvider( $caller );
	 *              }
	 *          }
	 *          ```
	 *
	 * @example Multiple service providers:
	 *          ```php
	 *          class Application implements HasServiceProviderInterface {
	 *              private array $providers = [];
	 *
	 *              public function service_provider( object $caller ): AbstractServiceProvider {
	 *                  return new CoreServiceProvider( $caller );
	 *              }
	 *
	 *              public function register_service_provider( object $caller ): void {
	 *                  $this->providers[] = $this->service_provider( $caller );
	 *                  // Add additional providers
	 *                  $this->providers[] = new DatabaseServiceProvider( $caller );
	 *                  $this->providers[] = new CacheServiceProvider( $caller );
	 *              }
	 *          }
	 *          ```
	 *
	 * @example WordPress plugin integration:
	 *          ```php
	 *          class WooExtension implements HasServiceProviderInterface {
	 *              use RegisterServiceProviderTrait;
	 *
	 *              public function __construct() {
	 *                  add_action( 'plugins_loaded', array( $this, 'init' ) );
	 *              }
	 *
	 *              public function init(): void {
	 *                  $this->register_service_provider( $this );
	 *                  $this->register_services();
	 *                  add_action( 'init', array( $this, 'boot_services' ) );
	 *              }
	 *          }
	 *          ```
	 */
	interface HasServiceProviderInterface {

		/**
		 * Create and return a service provider instance.
		 *
		 * Factory method that creates the appropriate service provider for the
		 * implementing class. The caller object is passed to the provider to
		 * establish the parent-child relationship and enable container access.
		 *
		 * @since 3.1.0
		 *
		 * @param object $caller The caller object that owns the service provider.
		 *                       Typically the implementing class instance (`$this`).
		 *
		 * @return AbstractServiceProvider The instantiated service provider.
		 *
		 * @see AbstractServiceProvider For the provider base class.
		 * @see HasServiceProviderInterface::register_service_provider() For registering the provider.
		 *
		 * @example Return a custom service provider:
		 *          ```php
		 *          public function service_provider( object $caller ): AbstractServiceProvider {
		 *              return new MyPluginServiceProvider( $caller );
		 *          }
		 *          ```
		 *
		 * @example Conditional provider based on environment:
		 *          ```php
		 *          public function service_provider( object $caller ): AbstractServiceProvider {
		 *              if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		 *                  return new DebugServiceProvider( $caller );
		 *              }
		 *              return new ProductionServiceProvider( $caller );
		 *          }
		 *          ```
		 *
		 * @example Provider with configuration:
		 *          ```php
		 *          public function service_provider( object $caller ): AbstractServiceProvider {
		 *              $provider = new ConfigurableServiceProvider( $caller );
		 *              $provider->configure( $this->get_config() );
		 *              return $provider;
		 *          }
		 *          ```
		 */
		public function service_provider( object $caller ): AbstractServiceProvider;

		/**
		 * Register the service provider with the container.
		 *
		 * Adds the service provider to the internal collection for lifecycle
		 * management. This method should instantiate the provider via
		 * `service_provider()` and store it for later registration and booting.
		 *
		 * @since 3.1.0
		 *
		 * @param object $caller The caller object to pass to the service provider.
		 *                       Typically the implementing class instance (`$this`).
		 *
		 * @return void
		 *
		 * @see HasServiceProviderInterface::service_provider() For creating the provider.
		 * @see HasServiceProviderInterface::register_services() For registering services.
		 *
		 * @example Basic registration:
		 *          ```php
		 *          public function register_service_provider( object $caller ): void {
		 *              $this->provider = $this->service_provider( $caller );
		 *          }
		 *          ```
		 *
		 * @example Register multiple providers:
		 *          ```php
		 *          public function register_service_provider( object $caller ): void {
		 *              $this->providers[] = $this->service_provider( $caller );
		 *              $this->providers[] = new AdditionalProvider( $caller );
		 *          }
		 *          ```
		 *
		 * @example Conditional registration:
		 *          ```php
		 *          public function register_service_provider( object $caller ): void {
		 *              $this->providers[] = $this->service_provider( $caller );
		 *
		 *              if ( is_admin() ) {
		 *                  $this->providers[] = new AdminServiceProvider( $caller );
		 *              }
		 *          }
		 *          ```
		 */
		public function register_service_provider( object $caller ): void;

		/**
		 * Register all services from registered providers.
		 *
		 * Iterates through all registered service providers and calls their
		 * `register()` method to bind services to the container. This should
		 * be called after all providers are registered but before booting.
		 *
		 * During registration, providers should only bind services without
		 * resolving dependencies, as not all services may be available yet.
		 *
		 * @since 3.1.0
		 *
		 * @return void
		 *
		 * @see HasServiceProviderInterface::register_service_provider() Must be called first.
		 * @see HasServiceProviderInterface::boot_services() Should be called after.
		 * @see AbstractServiceProvider::register() The method called on each provider.
		 *
		 * @example Basic implementation:
		 *          ```php
		 *          public function register_services(): void {
		 *              foreach ( $this->providers as $provider ) {
		 *                  $provider->register();
		 *              }
		 *          }
		 *          ```
		 *
		 * @example With error handling:
		 *          ```php
		 *          public function register_services(): void {
		 *              foreach ( $this->providers as $provider ) {
		 *                  try {
		 *                      $provider->register();
		 *                  } catch ( \Exception $e ) {
		 *                      error_log( 'Provider registration failed: ' . $e->getMessage() );
		 *                  }
		 *              }
		 *          }
		 *          ```
		 *
		 * @example With action hook:
		 *          ```php
		 *          public function register_services(): void {
		 *              foreach ( $this->providers as $provider ) {
		 *                  $provider->register();
		 *              }
		 *              do_action( 'my_plugin_services_registered', $this->get_container() );
		 *          }
		 *          ```
		 */
		public function register_services(): void;

		/**
		 * Boot all services from registered providers.
		 *
		 * Iterates through all registered service providers and calls their
		 * `boot()` method for post-registration initialization. This should
		 * be called after `register_services()` when all services are available.
		 *
		 * During booting, providers can safely resolve services from the
		 * container and perform initialization that requires dependencies.
		 *
		 * @since 3.1.0
		 *
		 * @return void
		 *
		 * @see HasServiceProviderInterface::register_services() Must be called first.
		 * @see AbstractServiceProvider::boot() The method called on each provider.
		 *
		 * @example Basic implementation:
		 *          ```php
		 *          public function boot_services(): void {
		 *              foreach ( $this->providers as $provider ) {
		 *                  $provider->boot();
		 *              }
		 *          }
		 *          ```
		 *
		 * @example Deferred booting with WordPress:
		 *          ```php
		 *          public function boot_services(): void {
		 *              // Boot on 'init' hook for WordPress dependencies
		 *              add_action( 'init', function () {
		 *                  foreach ( $this->providers as $provider ) {
		 *                      $provider->boot();
		 *                  }
		 *              } );
		 *          }
		 *          ```
		 *
		 * @example With completion action:
		 *          ```php
		 *          public function boot_services(): void {
		 *              foreach ( $this->providers as $provider ) {
		 *                  $provider->boot();
		 *              }
		 *              do_action( 'my_plugin_booted', $this );
		 *          }
		 *          ```
		 */
		public function boot_services(): void;
	}
}
