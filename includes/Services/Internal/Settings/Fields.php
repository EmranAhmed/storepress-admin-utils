<?php
	/**
	 * Admin Settings Fields Class File.
	 *
	 * This file contains the Fields class which manages collections of settings
	 * fields organized into sections. It handles field grouping, section creation,
	 * and coordinated display of all fields within a settings tab.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\Settings;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	use StorePress\AdminUtils\ServiceContainers\InternalServiceContainer;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Settings\Fields' ) ) {
	/**
	 * Admin Settings Fields Class.
	 *
	 * Manages a collection of settings fields organized into sections. This class
	 * processes field configuration arrays, automatically groups fields into sections,
	 * and provides coordinated rendering of all fields within a settings tab.
	 *
	 * @name Fields
	 *
	 * @phpstan-use CallerTrait<AbstractSettings>
	 *
	 * @method AbstractSettings get_caller() Returns the parent AbstractSettings instance.
	 *
	 * @since 1.0.0
	 *
	 * @see Field For individual field handling.
	 * @see Section For section management.
	 * @see AbstractSettings For the parent settings class.
	 *
	 * @example Basic usage:
	 *          ```php
	 *          $fields = new Fields( $settings, array(
	 *              array( 'type' => 'section', 'title' => 'General Settings' ),
	 *              array( 'id' => 'name', 'type' => 'text', 'title' => 'Name' ),
	 *              array( 'id' => 'email', 'type' => 'email', 'title' => 'Email' ),
	 *          ) );
	 *          $fields->display();
	 *          ```
	 *
	 * @example Multiple sections:
	 *          ```php
	 *          $fields = new Fields( $settings, array(
	 *              array( 'type' => 'section', 'title' => 'Basic Info' ),
	 *              array( 'id' => 'name', 'type' => 'text', 'title' => 'Name' ),
	 *              array( 'type' => 'section', 'title' => 'Advanced' ),
	 *              array( 'id' => 'api_key', 'type' => 'password', 'title' => 'API Key' ),
	 *          ) );
	 *          ```
	 *
	 * @example Fields without explicit section:
	 *          ```php
	 *          // Auto-generates a section for fields without explicit section
	 *          $fields = new Fields( $settings, array(
	 *              array( 'id' => 'option1', 'type' => 'text', 'title' => 'Option 1' ),
	 *              array( 'id' => 'option2', 'type' => 'text', 'title' => 'Option 2' ),
	 *          ) );
	 *          ```
	 */
	class Fields {

		use HelperMethodsTrait;
		use CallerTrait;

		// =====================================================================
		// Properties
		// =====================================================================

		/**
		 * Collection of section instances.
		 *
		 * Stores Section objects indexed by their unique section IDs.
		 * Each section contains its own collection of Field objects.
		 *
		 * @since 1.0.0
		 *
		 * @var array<string, Section>
		 */
		private array $sections = array();

		/**
		 * Last processed section ID.
		 *
		 * Tracks the current section being populated with fields.
		 * Used to assign fields to the correct section during processing.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private string $last_section_id = '';

		// =====================================================================
		// Constructor and Initialization
		// =====================================================================

		/**
		 * Construct Fields instance.
		 *
		 * Initializes the fields collection with the given settings context
		 * and processes the provided fields configuration array.
		 *
		 * @since 1.0.0
		 *
		 * @param AbstractSettings                 $settings Parent settings object that manages these fields.
		 * @param array<int, array<string, mixed>> $fields   Array of field configuration arrays.
		 *
		 * @see Fields::add() For field processing logic.
		 * @see Fields::init() For custom initialization.
		 *
		 * @example Basic construction:
		 *          ```php
		 *          $fields = new Fields( $settings, array(
		 *              array( 'id' => 'my_field', 'type' => 'text', 'title' => 'My Field' ),
		 *          ) );
		 *          ```
		 *
		 * @example With sections:
		 *          ```php
		 *          $fields = new Fields( $settings, array(
		 *              array( 'type' => 'section', 'title' => 'Settings Section' ),
		 *              array( 'id' => 'field1', 'type' => 'text', 'title' => 'Field 1' ),
		 *          ) );
		 *          ```
		 */
		public function __construct( AbstractSettings $settings, array $fields ) {
			$this->set_caller( $settings );
			$this->add( $fields );
			$this->init();
		}

		/**
		 * Initialize fields collection.
		 *
		 * Override this method in subclasses to add custom initialization logic
		 * after fields have been processed and added.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @example Override in subclass:
		 *          ```php
		 *          public function init(): void {
		 *              // Custom initialization logic
		 *              add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		 *          }
		 *          ```
		 */
		public function init(): void {}

		// =====================================================================
		// Container and Settings Access
		// =====================================================================

		/**
		 * Get the service container instance.
		 *
		 * Returns the InternalServiceContainer from the parent settings object.
		 * Used to resolve Field and Section service instances.
		 *
		 * @since 1.0.0
		 *
		 * @return InternalServiceContainer The service container instance.
		 *
		 * @see Fields::add() Uses container to create Field and Section instances.
		 *
		 * @example
		 *          ```php
		 *          $container = $this->get_container();
		 *          $field = $container->get( Field::class, $field_config );
		 *          ```
		 */
		public function get_container(): InternalServiceContainer {
			return $this->get_caller()->get_container();
		}

		/**
		 * Get the parent Settings object.
		 *
		 * Returns the AbstractSettings instance that this fields collection belongs to.
		 * Provides access to settings-level methods like get_options(), allowed_tags(), etc.
		 *
		 * @since 1.0.0
		 *
		 * @return AbstractSettings The parent settings object.
		 *
		 * @see AbstractSettings For available settings methods.
		 *
		 * @example
		 *          ```php
		 *          $settings = $this->get_settings();
		 *          $options = $settings->get_options();
		 *          $allowed_tags = $settings->allowed_tags();
		 *          ```
		 */
		public function get_settings(): AbstractSettings {
			return $this->get_caller();
		}

		// =====================================================================
		// Field Processing Methods
		// =====================================================================

		/**
		 * Add and process fields configuration.
		 *
		 * Processes an array of field configurations, creating Field and Section
		 * instances as needed. Fields are automatically grouped into sections.
		 * If no section is defined, an auto-generated section is created.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, array<string, mixed>> $fields Array of field configuration arrays.
		 *
		 * @return void
		 *
		 * @see Fields::is_section() For section type detection.
		 * @see Fields::is_field() For field type detection.
		 * @see Fields::get_section_id() For unique section ID generation.
		 *
		 * @example Adding fields with section:
		 *          ```php
		 *          $this->add( array(
		 *              array( 'type' => 'section', 'title' => 'My Section' ),
		 *              array( 'id' => 'field1', 'type' => 'text', 'title' => 'Field 1' ),
		 *              array( 'id' => 'field2', 'type' => 'checkbox', 'title' => 'Field 2' ),
		 *          ) );
		 *          ```
		 *
		 * @example Fields without explicit section (auto-generates section):
		 *          ```php
		 *          $this->add( array(
		 *              array( 'id' => 'field1', 'type' => 'text', 'title' => 'Field 1' ),
		 *          ) );
		 *          ```
		 */
		public function add( array $fields ): void {
			/**
			 * Fields configuration array.
			 *
			 * @var array<string, string|string[]> $field Individual field configuration.
			 */
			foreach ( $fields as $field ) {

				$_field = $this->get_container()->get( Field::class, $field );

				$section_id = $this->get_section_id();

				// Create section when field type is 'section'.
				if ( $this->is_section( $field ) ) {

					$_section = $this->get_container()->get(
						Section::class,
						array(
							'_id'         => $section_id,
							'title'       => $_field->get_attribute( 'title' ),
							'description' => $_field->get_attribute( 'description' ),
						)
					);

					$this->sections[ $section_id ] = $_section;
					$this->last_section_id         = $section_id;
				}

				// Generate section id when section not available on a tab.
				if ( $this->is_empty_string( $this->last_section_id ) ) {

					$_section = $this->get_container()->get(
						Section::class,
						array(
							'_id' => $section_id,
						)
					);

					$this->sections[ $section_id ] = $_section;
					$this->last_section_id         = $section_id;
				}

				// Add field to current section.
				if ( $this->is_field( $field ) ) {
					$this->sections[ $this->last_section_id ]->add_field( $_field );
				}
			}
		}

		// =====================================================================
		// Field Type Detection Methods
		// =====================================================================

		/**
		 * Check if field configuration is a section.
		 *
		 * Determines whether a field configuration array represents a section
		 * definition rather than a regular field.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 *
		 * @return bool True if the field type is 'section', false otherwise.
		 *
		 * @see Fields::is_field() For checking regular fields.
		 *
		 * @example
		 *          ```php
		 *          $field = array( 'type' => 'section', 'title' => 'My Section' );
		 *          if ( $this->is_section( $field ) ) {
		 *              // Create new section
		 *          }
		 *          ```
		 */
		public function is_section( array $field ): bool {
			return 'section' === $field['type'];
		}

		/**
		 * Check if field configuration is a regular field.
		 *
		 * Determines whether a field configuration array represents a regular
		 * form field rather than a section definition.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 *
		 * @return bool True if not a section, false if it is a section.
		 *
		 * @see Fields::is_section() For checking sections.
		 *
		 * @example
		 *          ```php
		 *          $field = array( 'id' => 'name', 'type' => 'text', 'title' => 'Name' );
		 *          if ( $this->is_field( $field ) ) {
		 *              // Add to current section
		 *          }
		 *          ```
		 */
		public function is_field( array $field ): bool {
			return ! $this->is_section( $field );
		}

		// =====================================================================
		// Identification Methods
		// =====================================================================

		/**
		 * Generate unique section ID.
		 *
		 * Creates a unique prefixed ID for a new section using WordPress's
		 * wp_unique_prefixed_id() function.
		 *
		 * @since 1.0.0
		 *
		 * @return string Unique section ID in format 'section-{n}'.
		 *
		 * @see Fields::add() Uses this for section creation.
		 *
		 * @example
		 *          ```php
		 *          $section_id = $this->get_section_id();
		 *          // Returns: 'section-1', 'section-2', etc.
		 *          ```
		 */
		public function get_section_id(): string {
			return wp_unique_prefixed_id( 'section-' );
		}

		/**
		 * Get field ID from configuration array.
		 *
		 * Extracts the 'id' value from a field configuration array.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $field Field configuration array.
		 *
		 * @return string The field ID.
		 *
		 * @example
		 *          ```php
		 *          $field = array( 'id' => 'my_field', 'type' => 'text' );
		 *          $id = $this->get_field_id( $field );
		 *          // Returns: 'my_field'
		 *          ```
		 */
		public function get_field_id( array $field ): string {
			return $field['id'];
		}

		// =====================================================================
		// Section Retrieval Methods
		// =====================================================================

		/**
		 * Get all sections.
		 *
		 * Returns the collection of Section instances indexed by their IDs.
		 * Each section contains its own collection of Field objects.
		 *
		 * @since 1.0.0
		 *
		 * @return array<string, Section> Array of Section objects keyed by section ID.
		 *
		 * @see Fields::display() Uses this to iterate sections.
		 *
		 * @example
		 *          ```php
		 *          $sections = $this->get_sections();
		 *          foreach ( $sections as $section_id => $section ) {
		 *              echo $section->display();
		 *          }
		 *          ```
		 */
		public function get_sections(): array {
			return $this->sections;
		}

		// =====================================================================
		// Display Methods
		// =====================================================================

		/**
		 * Display all fields organized by sections.
		 *
		 * Renders all sections and their contained fields with proper HTML escaping.
		 * Uses wp_kses() with allowed tags from the parent settings object.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @see Fields::get_sections() For section retrieval.
		 * @see Section::display() For section rendering.
		 * @see Section::has_fields() For checking if section has fields.
		 * @see Field::display() For field rendering.
		 *
		 * @example
		 *          ```php
		 *          $fields = new Fields( $settings, $field_configs );
		 *          $fields->display();
		 *          ```
		 */
		public function display(): void {

			$allowed_input_html = $this->get_kses_allowed_input_html( $this->get_settings()->allowed_tags() );
			$allowed_html       = $this->get_kses_allowed_html( $this->get_settings()->allowed_tags() );

			/**
			 * Section instance for iteration.
			 *
			 * @var Section $section The current section being displayed.
			 */
			foreach ( $this->get_sections() as $section ) {
				echo wp_kses( $section->display(), $allowed_html );

				if ( $section->has_fields() ) {

					echo wp_kses( $section->before_display_fields(), $allowed_html );

					/**
					 * Field instance for iteration.
					 *
					 * @var Field $field The current field being displayed.
					 */
					foreach ( $section->get_fields() as $field ) {
						echo wp_kses( $field->display(), $allowed_input_html );
					}

					echo wp_kses( $section->after_display_fields(), $allowed_html );
				}
			}
		}
	}
}
