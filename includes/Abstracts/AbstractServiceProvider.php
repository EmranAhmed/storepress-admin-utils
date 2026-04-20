<?php
	/**
	 * Abstract Service Provider Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    3.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use Psr\Container\ContainerInterface;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractServiceProvider' ) ) {

	/**
	 * Base class for service providers with register/boot lifecycle.
	 *
	 * @name AbstractServiceProvider
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractServiceProvider {

		/**
		 * The dependency injection container instance.
		 *
		 * @var ContainerInterface
		 *
		 * @since 1.0.0
		 */
		protected ContainerInterface $container;

		// =====================================================================
		// Constructor and Initialization Methods
		// =====================================================================

		/**
		 * Constructor.
		 *
		 * @param ContainerInterface|null $container The container instance.
		 *
		 * @since 1.0.0
		 */
		public function __construct( ContainerInterface $container = null ) {

			if ( $container ) {
				$this->container = $container;
			}

			$this->init();
		}

		/**
		 * Get service container.
		 *
		 * @return ContainerInterface
		 *
		 * @since 3.1.0
		 */
		public function get_container(): ContainerInterface {
			return $this->container;
		}

		/**
		 * Called after constructor. Override for custom initialization.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function init(): void {}

		// =====================================================================
		// Abstract Lifecycle Methods
		// =====================================================================

		/**
		 * Register services with the container. Avoid resolving services here.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::boot()
		 */
		abstract public function register(): void;

		/**
		 * Bootstrap services after all providers are registered.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::register()
		 */
		abstract public function boot(): void;
	}
}
