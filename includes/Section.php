<?php
	
	namespace StorePress\AdminUtils;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	/**
	 * Admin Settings
	 *
	 * @package    StorePress
	 * @subpackage AdminUtils
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
				$this->section = wp_parse_args( $section, array(
					'_id'         => uniqid( 'section-' ),
					'title'       => '',
					'description' => '',
					'fields'      => array()
				) );
			}
			
			/**
			 * @return string
			 */
			public function get_id(): string {
				return $this->section[ '_id' ];
			}
			
			/**
			 * @return string
			 */
			public function get_title(): string {
				return $this->section[ 'title' ];
			}
			
			/**
			 * @return string
			 */
			public function get_description(): string {
				return $this->section[ 'description' ] ?? '';
			}
			
			/**
			 * @return array
			 */
			public function get_fields(): array {
				return $this->section[ 'fields' ];
			}
			
			/**
			 * @return bool
			 */
			public function has_fields(): bool {
				return ! empty( $this->section[ 'fields' ] );
			}
			
			/**
			 * @param Field $field
			 *
			 * @return self
			 */
			public function add_field( Field $field ): self {
				$this->section[ 'fields' ][] = $field;
				
				return $this;
			}
			
			/**
			 * @return string
			 */
			public function display(): string {
				return sprintf( '<h2 class="title">%s</h2><p>%s</p>', $this->get_title(), $this->get_description() );
			}
			
			/**
			 * @return string
			 */
			public function before_display_fields(): string {
				return '<table class="form-table storepress-admin-form-table" role="presentation"><tbody>';
			}
			
			/**
			 * @return string
			 */
			public function after_display_fields(): string {
				return '</tbody></table>';
			}
		}
	}