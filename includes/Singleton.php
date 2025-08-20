<?php
	/**
	 * Common Instance for Classes.
	 *
	 * @package      StorePress/AdminUtils
	 * @since        0.0.1
	 * @version      0.0.1
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

trait Singleton {
	/**
	 *  Return singleton instance of Class.
	 *  The instance will be created if it does not exist yet.
	 *
	 * @param mixed ...$args Class params.
	 *
	 * @return self
	 * @since  0.0.1
	 */
	public static function instance( ...$args ): self {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self( ...$args );
		}

		return $instance;
	}
}
