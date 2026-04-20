<?php
/**
 * Dependency Not Available Exception Class File.
 *
 * @package    StorePress/AdminUtils
 * @since      1.0.0
 * @version    1.0.0
 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceContainers;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use Psr\Container\NotFoundExceptionInterface;
	use RuntimeException;

if ( ! class_exists( '\StorePress\AdminUtils\ServiceContainers\DependencyNotAvailableException' ) ) {
	/**
	 * Dependency Not Available Exception.
	 *
	 * Thrown when a requested dependency ID is not registered in the service container.
	 *
	 * @name DependencyNotAvailableException
	 *
	 * @since 1.0.0
	 */
	class DependencyNotAvailableException extends RuntimeException implements NotFoundExceptionInterface {

		/**
		 * Constructor.
		 *
		 * @param string $id The dependency class name or identifier that was not found.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $id ) {

			$message = sprintf( 'Dependency Class "%s" not available / registered.' . "\n\n", $id );

			parent::__construct( $message );
		}
	}
}
