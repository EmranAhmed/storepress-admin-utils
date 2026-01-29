<?php
	/**
	 * Service Container File.
	 *
	 * Provides a simple dependency injection container for registering and resolving services.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceContainers;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use RuntimeException;
	use StorePress\AdminUtils\Interfaces\ContainerInterface;

if ( ! class_exists( '\StorePress\AdminUtils\ServiceContainers\BaseServiceContainer' ) ) {

	/**
	 * BaseServiceContainer Class.
	 *
	 * A simple DI container with support for service registration, resolution, and lifecycle management.
	 * Services can be registered as singletons (resolved once) or factories (resolved each time).
	 *
	 * @name BaseServiceContainer
	 *
	 * @example Basic Usage
	 * ```php
	 * // Create container instance
	 * $container = new BaseServiceContainer();
	 *
	 * // Register a simple service
	 * $container->register( MyService::class, function ( $container ) {
	 *     return new MyService();
	 * } );
	 *
	 * // Resolve the service
	 * $service = $container->get( MyService::class );
	 * ```
	 *
	 * @example Registering Service with Dependencies
	 * ```php
	 * // Register a logger service
	 * $container->register( Logger::class, function ( $container ) {
	 *     return new Logger( '/var/log/app.log' );
	 * } );
	 *
	 * // Register a service that depends on Logger
	 * $container->register( UserService::class, function ( $container ) {
	 *     $logger = $container->get( Logger::class );
	 *     return new UserService( $logger );
	 * } );
	 *
	 * // Resolve UserService (Logger will be resolved automatically)
	 * $userService = $container->get( UserService::class );
	 * ```
	 *
	 * @example Singleton Pattern
	 * ```php
	 * // For singleton behavior, store instance in static variable
	 * $container->register( Database::class, function ( $container ) {
	 *     static $instance = null;
	 *     return $instance ??= new Database( DB_HOST, DB_USER, DB_PASS );
	 * } );
	 *
	 * // Both calls return the same instance
	 * $db1 = $container->get( Database::class );
	 * $db2 = $container->get( Database::class );
	 * // $db1 === $db2
	 * ```
	 *
	 * @example Passing Arguments to Resolver
	 * ```php
	 * // Register service that accepts runtime arguments
	 * $container->register( EmailSender::class, function ( $container, $to, $subject ) {
	 *     $mailer = $container->get( Mailer::class );
	 *     return new EmailSender( $mailer, $to, $subject );
	 * } );
	 *
	 * // Pass arguments when resolving
	 * $sender = $container->get( EmailSender::class, 'user@example.com', 'Welcome!' );
	 * ```
	 *
	 * @since 2.0.0
	 */
	class BaseServiceContainer implements ContainerInterface {

		// =========================================================================
		// Properties
		// =========================================================================

		/**
		 * Registered service resolvers.
		 *
		 * Maps service identifiers to their resolver callbacks. Each resolver is a callable
		 * that receives the container instance and returns the resolved service.
		 *
		 * @var array<string, callable(BaseServiceContainer): mixed>
		 *
		 * @since 2.0.0
		 */
		protected array $resolvers = array();

		// =========================================================================
		// Registration Methods
		// =========================================================================

		/**
		 * Register a service resolver.
		 *
		 * Registers a new service with the container. The resolver callback will be invoked
		 * each time the service is resolved via the get() method. The service is not registered
		 * if a service with the same ID already exists.
		 *
		 * @template T of object
		 *
		 * @param class-string<T> $id       Service identifier (typically class name).
		 * @param callable        $resolver Resolver callback that returns the service instance.
		 *
		 * @return self Returns the container instance for method chaining.
		 *
		 * @phpstan-param callable(self, mixed...): T $resolver
		 *
		 * @example Register a simple service
		 * ```php
		 * $container->register( PaymentGateway::class, function ( $container ) {
		 *     return new PaymentGateway( 'api_key_here' );
		 * } );
		 * ```
		 *
		 * @example Method chaining
		 * ```php
		 * $container
		 *     ->register( Logger::class, fn( $c ) => new Logger() )
		 *     ->register( Cache::class, fn( $c ) => new Cache() )
		 *     ->register( Database::class, fn( $c ) => new Database() );
		 * ```
		 *
		 * @since 2.0.0
		 *
		 * @phpcs:disable Squiz.Commenting.FunctionComment.IncorrectTypeHint
		 */
		public function register( string $id, callable $resolver ): self {

			if ( ! $this->has( $id ) ) {
				$this->resolvers[ $id ] = $resolver;
			}

			return $this;
		}

		/**
		 * Overwrite an existing service or register a new one if it doesn't exist.
		 *
		 * Removes any existing service with the given ID and registers a new one with the provided resolver.
		 *
		 * @template T of object
		 *
		 * @param class-string<T> $id       Service identifier (typically class name).
		 * @param callable        $resolver Resolver callback that returns the service instance.
		 *
		 * @phpstan-param callable(self, mixed...): T $resolver
		 *
		 * @return self Returns the container instance for method chaining.
		 *
		 * @example Replace an existing service
		 * ```php
		 * // Original registration
		 * $container->register( Mailer::class, fn( $c ) => new SMTPMailer() );
		 *
		 * // Override with a mock for testing
		 * $container->overwrite( Mailer::class, fn( $c ) => new MockMailer() );
		 * ```
		 *
		 * @since 2.0.0
		 *
		 * @phpcs:disable Squiz.Commenting.FunctionComment.IncorrectTypeHint
		 */
		public function overwrite( string $id, callable $resolver ): self {
			return $this->remove( $id )->register( $id, $resolver );
		}

		// =========================================================================
		// Resolution Methods
		// =========================================================================

		/**
		 * Resolve a service from the container.
		 *
		 * Invokes the resolver callback for the specified service ID and returns the result.
		 * The resolver is called each time, so it's suitable for both singletons and factories.
		 *
		 * @template T of object
		 *
		 * @param class-string<T> $id      Service identifier.
		 * @param mixed           ...$args Additional arguments to pass to the resolver.
		 *
		 * @return T The resolved service instance.
		 *
		 * @throws RuntimeException If service with the given ID is not registered.
		 *
		 * @example Resolve a registered service
		 * ```php
		 * $container->register( UserRepository::class, fn( $c ) => new UserRepository() );
		 *
		 * $repo = $container->get( UserRepository::class );
		 * $users = $repo->findAll();
		 * ```
		 *
		 * @example Resolve with additional arguments
		 * ```php
		 * $container->register( Report::class, function ( $container, $type, $date ) {
		 *     return new Report( $type, $date );
		 * } );
		 *
		 * $report = $container->get( Report::class, 'sales', '2024-01-01' );
		 * ```
		 *
		 * @since 2.0.0
		 *
		 * @phpcs:disable Squiz.Commenting.FunctionComment.IncorrectTypeHint
		 */
		public function get( string $id, ...$args ): object {

			if ( ! $this->has( $id ) ) {
				throw new RuntimeException( sprintf( 'Class "%s" not added.', esc_html( $id ) ) );
			}

			return $this->resolvers[ $id ]( $this, ...$args );
		}

		/**
		 * Check if a service is registered in the container.
		 *
		 * @param string $id Service identifier.
		 *
		 * @return bool True if service is registered, false otherwise.
		 *
		 * @example Check before resolving
		 * ```php
		 * if ( $container->has( CacheDriver::class ) ) {
		 *     $cache = $container->get( CacheDriver::class );
		 * } else {
		 *     // Use fallback
		 *     $cache = new ArrayCache();
		 * }
		 * ```
		 *
		 * @since 2.0.0
		 */
		public function has( string $id ): bool {
			return isset( $this->resolvers[ $id ] );
		}

		// =========================================================================
		// Removal Methods
		// =========================================================================

		/**
		 * Remove a registered service from the container.
		 *
		 * @param string $id Service identifier.
		 *
		 * @return self Returns the container instance for method chaining.
		 *
		 * @example Remove a service
		 * ```php
		 * $container->register( TempService::class, fn( $c ) => new TempService() );
		 *
		 * // Later, remove the service
		 * $container->remove( TempService::class );
		 *
		 * // This will now return false
		 * $container->has( TempService::class ); // false
		 * ```
		 *
		 * @since 2.0.0
		 */
		public function remove( string $id ): self {
			unset( $this->resolvers[ $id ] );

			return $this;
		}

		/**
		 * Remove all registered services from the container.
		 *
		 * @return self Returns the container instance for method chaining.
		 *
		 * @example Reset container for testing
		 * ```php
		 * // In test setup
		 * public function setUp(): void {
		 *     $this->container = new BaseServiceContainer();
		 *     $this->container->reset(); // Ensure clean state
		 * }
		 * ```
		 *
		 * @since 2.0.0
		 */
		public function reset(): self {
			$this->resolvers = array();

			return $this;
		}

		// =========================================================================
		// Utility Methods
		// =========================================================================

		/**
		 * Get all registered service identifiers.
		 *
		 * Returns an array of all currently registered service IDs.
		 *
		 * @return array<int, string> List of registered service identifiers.
		 *
		 * @example List all registered services
		 * ```php
		 * $container->register( Logger::class, fn( $c ) => new Logger() );
		 * $container->register( Cache::class, fn( $c ) => new Cache() );
		 *
		 * $services = $container->list();
		 * // ['Logger', 'Cache']
		 *
		 * foreach ( $services as $service_id ) {
		 *     echo "Registered: {$service_id}\n";
		 * }
		 * ```
		 *
		 * @since 2.0.0
		 */
		public function list(): array {
			return array_keys( $this->resolvers );
		}
	}
}
