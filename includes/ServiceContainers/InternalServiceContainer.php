<?php
	/**
	 * Simple DI Container File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceContainers;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Traits\SingletonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\ServiceContainers\InternalServiceContainer' ) ) {

	/**
	 * DI Container for Internal package usages.
	 *
	 * @name InternalServiceContainer
	 */
	class InternalServiceContainer extends BaseServiceContainer {
		use SingletonTrait;
	}
}
