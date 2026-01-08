<?php
	/**
	 * Singleton trait.
	 *
	 * @package      StorePress/AdminUtils
	 * @since        1.11.1
	 * @version      1.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

trait Singleton {

	/**
	 * Instance holder.
	 *
	 * @var self|null
	 */
	private static $instance;

	/**
	 *  Return singleton instance of Class.
	 *  The instance will be created if it does not exist yet.
	 *
	 * @param mixed ...$args Class params.
	 *
	 * @return self
	 * @since  1.11.0
	 */
	public static function instance( ...$args ): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( ...$args );
		}

		return self::$instance;
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
