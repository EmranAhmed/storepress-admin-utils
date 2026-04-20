<?php
/**
 * Deactivation Feedback Factory Class File.
 *
 * @package    StorePress/AdminUtils
 * @since      1.0.0
 * @version    1.0.0
 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Factory;

	use StorePress\AdminUtils\Abstracts\AbstractDeactivationFeedback;
	use StorePress\AdminUtils\Services\Internal\DeactivationFeedback\Dialog;
	use StorePress\AdminUtils\Traits\SingletonTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Factory\DeactivationFeedbackFactory' ) ) {
	/**
	 * Deactivation Feedback Factory Class.
	 *
	 * Creates deactivation feedback service instances for a given feedback owner.
	 *
	 * @name DeactivationFeedbackFactory
	 *
	 * @since 1.0.0
	 */
	class DeactivationFeedbackFactory {

		use SingletonTrait;

		/**
		 * Create a deactivation feedback dialog instance.
		 *
		 * @param AbstractDeactivationFeedback $feedback Deactivation-feedback-capable plugin instance.
		 *
		 * @return Dialog
		 *
		 * @since 1.0.0
		 */
		public function create_dialog( AbstractDeactivationFeedback $feedback ): Dialog {
			return new Dialog( $feedback );
		}
	}
}
