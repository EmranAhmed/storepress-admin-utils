<?php
	/**
	 * Admin Settings Section Class File.
	 *
	 * This file contains the Section class which handles grouping of settings
	 * fields into logical sections with optional titles and descriptions.
	 * Sections provide visual organization for settings pages.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\Settings;

	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	use StorePress\AdminUtils\ServiceContainers\InternalServiceContainer;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Settings\Section' ) ) {
	/**
	 * Admin Settings Section Class.
	 *
	 * Manages a section within a settings page. Sections group related fields
	 * together with an optional title and description. Each section renders
	 * its fields within a form table structure.
	 *
	 * @name Section
	 *
	 * @phpstan-use CallerTrait<AbstractSettings>
	 *
	 * @method AbstractSettings get_caller() Returns the parent AbstractSettings instance.
	 *
	 * @since 1.0.0
	 *
	 * @see Field For individual field handling within sections.
	 * @see Fields For the collection manager that creates sections.
	 * @see AbstractSettings For the parent settings class.
	 *
	 * @example Basic section with title and description:
	 *          ```php
	 *          $section = new Section( $settings, array(
	 *              '_id'         => 'general-section',
	 *              'title'       => 'General Settings',
	 *              'description' => 'Configure basic options.',
	 *          ) );
	 *          echo $section->display();
	 *          ```
	 *
	 * @example Section without title (hidden section):
	 *          ```php
	 *          $section = new Section( $settings, array(
	 *              '_id' => 'hidden-section',
	 *          ) );
	 *          ```
	 *
	 * @example Adding fields to a section:
	 *          ```php
	 *          $section = new Section( $settings, array(
	 *              '_id'   => 'my-section',
	 *              'title' => 'My Section',
	 *          ) );
	 *          $section->add_field( $field1 );
	 *          $section->add_field( $field2 );
	 *          ```
	 */
	class Section {

		use HelperMethodsTrait;
		use CallerTrait;

		// =====================================================================
		// Properties
		// =====================================================================

		/**
		 * Section configuration data.
		 *
		 * Contains section attributes including _id, title, description, and fields array.
		 *
		 * @since 1.0.0
		 *
		 * @var array<string, mixed>
		 */
		private array $section;

		// =====================================================================
		// Constructor and Initialization
		// =====================================================================

		/**
		 * Construct Section instance.
		 *
		 * Initializes a new Section object with the given settings context
		 * and section configuration array.
		 *
		 * @since 1.0.0
		 *
		 * @param AbstractSettings     $settings Parent settings object that manages this section.
		 * @param array<string, mixed> $section  Section configuration array containing _id, title, description, etc.
		 *
		 * @see Section::add() For configuration processing.
		 * @see Section::init() For custom initialization.
		 *
		 * @example Basic construction:
		 *          ```php
		 *          $section = new Section( $settings, array(
		 *              '_id'   => 'my-section',
		 *              'title' => 'My Section Title',
		 *          ) );
		 *          ```
		 *
		 * @example With description:
		 *          ```php
		 *          $section = new Section( $settings, array(
		 *              '_id'         => 'advanced-section',
		 *              'title'       => 'Advanced Settings',
		 *              'description' => 'These settings require careful configuration.',
		 *          ) );
		 *          ```
		 */
		public function __construct( AbstractSettings $settings, array $section ) {
			$this->set_caller( $settings );
			$this->add( $section );
			$this->init();
		}

		/**
		 * Initialize section.
		 *
		 * Override this method in subclasses to add custom initialization logic
		 * after the section has been configured.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @example Override in subclass:
		 *          ```php
		 *          public function init(): void {
		 *              // Custom initialization logic
		 *          }
		 *          ```
		 */
		public function init(): void {}

		/**
		 * Add and configure section data.
		 *
		 * Processes the section configuration array, merging with defaults.
		 * Generates a unique section ID if not provided.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $section Section configuration array.
		 *
		 * @return self Returns the Section instance for method chaining.
		 *
		 * @see Section::get_id() For retrieving the section ID.
		 * @see Section::get_title() For retrieving the title.
		 * @see Section::get_description() For retrieving the description.
		 *
		 * @example
		 *          ```php
		 *          $section->add( array(
		 *              '_id'         => 'custom-section',
		 *              'title'       => 'Custom Section',
		 *              'description' => 'Section description text.',
		 *          ) );
		 *          ```
		 */
		public function add( array $section ): self {
			$this->section = wp_parse_args(
				$section,
				array(
					'_id'         => wp_unique_prefixed_id( 'section-' ),
					'title'       => '',
					'description' => '',
					'fields'      => array(),
				)
			);
			return $this;
		}

		// =====================================================================
		// Container Access Methods
		// =====================================================================

		/**
		 * Get the service container instance.
		 *
		 * Returns the InternalServiceContainer from the parent settings object.
		 * Used to resolve service instances when needed.
		 *
		 * @since 1.0.0
		 *
		 * @return InternalServiceContainer The service container instance.
		 *
		 * @example
		 *          ```php
		 *          $container = $section->get_container();
		 *          $field = $container->get( Field::class, $field_config );
		 *          ```
		 */
		public function get_container(): InternalServiceContainer {
			return $this->get_caller()->get_container();
		}

		// =====================================================================
		// Section Identification Methods
		// =====================================================================

		/**
		 * Get section ID.
		 *
		 * Returns the unique identifier for this section. Auto-generated
		 * if not explicitly provided in the configuration.
		 *
		 * @since 1.0.0
		 *
		 * @return string The section ID.
		 *
		 * @example
		 *          ```php
		 *          $id = $section->get_id();
		 *          // Returns: 'section-1' or custom ID like 'general-settings'
		 *          ```
		 */
		public function get_id(): string {
			return $this->section['_id'];
		}

		// =====================================================================
		// Title Methods
		// =====================================================================

		/**
		 * Get section title.
		 *
		 * Returns the human-readable title displayed as the section heading.
		 *
		 * @since 1.0.0
		 *
		 * @return string The section title or empty string if not set.
		 *
		 * @see Section::has_title() For checking if title exists.
		 *
		 * @example
		 *          ```php
		 *          $title = $section->get_title();
		 *          // Returns: 'General Settings'
		 *          ```
		 */
		public function get_title(): string {
			return $this->section['title'] ?? '';
		}

		/**
		 * Check if section has a title.
		 *
		 * Determines whether the section has a non-empty title defined.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if title is defined and not empty, false otherwise.
		 *
		 * @see Section::get_title() For retrieving the title.
		 *
		 * @example
		 *          ```php
		 *          if ( $section->has_title() ) {
		 *              echo '<h2>' . $section->get_title() . '</h2>';
		 *          }
		 *          ```
		 */
		public function has_title(): bool {
			return ! $this->is_empty_string( $this->section['title'] );
		}

		// =====================================================================
		// Description Methods
		// =====================================================================

		/**
		 * Get section description.
		 *
		 * Returns the descriptive text displayed below the section title.
		 *
		 * @since 1.0.0
		 *
		 * @return string The section description or empty string if not set.
		 *
		 * @see Section::has_description() For checking if description exists.
		 *
		 * @example
		 *          ```php
		 *          $description = $section->get_description();
		 *          // Returns: 'Configure basic options for the plugin.'
		 *          ```
		 */
		public function get_description(): string {
			return $this->section['description'] ?? '';
		}

		/**
		 * Check if section has a description.
		 *
		 * Determines whether the section has a non-empty description defined.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if description is defined and not empty, false otherwise.
		 *
		 * @see Section::get_description() For retrieving the description.
		 *
		 * @example
		 *          ```php
		 *          if ( $section->has_description() ) {
		 *              echo '<p>' . $section->get_description() . '</p>';
		 *          }
		 *          ```
		 */
		public function has_description(): bool {
			return ! $this->is_empty_string( $this->section['description'] );
		}

		// =====================================================================
		// Field Management Methods
		// =====================================================================

		/**
		 * Get all fields in this section.
		 *
		 * Returns the array of Field objects contained within this section.
		 *
		 * @since 1.0.0
		 *
		 * @return array<int, Field> Array of Field objects.
		 *
		 * @see Section::has_fields() For checking if fields exist.
		 * @see Section::add_field() For adding fields.
		 *
		 * @example
		 *          ```php
		 *          foreach ( $section->get_fields() as $field ) {
		 *              echo $field->display();
		 *          }
		 *          ```
		 */
		public function get_fields(): array {
			return $this->section['fields'];
		}

		/**
		 * Check if section has fields.
		 *
		 * Determines whether the section contains any Field objects.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if section has one or more fields, false otherwise.
		 *
		 * @see Section::get_fields() For retrieving fields.
		 *
		 * @example
		 *          ```php
		 *          if ( $section->has_fields() ) {
		 *              echo $section->before_display_fields();
		 *              foreach ( $section->get_fields() as $field ) {
		 *                  echo $field->display();
		 *              }
		 *              echo $section->after_display_fields();
		 *          }
		 *          ```
		 */
		public function has_fields(): bool {
			return ! $this->is_empty_array( $this->section['fields'] );
		}

		/**
		 * Add a field to this section.
		 *
		 * Appends a Field object to the section's fields collection.
		 *
		 * @since 1.0.0
		 *
		 * @param Field $field Field object to add.
		 *
		 * @return self Returns the Section instance for method chaining.
		 *
		 * @see Section::get_fields() For retrieving fields.
		 * @see Section::has_fields() For checking if fields exist.
		 *
		 * @example
		 *          ```php
		 *          $section->add_field( $text_field )
		 *                  ->add_field( $checkbox_field )
		 *                  ->add_field( $select_field );
		 *          ```
		 */
		public function add_field( Field $field ): self {
			$this->section['fields'][] = $field;

			return $this;
		}

		// =====================================================================
		// Display Methods
		// =====================================================================

		/**
		 * Display section header markup.
		 *
		 * Renders the section title and description HTML. Returns empty
		 * string if neither title nor description is defined.
		 *
		 * @since 1.0.0
		 *
		 * @return string HTML markup for section header.
		 *
		 * @see Section::has_title() For title check.
		 * @see Section::has_description() For description check.
		 *
		 * @example
		 *          ```php
		 *          echo $section->display();
		 *          // Output: '<h2 class="title">Section Title</h2><p class="section-description">Description</p>'
		 *          ```
		 */
		public function display(): string {

			$title       = $this->has_title() ? sprintf( '<h2 class="title">%s</h2>', $this->get_title() ) : '';
			$description = $this->has_description() ? sprintf( '<p class="section-description">%s</p>', $this->get_description() ) : '';

			return $title . $description;
		}

		/**
		 * Get markup before displaying section fields.
		 *
		 * Returns the opening HTML for the form table that wraps section fields.
		 * Includes CSS class indicating whether section has title/description.
		 *
		 * @since 1.0.0
		 *
		 * @return string Opening table HTML markup.
		 *
		 * @see Section::after_display_fields() For closing markup.
		 * @see Section::display() For section header.
		 *
		 * @example
		 *          ```php
		 *          echo $section->display();
		 *          echo $section->before_display_fields();
		 *          foreach ( $section->get_fields() as $field ) {
		 *              echo $field->display();
		 *          }
		 *          echo $section->after_display_fields();
		 *          ```
		 */
		public function before_display_fields(): string {
			$table_class = array();

			$table_class[] = ( $this->has_title() || $this->has_description() ) ? 'has-section' : 'no-section';

			return sprintf( '<table class="form-table storepress-admin-form-table %s" role="presentation"><tbody>', implode( ' ', $table_class ) );
		}

		/**
		 * Get markup after displaying section fields.
		 *
		 * Returns the closing HTML for the form table that wraps section fields.
		 *
		 * @since 1.0.0
		 *
		 * @return string Closing table HTML markup.
		 *
		 * @see Section::before_display_fields() For opening markup.
		 *
		 * @example
		 *          ```php
		 *          echo $section->before_display_fields();
		 *          // ... render fields ...
		 *          echo $section->after_display_fields();
		 *          // Output: '</tbody></table>'
		 *          ```
		 */
		public function after_display_fields(): string {
			return '</tbody></table>';
		}
	}
}
