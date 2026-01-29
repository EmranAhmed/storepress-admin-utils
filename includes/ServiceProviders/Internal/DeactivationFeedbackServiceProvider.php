<?php
	/**
	 * Deactivation Feedback Service Provider File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceProviders\Internal;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractDeactivationFeedback;
	use StorePress\AdminUtils\Abstracts\AbstractServiceProvider;
	use StorePress\AdminUtils\Services\Internal\DeactivationFeedback\Dialog;
	use StorePress\AdminUtils\Traits\Internal\InternalServiceProviderCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\ServiceProviders\Internal\DeactivationFeedbackServiceProvider' ) ) {

	/**
	 * Deactivation Feedback Service Provider Class.
	 *
	 * Provides services for the plugin deactivation feedback functionality.
	 * Registers the Dialog service for displaying the feedback form when
	 * a user deactivates the plugin.
	 *
	 * @name DeactivationFeedbackServiceProvider
	 *
	 * @method AbstractDeactivationFeedback get_caller() Returns the parent AbstractDeactivationFeedback instance.
	 *
	 * @see AbstractServiceProvider For base service provider implementation.
	 * @see AbstractDeactivationFeedback For deactivation feedback integration.
	 * @see Dialog For feedback dialog UI.
	 *
	 * @example Usage in AbstractDeactivationFeedback:
	 *          ```php
	 *          public function service_provider( object $caller ): DeactivationFeedbackServiceProvider {
	 *              return new DeactivationFeedbackServiceProvider( $caller );
	 *          }
	 *          ```
	 *
	 * @since 2.0.0
	 */
	class DeactivationFeedbackServiceProvider extends AbstractServiceProvider {

		use InternalServiceProviderCommonTrait;

		/**
		 * Register services with the container.
		 *
		 * Registers the Dialog service as a factory function that creates
		 * a new instance with the parent deactivation feedback as the caller.
		 *
		 * @return void
		 *
		 * @see Dialog
		 *
		 * @since 2.0.0
		 */
		public function register(): void {
			// Register Dialog service with factory callback.
			$this->get_container()->register(
				Dialog::class,
				function () {
					return new Dialog( $this->get_caller() );
				}
			);
		}

		/**
		 * Bootstrap services after all providers are registered.
		 *
		 * Resolves and initializes the Dialog service to display the
		 * deactivation feedback form.
		 *
		 * @return void
		 *
		 * @see Dialog
		 *
		 * @since 2.0.0
		 */
		public function boot(): void {
			// Resolve Dialog service to initialize it.
			$this->get_container()->get( Dialog::class );
		}
	}
}
