<?php
	/**
	 * Admin Settings Fields Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Fields' ) ) {
	/**
	 * Admin Settings Fields Class.
	 *
	 * @name Fields
	 */
	class Fields {

		use Common;

		/**
		 * Sections.
		 *
		 * @var array
		 */
		private array $sections = array();
		/**
		 * Last section ID.
		 *
		 * @var string
		 */
		private string $last_section_id = '';

		/**
		 * Class Construct.
		 *
		 * @param array    $fields Field list.
		 * @param Settings $settings Settings Class Instance.
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
					$this->sections[ $this->last_section_id ]->add_field( $_field );
				}
			}
		}

		/**
		 * Check is section or not.
		 *
		 * @param array $field Single field.
		 *
		 * @return bool
		 */
		public function is_section( array $field ): bool {
			return 'section' === $field['type'];
		}

		/**
		 * Check is field or not.
		 *
		 * @param array $field Field array.
		 *
		 * @return bool
		 */
		public function is_field( array $field ): bool {
			return ! $this->is_section( $field );
		}

		/**
		 * Get section id.
		 *
		 * @return string
		 */
		public function get_section_id(): string {
			return uniqid( 'section-' );
		}

		/**
		 * Get Field ID.
		 *
		 * @param array $field Field array.
		 *
		 * @return mixed
		 */
		public function get_field_id( array $field ) {
			return $field['id'];
		}

		/**
		 * Get Sections.
		 *
		 * @return array
		 */
		public function get_sections(): array {
			return $this->sections;
		}

		/**
		 * Display fields with section wrapped.
		 *
		 * @return void
		 */
		public function display() {
			/**
			 * Section Instance.
			 *
			 * @var Section $section
			 */
			$allowed_input_html = $this->get_kses_allowed_input_html();
			foreach ( $this->get_sections() as $section ) {
				echo wp_kses_post( $section->display() );

				if ( $section->has_fields() ) {

					echo wp_kses_post( $section->before_display_fields() );
					/**
					 * Field Instance.
					 *
					 * @var Field $field
					 */
					foreach ( $section->get_fields() as $field ) {
						echo wp_kses( $field->display(), $allowed_input_html );
					}

					echo wp_kses_post( $section->after_display_fields() );
				}
			}
		}
	}
}
