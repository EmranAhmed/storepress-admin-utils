<?php
	/**
	 * Caller Trait File.
	 *
	 * @package      StorePress/AdminUtils
	 * @since        1.11.1
	 * @version      1.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Traits;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! trait_exists( '\StorePress\AdminUtils\Traits\CallerTrait' ) ) {

	/**
	 * Trait for injecting a caller/parent class instance.
	 *
	 * @name CallerTrait
	 * @template T of object
	 */
	trait CallerTrait {

		use MethodShouldImplementTrait;

		/**
		 * The caller class instance.
		 *
		 * @var T
		 */
		protected object $caller;

		/**
		 * Initialize the caller instance.
		 *
		 * @param T $caller Caller class instance.
		 *
		 * @phpcs:disable Squiz.Commenting.FunctionComment.IncorrectTypeHint
		 */
		public function set_caller( object $caller ): void {
			$this->caller = $caller;
		}

		/**
		 * Get the caller class instance.
		 *
		 * @return T
		 */
		public function get_caller(): object {
			return $this->caller;
		}

		/**
		 * Get caller name.
		 *
		 * @return string
		 */
		public function get_caller_name(): string {
			return get_class( $this->get_caller() );
		}

		/**
		 * Get caller id.
		 *
		 * @return int
		 */
		public function get_caller_id(): int {
			return spl_object_id( $this->get_caller() );
		}

		/**
		 * Get plugin file absolute or relative path.
		 *
		 * @abstract Must be overridden in subclass.
		 * @return string
		 * @throws \WP_Exception Throws exception, If method not available.
		 */
		public function plugin_file(): string {
			$method_exists = method_exists( $this->get_caller(), 'get_plugin_file' );

			if ( ! $method_exists ) {

				$this->subclass_should_implement( 'get_plugin_file', $this->get_caller() );
				return '';
			}

			return $this->get_caller()->get_plugin_file();
		}
	}
}
