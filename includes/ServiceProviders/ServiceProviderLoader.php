<?php
	/**
	 * Service Providers Loader File.
	 *
	 * Iterates and load all registered service providers.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      3.4.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceProviders;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	/**
	 * Bootstraps all registered service providers.
	 *
	 * Accepts an array of service provider class names and calls
	 * register() then boot() on each in sequence.
	 *
	 * @name    ServiceProviderLoader
	 * @package StorePress/AdminUtils
	 * @since   1.0.0
	 *
	 * @example new ServiceProviderLoader( [ BlocksServiceProvider::class, BlockSupportServiceProvider::class ] );
	 * @example ServiceProviderLoader::get_instance()->get_providers();
	 */
class ServiceProviderLoader {

	/**
	 * Registered service provider class names.
	 *
	 * @var array<int, class-string>
	 */
	protected array $service_providers = array();

	// =====================================================================
	// Service Lifecycle Methods
	// =====================================================================

	/**
	 * Stores the provider list and immediately boots all providers via init().
	 *
	 * @param  array<int, class-string> $service_providers List of service provider class names.
	 *
	 * @since  3.4.0
	 * @see    init()
	 */
	public function __construct( array $service_providers = array() ) {
		$this->service_providers = $service_providers;
		$this->init();
	}

	/**
	 * Returns all registered service provider class names.
	 *
	 * @since   3.4.0
	 * @return  array<int, class-string>
	 * @see     init()
	 * @example ServiceProviders::instance()->get_providers(); // [ BlocksServiceProvider::class, ... ]
	 */
	public function get_providers(): array {
		return $this->service_providers;
	}

	// =====================================================================
	// Service Provider Registration Methods
	// =====================================================================

	/**
	 * Instantiates, registers, and boots each service provider in order.
	 *
	 * @since  3.4.0
	 * @return void
	 * @see    get_providers()
	 */
	protected function init(): void {
		$providers = $this->get_providers();

		foreach ( $providers as $provider ) {
			$instance = $provider::instance();
			$instance->register();

			if ( method_exists( $instance, 'boot' ) ) {
				$instance->boot();
			}
		}
	}
}
