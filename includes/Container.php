<?php
	/**
	 * Simple DI Container File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use RuntimeException;

if ( ! class_exists( '\StorePress\AdminUtils\Container' ) ) {

	/**
	 * Simple DI Container with singleton and factory support.
	 *
	 * @name Container
	 */
	class Container {

		use Singleton;

		/**
		 * Registered resolvers.
		 *
		 * @var array<string, callable(Container): mixed>
		 */
		private array $resolvers = array();

		/**
		 * Constructor.
		 */
		private function __construct() {}

		/**
		 * Register a service.
		 *
		 * @param string                     $id       Service identifier.
		 * @param callable(Container): mixed $resolver Resolver callback.
		 *
		 * @return self
		 */
		public function register( string $id, callable $resolver ): self {

			if ( ! $this->has( $id ) ) {
				$this->resolvers[ $id ] = $resolver;
			}

			return $this;
		}

		/**
		 * Resolve a service.
		 *
		 * @param string $id Service identifier.
		 *
		 * @return object
		 * @throws RuntimeException If service not found.
		 */
		public function get( string $id ): object {
			if ( ! $this->has( $id ) ) {
				throw new RuntimeException( sprintf( 'Class "%s" not added.', esc_html( $id ) ) );
			}

			return $this->resolvers[ $id ]( $this );
		}

		/**
		 * Check if service exists.
		 *
		 * @param string $id Service identifier.
		 *
		 * @return bool
		 */
		public function has( string $id ): bool {
			return isset( $this->resolvers[ $id ] );
		}

		/**
		 * Remove a service.
		 *
		 * @param string $id Service identifier.
		 *
		 * @return self
		 */
		public function remove( string $id ): self {
			unset( $this->resolvers[ $id ] );

			return $this;
		}

		/**
		 * Register in existing service.
		 *
		 * @param string                     $id Service identifier.
		 * @param callable(Container): mixed $resolver Resolver callback.
		 *
		 * @return self
		 */
		public function overwrite( string $id, callable $resolver ): self {
			return $this->remove( $id )->register( $id, $resolver );
		}

		/**
		 * Remove all services.
		 *
		 * @return self
		 */
		public function reset(): self {
			$this->resolvers = array();

			return $this;
		}

		/**
		 * Get all registered service IDs.
		 *
		 * @return array<int, string>
		 */
		public function list(): array {
			return array_keys( $this->resolvers );
		}
	}
}
