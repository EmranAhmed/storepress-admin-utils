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
	 */
	trait SingletonTrait {

		/**
		 * Instance holder.
		 *
		 * @var self|null
		 */
		protected static ?self $instance = null;

		/**
		 *  Return singleton instance of Class.
		 *  The instance will be created if it does not exist yet.
		 *
		 * @param mixed ...$args Constructor arguments (optional).
		 *
		 * @return self
		 * @phpstan-return self
		 *
		 * @since  1.11.0
		 */
		public static function instance( ...$args ): self {
			return self::$instance ??= new self( ...$args ); // @phpstan-ignore new.noConstructor
		}

		/**
		 * Reset instance (for testing).
		 *
		 * @return void
		 * @since  2.0.0
		 */
		public static function destroy(): void {
			self::$instance = null;
		}
	}
}
