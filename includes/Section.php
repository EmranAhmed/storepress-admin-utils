<?php

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	/**
	 * Admin Settings
	 *
	 * @package    StorePress/AdminUtils
	 * @name Section
	 * @version    1.0
	 */
if ( ! class_exists( '\StorePress\AdminUtils\Section' ) ) {
	class Section {

		/**
		 * @var array
		 */
		private array $section;

		/**
		 * @param array $section
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
		 * @return string
		 */
		public function get_id(): string {
			return $this->section['_id'];
		}

		/**
		 * @return string
		 */
		public function get_title(): string {
			return $this->section['title'] ?? '';
		}

		public function has_title(): string {
			return ! empty( $this->section['title'] );
		}

		/**
		 * @return string
		 */
		public function get_description(): string {
			return $this->section['description'] ?? '';
		}

		public function has_description(): string {
			return ! empty( $this->section['description'] );
		}

		/**
		 * @return array
		 */
		public function get_fields(): array {
			return $this->section['fields'];
		}

		/**
		 * @return bool
		 */
		public function has_fields(): bool {
			return ! empty( $this->section['fields'] );
		}

		/**
		 * @param Field $field
		 *
		 * @return self
		 */
		public function add_field( Field $field ): self {
			$this->section['fields'][] = $field;

			return $this;
		}

		/**
		 * @return string
		 */
		public function display(): string {

			$title       = $this->has_title() ? sprintf( '<h2 class="title">%s</h2>', $this->get_title() ) : '';
			$description = $this->has_description() ? sprintf( '<p class="section-description">%s</p>', $this->get_description() ) : '';

			return $title . $description;
		}

		/**
		 * @return string
		 */
		public function before_display_fields(): string {
			$table_class = array();

			$table_class[] = ( $this->has_title() || $this->has_description() ) ? 'has-section' : 'no-section';

			return sprintf( '<table class="form-table storepress-admin-form-table %s" role="presentation"><tbody>', implode( ' ', $table_class ) );
		}

		/**
		 * @return string
		 */
		public function after_display_fields(): string {
			return '</tbody></table>';
		}
	}
}
