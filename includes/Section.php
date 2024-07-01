<?php
	/**
	 * Admin Settings Section Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Section' ) ) {
	/**
	 * Admin Settings Section Class.
	 *
	 * @name Section
	 */
	class Section {

		use Common;

		/**
		 * Section data.
		 *
		 * @var array<string, mixed>
		 */
		private array $section;

		/**
		 * Construct section from array.
		 *
		 * @param array<string, mixed> $section Section array.
		 */
		public function __construct( array $section ) {
			$this->section = wp_parse_args(
				$section,
				array(
					'_id'         => uniqid( 'section-' ),
					'title'       => '',
					'description' => '',
					'fields'      => array(),
				)
			);
		}

		/**
		 * Get section ID.
		 *
		 * @return string
		 */
		public function get_id(): string {
			return $this->section['_id'];
		}

		/**
		 * Get Section title.
		 *
		 * @return string
		 */
		public function get_title(): string {
			return $this->section['title'] ?? '';
		}

		/**
		 * Check section title.
		 *
		 * @return bool
		 */
		public function has_title(): bool {
			return ! $this->is_empty_string( $this->section['title'] );
		}

		/**
		 * Get section description.
		 *
		 * @return string
		 */
		public function get_description(): string {
			return $this->section['description'] ?? '';
		}

		/**
		 * Check section has description defined.
		 *
		 * @return bool
		 */
		public function has_description(): bool {
			return ! $this->is_empty_string( $this->section['description'] );
		}

		/**
		 * Get fields array from section.
		 *
		 * @return array<string, mixed>
		 */
		public function get_fields(): array {
			return $this->section['fields'];
		}

		/**
		 * Check fields available on section.
		 *
		 * @return bool
		 */
		public function has_fields(): bool {
			return ! $this->is_empty_array( $this->section['fields'] );
		}

		/**
		 * Add field to section.
		 *
		 * @param Field $field Field object.
		 *
		 * @return self
		 */
		public function add_field( Field $field ): self {
			$this->section['fields'][] = $field;

			return $this;
		}

		/**
		 * Return section display.
		 *
		 * @return string
		 */
		public function display(): string {

			$title       = $this->has_title() ? sprintf( '<h2 class="title">%s</h2>', $this->get_title() ) : '';
			$description = $this->has_description() ? sprintf( '<p class="section-description">%s</p>', $this->get_description() ) : '';

			return $title . $description;
		}

		/**
		 * Markup before display section fields.
		 *
		 * @return string
		 */
		public function before_display_fields(): string {
			$table_class = array();

			$table_class[] = ( $this->has_title() || $this->has_description() ) ? 'has-section' : 'no-section';

			return sprintf( '<table class="form-table storepress-admin-form-table %s" role="presentation"><tbody>', implode( ' ', $table_class ) );
		}

		/**
		 * Markup after display section fields.
		 *
		 * @return string
		 */
		public function after_display_fields(): string {
			return '</tbody></table>';
		}
	}
}
