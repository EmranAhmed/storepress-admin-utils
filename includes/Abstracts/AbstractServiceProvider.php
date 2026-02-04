<?php
	/**
	 * Abstract Service Provider Class File.
	 *
	 * Provides a base implementation for service providers that register services
	 * with a dependency injection container. Service providers handle the binding
	 * of services and bootstrapping of required functionality.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    3.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Interfaces\ContainerInterface;
	use StorePress\AdminUtils\Traits\CallerTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractServiceProvider' ) ) {

	/**
	 * Abstract Service Provider Class.
	 *
	 * Base class for service providers that register services with a dependency
	 * injection container. Service providers follow the register/boot lifecycle:
	 *
	 * 1. **Register**: Bind services, factories, and dependencies to the container.
	 * 2. **Boot**: Perform initialization that requires other services to be available.
	 *
	 * This pattern allows for modular, testable code where dependencies are
	 * explicitly declared and can be easily swapped or mocked.
	 *
	 * Subclasses must implement:
	 * - `register()`: Bind services to the container.
	 * - `boot()`: Initialize services after all providers are registered.
	 *
	 * Optionally override:
	 * - `init()`: Add custom initialization logic after construction.
	 *
	 * @name AbstractServiceProvider
	 *
	 * @phpstan-use CallerTrait<AbstractServiceProvider>
	 *
	 * @see ContainerInterface For the container contract.
	 * @see RegisterServiceProviderTrait For provider registration helpers.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractServiceProvider {

		use CallerTrait;

		// =====================================================================
		// Constructor and Initialization Methods
		// =====================================================================

		/**
		 * Create a new service provider instance.
		 *
		 * Initializes the service provider by setting the caller reference
		 * (typically the class that implements HasServiceProviderInterface).
		 *
		 * @param object $caller The caller object that owns this service provider.
		 *                       This object must implement a `get_container()` method.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Creating a custom service provider:
		 * class MyServiceProvider extends AbstractServiceProvider {
		 *     public function register(): void {
		 *         $this->get_container()->set( MyService::class, function() {
		 *             return new MyService();
		 *         });
		 *     }
		 *
		 *     public function boot(): void {
		 *         $service = $this->get_container()->get( MyService::class );
		 *         $service->initialize();
		 *     }
		 * }
		 *
		 * // Instantiate with caller:
		 * $provider = new MyServiceProvider( $this );
		 *
		 * @see CallerTrait::set_caller() For caller management.
		 */
		public function __construct( object $caller ) {
			$this->set_caller( $caller );
			$this->init();
		}

		/**
		 * Custom initialization hook for subclasses.
		 *
		 * Override this method in subclasses to add custom initialization logic
		 * that runs after the constructor sets the caller reference.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // In your subclass:
		 * public function init(): void {
		 *     // Setup additional configuration
		 *     $this->setup_config();
		 * }
		 */
		public function init(): void {}

		// =====================================================================
		// Abstract Lifecycle Methods
		// =====================================================================

		/**
		 * Register services with the container.
		 *
		 * This method is called when the provider is registered with the container.
		 * Use this method to bind services, factories, or other dependencies.
		 *
		 * During registration, avoid resolving services from the container as
		 * not all providers may be registered yet. Use `boot()` for initialization
		 * that requires other services.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Register services:
		 * public function register(): void {
		 *     // Bind a simple service
		 *     $this->get_container()->set( Logger::class, function() {
		 *         return new FileLogger( '/path/to/log' );
		 *     });
		 *
		 *     // Bind with dependencies
		 *     $this->get_container()->set( UserService::class, function( $container ) {
		 *         return new UserService(
		 *             $container->get( Database::class ),
		 *             $container->get( Logger::class )
		 *         );
		 *     });
		 * }
		 *
		 * @see self::boot() For post-registration initialization.
		 */
		abstract public function register(): void;

		/**
		 * Bootstrap any application services.
		 *
		 * This method is called after all providers have been registered.
		 * Use this method to perform any actions that require other services
		 * to be available in the container, such as registering hooks,
		 * initializing services, or setting up event listeners.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Bootstrap services:
		 * public function boot(): void {
		 *     // Initialize a service that needs configuration
		 *     $logger = $this->get_container()->get( Logger::class );
		 *     $logger->setLevel( 'debug' );
		 *
		 *     // Register WordPress hooks
		 *     $userService = $this->get_container()->get( UserService::class );
		 *     add_action( 'init', array( $userService, 'initialize' ) );
		 *
		 *     // Set up event listeners
		 *     add_action( 'user_registered', array( $userService, 'onUserRegistered' ) );
		 * }
		 *
		 * @see self::register() For service binding.
		 */
		abstract public function boot(): void;
	}
}
