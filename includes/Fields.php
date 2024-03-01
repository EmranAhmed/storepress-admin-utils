<?php

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	/**
	 * Admin Settings
	 *
	 * @package    StorePress/AdminUtils
	 * @name Fields
	 * @version    1.0
	 */

if ( ! class_exists( '\StorePress\AdminUtils\Fields' ) ) {
	class Fields {
		/**
		 * @var array
		 */
		private array $sections = array();
		/**
		 * @var string
		 */
		private string $last_section_id = '';

		/**
		 * @param array    $fields
		 * @param Settings $settings
		 */
		public function __construct( array $fields, Settings $settings ) {

			foreach ( $fields as $field ) {

				$_field     = ( new Field( $field ) )->add_settings( $settings );
				$section_id = $this->get_section_id();

				if ( $this->is_section( $field ) ) {

					$this->sections[ $section_id ] = new Section(
						array(
							'_id'         => $section_id,
							'title'       => $_field->get_attribute( 'title' ),
							'description' => $_field->get_attribute( 'description' ),
						)
					);
					$this->last_section_id         = $section_id;
				}

				// Generate section id when section not available on a tab.
				if ( empty( $this->last_section_id ) ) {
					$this->sections[ $section_id ] = new Section(
						array(
							'_id' => $section_id,
						)
					);
					$this->last_section_id         = $section_id;
				}

				if ( $this->is_field( $field ) ) {
					// $value = $this->get_saved_value( $_field );
					// $f     = $_field->add_value( $value )->add_settings_id( $this->get_settings()->get_settings_id() );
					$this->sections[ $this->last_section_id ]->add_field( $_field );
				}
			}
		}

		/**
		 * @param array $field
		 *
		 * @return bool
		 */
		public function is_section( array $field ): bool {
			return 'section' === $field['type'];
		}

		/**
		 * @param array $field
		 *
		 * @return bool
		 */
		public function is_field( array $field ): bool {
			return ! $this->is_section( $field );
		}

		/**
		 * @return string
		 */
		public function get_section_id(): string {
			return uniqid( 'section-' );
		}

		/**
		 * @param array $field
		 *
		 * @return mixed
		 */
		public function get_field_id( array $field ) {
			return $field['id'];
		}

		/**
		 * @return array
		 */
		public function get_sections(): array {
			return $this->sections;
		}

		/**
		 * @return void
		 */
		public function display() {
			/**
			 * @var Section $section
			 */
			foreach ( $this->get_sections() as $section ) {
				echo $section->display();

				if ( $section->has_fields() ) {

					echo $section->before_display_fields();
					/**
					 * @var Field $field
					 */
					foreach ( $section->get_fields() as $field ) {
						echo $field->display();
					}

					echo $section->after_display_fields();
				}
			}
		}
	}
}
