<?php
	/**
	 * Internal ServiceProvider Common Trait File.
	 *
	 * @package      StorePress/AdminUtils
	 * @since        1.11.1
	 * @version      1.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Traits\Internal;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\ServiceContainers\InternalServiceContainer;

if ( ! trait_exists( '\StorePress\AdminUtils\Traits\Internal\InternalServiceProviderCommonTrait' ) ) {

	/**
	 * Internal Service Provider Common Trait.
	 *
	 * Provides service container initialization and access for internal service providers.
	 *
	 * @name InternalServiceProviderCommonTrait
	 *
	 * @see InternalServiceContainer For the singleton container implementation.
	 *
	 * @since 1.11.1
	 */
	trait InternalServiceProviderCommonTrait {

		/**
		 * Service container holder.
		 *
		 * @var object
		 */
		protected object $container;

		/**
		 * Initialize and set the service container.
		 *
		 * @return void
		 *
		 * @since 1.11.1
		 */
		public function init(): void {
			$this->container = new InternalServiceContainer();
		}

		/**
		 * Get the service container instance.
		 *
		 * @return InternalServiceContainer The service container.
		 *
		 * @since 1.11.1
		 */
		public function get_container(): InternalServiceContainer {
			return $this->container;
		}
	}
}
