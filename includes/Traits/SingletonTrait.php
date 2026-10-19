<?php
	/**
	 * Singleton Trait File.
	 *
	 * @package      StorePress/AdminUtils
	 * @since        1.11.1
	 * @version      1.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Traits;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! trait_exists( '\StorePress\AdminUtils\Traits\SingletonTrait' ) ) {

	/**
	 * Singleton Trait.
	 *
	 * @name SingletonTrait
	 *
	 * Which method to use:
	 *
	 * - instance()      For classes that are meant to be extended (e.g. Blocks → Blocks_Pro).
	 *                   Each class in the hierarchy gets its own instance, and
	 *                   Blocks_Pro::instance() returns a Blocks_Pro.
	 *                   The using class must declare @phpstan-consistent-constructor
	 *                   (unless it is final), and child constructors must accept the
	 *                   same arguments as the parent's.
	 *
	 * - self_instance() For final classes only (e.g. License).
	 *                   Always creates the class that uses the trait, never a subclass.
	 *                   Calling it on a subclass returns the parent's instance, so do not
	 *                   use it in classes that can be extended.
	 *
	 * Both methods use their arguments only on the first call; later calls return the
	 * existing instance and ignore any arguments. Arguments are not type-checked by
	 * PHPStan, so prefer argument-free constructors where possible.
	 */
	trait SingletonTrait {

		/**
		 * Return the singleton instance of the called class (late static binding).
		 *
		 * The instance is created on the first call. Each subclass gets its own
		 * separate instance, so this is the method to use in extendable base classes.
		 *
		 * Usage:
		 *     Blocks::instance();     // Blocks
		 *     Blocks_Pro::instance(); // Blocks_Pro (separate instance)
		 *
		 * Requirements:
		 * - Add @phpstan-consistent-constructor to the using class unless it is final.
		 * - Subclass constructors must stay compatible with the parent's (same or
		 *   additional optional parameters only).
		 *
		 * @since 1.0.0
		 *
		 * @param mixed ...$args Constructor arguments. Used only on the first call.
		 * @return static
		 * @phpstan-return static
		 * @TODO: After Minimaum PHP Requrement is finalize from WP and WC We will change return type to static. PHP 7.4 Does not support return type static now.
		 */
		public static function instance( ...$args ): self {
			/**
			 * Static instances.
			 *
			 * @var array<string, static> $instances
			 */
			static $instances = array();

			if ( ! isset( $instances[ static::class ] ) ) {
				// @phpstan-ignore new.noConstructor, argument.type
				$instances[ static::class ] = new static( ...$args );
			}

			return $instances[ static::class ];
		}

		/**
		 * Return the singleton instance of the class that uses this trait.
		 *
		 * Always creates the using class itself (`new self()`), never a subclass.
		 * Use this only in final classes.
		 *
		 * Do not use in extendable classes: Child::self_instance() returns a Parent
		 * object, and on PHP 8.1+ parent and child share the same instance.
		 *
		 * Usage:
		 *     License::self_instance( 10 ); // License, created with 10
		 *     License::self_instance( 99 ); // same License, 99 is ignored
		 *
		 * @since 4.1.0
		 *
		 * @param mixed ...$args Constructor arguments. Used only on the first call.
		 * @return self
		 * @phpstan-return self
		 */
		public static function self_instance( ...$args ): self {
			/**
			 * Self Instance.
			 *
			 * @var self|null $instance
			 */
			static $instance = null;

			// @phpstan-ignore new.noConstructor, argument.type
			return $instance ??= new self( ...$args );
		}
	}
}
