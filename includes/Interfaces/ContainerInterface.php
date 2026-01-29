<?php
	/**
	 * Container Interface File.
	 *
	 * Defines the contract for dependency injection containers used throughout
	 * the admin utilities framework. Implementations provide service registration,
	 * resolution, and existence checking capabilities.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Interfaces;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! interface_exists( '\StorePress\AdminUtils\Interfaces\ContainerInterface' ) ) {

	/**
	 * Container Interface.
	 *
	 * Provides the contract for service container implementations. Containers
	 * manage service registration and resolution, enabling dependency injection
	 * patterns throughout the application.
	 *
	 * Implementations should support:
	 * - Registering services with factory callbacks
	 * - Resolving services by identifier (typically class names)
	 * - Checking for service existence before resolution
	 *
	 * @name ContainerInterface
	 *
	 * @since 2.0.0
	 *
	 * @see \StorePress\AdminUtils\ServiceContainers\BaseServiceContainer For the base implementation.
	 * @see \StorePress\AdminUtils\ServiceContainers\InternalServiceContainer For internal service container.
	 *
	 * @example Basic container usage:
	 *          ```php
	 *          // Register a service
	 *          $container->register( MyService::class, function ( $container ) {
	 *              return new MyService();
	 *          } );
	 *
	 *          // Check and resolve
	 *          if ( $container->has( MyService::class ) ) {
	 *              $service = $container->get( MyService::class );
	 *          }
	 *          ```
	 *
	 * @example Implementing the interface:
	 *          ```php
	 *          class MyContainer implements ContainerInterface {
	 *              private array $services = [];
	 *
	 *              public function register( string $id, callable $resolver ): self {
	 *                  $this->services[ $id ] = $resolver;
	 *                  return $this;
	 *              }
	 *
	 *              public function get( string $id, ...$args ): object {
	 *                  return $this->services[ $id ]( $this, ...$args );
	 *              }
	 *
	 *              public function has( string $id ): bool {
	 *                  return isset( $this->services[ $id ] );
	 *              }
	 *          }
	 *          ```
	 *
	 * @example Service with dependencies:
	 *          ```php
	 *          $container->register( Logger::class, fn( $c ) => new Logger() );
	 *          $container->register( UserService::class, function ( $container ) {
	 *              return new UserService( $container->get( Logger::class ) );
	 *          } );
	 *          ```
	 */
	interface ContainerInterface {

		/**
		 * Register a service resolver with the container.
		 *
		 * Binds a service identifier to a factory callback that creates the service
		 * instance when resolved. The resolver receives the container instance as
		 * its first argument, enabling dependency resolution.
		 *
		 * @since 2.0.0
		 *
		 * @param string   $id       Service identifier, typically a fully-qualified class name.
		 * @param callable $resolver Factory callback that returns the service instance.
		 *                           Signature: `function( ContainerInterface $container, mixed ...$args ): object`.
		 *
		 * @return self Returns the container instance for method chaining.
		 *
		 * @see ContainerInterface::get() To resolve registered services.
		 * @see ContainerInterface::has() To check if a service is registered.
		 *
		 * @example Register a simple service:
		 *          ```php
		 *          $container->register( Cache::class, function ( $container ) {
		 *              return new FileCache( '/tmp/cache' );
		 *          } );
		 *          ```
		 *
		 * @example Register with arrow function:
		 *          ```php
		 *          $container->register( Logger::class, fn( $c ) => new Logger() );
		 *          ```
		 *
		 * @example Method chaining:
		 *          ```php
		 *          $container
		 *              ->register( Logger::class, fn( $c ) => new Logger() )
		 *              ->register( Cache::class, fn( $c ) => new Cache() )
		 *              ->register( Database::class, fn( $c ) => new Database() );
		 *          ```
		 *
		 * @example Service with runtime arguments:
		 *          ```php
		 *          $container->register( Report::class, function ( $container, $type, $date ) {
		 *              return new Report( $type, $date );
		 *          } );
		 *          // Resolve with: $container->get( Report::class, 'sales', '2024-01-01' );
		 *          ```
		 */
		public function register( string $id, callable $resolver ): self;

		/**
		 * Resolve a service from the container.
		 *
		 * Retrieves a service instance by invoking its registered resolver callback.
		 * Additional arguments are passed to the resolver after the container instance.
		 *
		 * @since 2.0.0
		 *
		 * @template T of object
		 *
		 * @param class-string<T> $id      Service identifier to resolve.
		 * @param mixed           ...$args Additional arguments to pass to the resolver.
		 *
		 * @return T The resolved service instance.
		 *
		 * @throws \RuntimeException If the service identifier is not registered.
		 *
		 * @see ContainerInterface::register() To register services before resolution.
		 * @see ContainerInterface::has() To check existence before resolution.
		 *
		 * @example Basic resolution:
		 *          ```php
		 *          $logger = $container->get( Logger::class );
		 *          $logger->info( 'Application started' );
		 *          ```
		 *
		 * @example Resolution with arguments:
		 *          ```php
		 *          $report = $container->get( Report::class, 'monthly', '2024-01' );
		 *          ```
		 *
		 * @example Safe resolution with existence check:
		 *          ```php
		 *          if ( $container->has( CacheDriver::class ) ) {
		 *              $cache = $container->get( CacheDriver::class );
		 *          } else {
		 *              $cache = new NullCache();
		 *          }
		 *          ```
		 *
		 * @example Resolution with type inference (PHPStan/IDE support):
		 *          ```php
		 *          // IDE will know $user is UserRepository instance
		 *          $user = $container->get( UserRepository::class );
		 *          $users = $user->findAll(); // Autocomplete works
		 *          ```
		 *
		 * @phpstan-return T
		 *
		 * @phpcs:disable Squiz.Commenting.FunctionComment.IncorrectTypeHint
		 */
		public function get( string $id, ...$args ): object;

		/**
		 * Check if a service is registered in the container.
		 *
		 * Determines whether a service with the given identifier has been registered.
		 * Use this method before calling get() to avoid exceptions for missing services.
		 *
		 * @since 2.0.0
		 *
		 * @param string $id Service identifier to check.
		 *
		 * @return bool True if the service is registered, false otherwise.
		 *
		 * @see ContainerInterface::register() To register services.
		 * @see ContainerInterface::get() To resolve registered services.
		 *
		 * @example Check before resolution:
		 *          ```php
		 *          if ( $container->has( OptionalService::class ) ) {
		 *              $service = $container->get( OptionalService::class );
		 *              $service->doSomething();
		 *          }
		 *          ```
		 *
		 * @example Conditional registration:
		 *          ```php
		 *          if ( ! $container->has( Logger::class ) ) {
		 *              $container->register( Logger::class, fn( $c ) => new NullLogger() );
		 *          }
		 *          ```
		 *
		 * @example Feature detection:
		 *          ```php
		 *          $features = array(
		 *              'caching'  => $container->has( CacheDriver::class ),
		 *              'logging'  => $container->has( Logger::class ),
		 *              'database' => $container->has( Database::class ),
		 *          );
		 *          ```
		 */
		public function has( string $id ): bool;
	}
}
