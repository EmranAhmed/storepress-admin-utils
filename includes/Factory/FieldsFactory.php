<?php
/**
 * Fields Factory Class File.
 *
 * @package    StorePress/AdminUtils
 * @since      1.0.0
 * @version    1.0.0
 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Factory;

	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	use StorePress\AdminUtils\Services\Internal\Settings\Field;
	use StorePress\AdminUtils\Services\Internal\Settings\Section;
	use StorePress\AdminUtils\Traits\SingletonTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Factory\FieldsFactory' ) ) {
	/**
	 * Fields Factory Class.
	 *
	 * Creates settings Field and Section service instances for a given settings owner.
	 *
	 * @name FieldsFactory
	 *
	 * @since 1.0.0
	 */
	class FieldsFactory {

		use SingletonTrait;

		/**
		 * Create a settings field instance.
		 *
		 * @param AbstractSettings     $settings Settings owner instance.
		 * @param array<string, mixed> $field    Field configuration array.
		 * @param array<string, mixed> $values   Saved field values.
		 *
		 * @return Field
		 *
		 * @since 1.0.0
		 */
		public function create_field( AbstractSettings $settings, array $field, array $values = array() ): Field {

			return new Field( $settings, $field, $values );
		}

		/**
		 * Create a settings section instance.
		 *
		 * @param AbstractSettings     $settings Settings owner instance.
		 * @param array<string, mixed> $section  Section configuration array.
		 *
		 * @return Section
		 *
		 * @since 1.0.0
		 */
		public function create_section( AbstractSettings $settings, array $section ): Section {
			return new Section( $settings, $section );
		}
	}
}
