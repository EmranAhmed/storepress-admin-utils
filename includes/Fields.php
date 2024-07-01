<?php
	/**
	 * Admin Settings Fields Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

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
		 * @var array<string, object>
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
		 * @param array<string, string|string[]> $fields Field list.
		 * @param Settings                       $settings Settings Class Instance.
		 */
		public function __construct( array $fields, Settings $settings ) {

			/**
			 * Fields
			 *
			 * @var array<string, string|string[]> $fields
			 */
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
				if ( $this->is_empty_string( $this->last_section_id ) ) {
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
		 * @param array<string, string> $field Single field.
		 *
		 * @return bool
		 */
		public function is_section( array $field ): bool {
			return 'section' === $field['type'];
		}

		/**
		 * Check is field or not.
		 *
		 * @param array<string, string> $field Field array.
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
		 * @param array<string, string> $field Field array.
		 *
		 * @return string
		 */
		public function get_field_id( array $field ): string {
			return $field['id'];
		}

		/**
		 * Get Sections.
		 *
		 * @return array<string, object>
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

			$allowed_input_html = $this->get_kses_allowed_input_html();
			/**
			 * Section Instance.
			 *
			 * @var Section $section
			 */
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
