<?php
	/**
	 * Method Should Implement Trait File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Traits;

	use ReflectionClass;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! trait_exists( '\StorePress\AdminUtils\Traits\MethodShouldImplementTrait' ) ) {

	/**
	 * Method Should Implement Trait.
	 *
	 * Provides a method to enforce abstract-like method implementation in subclasses
	 * when using traits. Triggers a WordPress error when a method that should be
	 * overridden is called without being implemented.
	 *
	 * @name MethodShouldImplementTrait
	 *
	 * @example Usage in a base class or trait:
	 *          ```php
	 *          trait MyTrait {
	 *              use MethodShouldImplementTrait;
	 *
	 *              public function some_method(): array {
	 *                  $this->subclass_should_implement( __FUNCTION__ );
	 *                  return array();
	 *              }
	 *          }
	 *          ```
	 *
	 * @example Error message format:
	 *          Method 'some_method' not implemented. Must be overridden in subclass.
	 *          Class: MySubclass. File: /wp-content/plugins/my-plugin/includes/MySubclass.php
	 *
	 * @since 1.0.0
	 */
	trait MethodShouldImplementTrait {

		/**
		 * Trigger an error if method is not implemented in subclass.
		 *
		 * Call this method at the beginning of a method that should be overridden
		 * by subclasses. Only triggers an error when WP_DEBUG is enabled.
		 *
		 * @param string      $method_name     The method name that should be implemented (use __FUNCTION__).
		 * @param object|null $subclass_object Optional. The subclass object to check. Default null ($this).
		 *
		 * @return void
		 *
		 * @throws \WP_Exception Triggers E_USER_WARNING when WP_DEBUG is enabled.
		 *
		 * @see get_class_relative_path()
		 *
		 * @example Basic usage:
		 *          ```php
		 *          public function my_method(): void {
		 *              $this->subclass_should_implement( __FUNCTION__ );
		 *          }
		 *          ```
		 *
		 * @example With custom object:
		 *          ```php
		 *          public function my_method( object $child ): void {
		 *              $this->subclass_should_implement( __FUNCTION__, $child );
		 *          }
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function subclass_should_implement( string $method_name, ?object $subclass_object = null ): void {

			// Bail out if WP_DEBUG is not turned on.
			if ( ! WP_DEBUG ) {
				return;
			}

			$class         = $subclass_object ? $subclass_object : $this;
			$relative_file = $this->get_class_relative_path( $class );

			/* translators: %s: Method name. */
			$message = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), $method_name );
			wp_trigger_error( '', sprintf( '%s Class: <strong>%s</strong>. File: <strong>%s</strong><br />', $message, get_class( $class ), $relative_file ), E_USER_WARNING );
		}

		/**
		 * Get the relative file path for a class.
		 *
		 * Uses ReflectionClass to determine the file location of a class
		 * and returns it as a path relative to ABSPATH.
		 *
		 * @param object $class_object The class instance to get the path for.
		 *
		 * @return string The relative file path (e.g., '/wp-content/plugins/my-plugin/MyClass.php').
		 *
		 * @throws \ReflectionException If the class cannot be reflected.
		 *
		 * @see \ReflectionClass::getFileName()
		 *
		 * @since 1.0.0
		 */
		public function get_class_relative_path( object $class_object ): string {
			$class_file = ( new ReflectionClass( $class_object ) )->getFileName();
			return str_ireplace( ABSPATH, '/', $class_file );
		}
	}
}
