<?php
	/**
	 * Admin Settings Field Class File.
	 *
	 * This file contains the Field class which handles individual settings field
	 * rendering, validation, sanitization, and value management for the WordPress
	 * admin settings framework.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\Settings;

	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Settings\Field' ) ) {
	/**
	 * Admin Settings Field Class.
	 *
	 * Handles individual settings field operations including rendering HTML markup,
	 * managing field values, sanitization callbacks, escape callbacks, and REST API
	 * integration. Supports various field types like text, textarea, select, checkbox,
	 * radio, toggle, group, and custom fields.
	 *
	 * @name Field
	 *
	 * @phpstan-use CallerTrait<AbstractSettings>
	 *
	 * @method AbstractSettings get_caller() Returns the parent AbstractSettings instance.
	 *
	 * @since   1.0.0
	 *
	 * @example Basic usage:
	 *          ```php
	 *          $field = new Field( $settings, array(
	 *              'id'      => 'my_field',
	 *              'type'    => 'text',
	 *              'title'   => 'My Field',
	 *              'default' => 'default value',
	 *          ) );
	 *          echo $field->display();
	 *          ```
	 *
	 * @example With options for select/radio:
	 *          ```php
	 *          $field = new Field( $settings, array(
	 *              'id'      => 'my_select',
	 *              'type'    => 'select',
	 *              'title'   => 'Choose Option',
	 *              'options' => array( 'opt1' => 'Option 1', 'opt2' => 'Option 2' ),
	 *          ) );
	 *          ```
	 *
	 * @example Group field with nested fields:
	 *          ```php
	 *          $field = new Field( $settings, array(
	 *              'id'     => 'my_group',
	 *              'type'   => 'group',
	 *              'title'  => 'Settings Group',
	 *              'fields' => array(
	 *                  array( 'id' => 'sub_field', 'type' => 'text', 'title' => 'Sub Field' ),
	 *              ),
	 *          ) );
	 *          ```
	 */
	class Field {

		use HelperMethodsTrait;
		use CallerTrait;

		// =====================================================================
		// Properties
		// =====================================================================

		/**
		 * Single field configuration array.
		 *
		 * Contains all field attributes like id, type, title, default, options, etc.
		 *
		 * @since 1.0.0
		 *
		 * @var string[]|array<string, mixed>
		 */
		private array $field;

		/**
		 * Setting ID for form name generation.
		 *
		 * Used to generate field names in format: settings_id[field_id].
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private string $settings_id;

		// =====================================================================
		// Constructor and Initialization
		// =====================================================================

		/**
		 * Construct Field instance.
		 *
		 * Initializes a new Field object with the given settings context and field configuration.
		 * Optionally accepts preloaded values to populate the field.
		 *
		 * @since 1.0.0
		 *
		 * @param AbstractSettings     $settings Parent settings object that manages this field.
		 * @param array<string, mixed> $field    Field configuration array containing id, type, title, etc.
		 * @param array<string, mixed> $values   Optional. Preloaded values for the field. Default empty array.
		 *
		 * @example Basic construction:
		 *          ```php
		 *          $field = new Field( $settings, array(
		 *              'id'    => 'my_field',
		 *              'type'  => 'text',
		 *              'title' => 'My Field',
		 *          ) );
		 *          ```
		 *
		 * @example With preloaded values:
		 *          ```php
		 *          $field = new Field( $settings, $field_config, array( 'my_field' => 'saved value' ) );
		 *          ```
		 */
		public function __construct( AbstractSettings $settings, array $field, array $values = array() ) {
			$this->set_caller( $settings );
			$this->add( $field, $values );
			$this->init();
		}


		/**
		 * Initialize settings.
		 *
		 * Override this method to add custom initialization logic.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function init(): void {}

		/**
		 * Add and configure field.
		 *
		 * Processes the field configuration array, populates values from database or
		 * provided values array, and sets up REST API visibility.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $field  Field configuration array.
		 * @param array<string, mixed> $values Optional. Values to populate. Default empty array.
		 *
		 * @return self Returns the Field instance for method chaining.
		 *
		 * @see Field::populate_option_values() For database value population.
		 * @see Field::populate_from_values() For provided value population.
		 *
		 * @example
		 *          ```php
		 *          $field->add( array(
		 *              'id'           => 'email_field',
		 *              'type'         => 'email',
		 *              'title'        => 'Email Address',
		 *              'show_in_rest' => true,
		 *          ) );
		 *          ```
		 */
		public function add( array $field, array $values = array() ): self {

			$this->field = $field;

			if ( $this->is_empty_array( $values ) ) {
				$this->populate_option_values();
			} else {
				$this->populate_from_values( $values );
			}

			$this->field['show_in_rest'] = $this->get_attribute( 'show_in_rest', true );

			return $this;
		}

		// =====================================================================
		// Value Population Methods
		// =====================================================================

		/**
		 * Populate field value from WordPress options.
		 *
		 * Retrieves the field value from the database. For private fields, uses
		 * get_option() directly. For regular fields, extracts value from the
		 * settings array.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @see Field::is_private() For checking private field status.
		 * @see Field::get_private_name() For private option name generation.
		 */
		private function populate_option_values(): void {

			if ( $this->is_private() ) {
				$id    = $this->get_private_name();
				$value = get_option( $id );
			} else {
				$id     = $this->get_id();
				$values = $this->get_settings()->get_options();
				$value  = $values[ $id ] ?? null;
			}

			$this->add_value( $value );
		}

		/**
		 * Populate field value from provided values array.
		 *
		 * Used when values are passed directly (e.g., in group fields) rather
		 * than loading from the database.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $values Key-value pairs of field IDs to values.
		 *
		 * @return void
		 *
		 * @see Field::add() Called from add() when values are provided.
		 */
		private function populate_from_values( array $values ): void {

			$id    = $this->get_id();
			$value = $values[ $id ] ?? null;
			$this->add_value( $value );
		}

		// =====================================================================
		// Settings Context Methods
		// =====================================================================

		/**
		 * Get the parent Settings object.
		 *
		 * Returns the AbstractSettings instance that this field belongs to.
		 * Provides access to settings-level methods like get_options(), get_field_id(), etc.
		 *
		 * @since 1.0.0
		 *
		 * @return AbstractSettings The parent settings object.
		 *
		 * @example
		 *          ```php
		 *          $settings = $field->get_settings();
		 *          $all_options = $settings->get_options();
		 *          ```
		 */
		public function get_settings(): AbstractSettings {
			return $this->get_caller();
		}

		/**
		 * Get settings ID for form name generation.
		 *
		 * Returns the settings ID used to generate form field names.
		 * Falls back to the parent settings ID if not explicitly set.
		 *
		 * @since 1.0.0
		 *
		 * @return string The settings ID string.
		 *
		 * @see Field::add_settings_id() For setting custom settings ID.
		 * @see Field::get_name() For full field name generation.
		 *
		 * @example
		 *          ```php
		 *          $settings_id = $field->get_settings_id();
		 *          // Returns: 'my_plugin_settings'
		 *          ```
		 */
		public function get_settings_id(): string {
			return $this->settings_id ?? $this->get_settings()->get_settings_id();
		}

		/**
		 * Set custom settings ID for field name generation.
		 *
		 * Overrides the default settings ID, useful for group fields where
		 * the name format differs from top-level fields.
		 *
		 * @since 1.0.0
		 *
		 * @param string $settings_id Custom settings ID string.
		 *
		 * @return self Returns the Field instance for method chaining.
		 *
		 * @see Field::get_settings_id() For retrieving the settings ID.
		 *
		 * @example
		 *          ```php
		 *          $field->add_settings_id( 'my_plugin[group_field]' );
		 *          ```
		 */
		public function add_settings_id( string $settings_id = '' ): self {
			$this->settings_id = $settings_id;

			return $this;
		}

		// =====================================================================
		// Value Methods
		// =====================================================================

		/**
		 * Add/set the field value.
		 *
		 * Sets the current value for this field. Accepts various types including
		 * strings, arrays (for multi-select/checkbox), numbers, and booleans.
		 *
		 * @since 1.0.0
		 *
		 * @param string|string[]|numeric|bool|null $value The value to set.
		 *
		 * @return self Returns the Field instance for method chaining.
		 *
		 * @see Field::get_value() For retrieving the value.
		 *
		 * @example
		 *          ```php
		 *          $field->add_value( 'my custom value' );
		 *          $field->add_value( array( 'option1', 'option2' ) ); // For multi-select
		 *          ```
		 */
		public function add_value( $value ): self {
			$this->field['value'] = $value;

			return $this;
		}

		/**
		 * Get the field's current value.
		 *
		 * Returns the field value, falling back to the provided default or
		 * the field's configured default value if no value is set.
		 *
		 * @since 1.0.0
		 *
		 * @param bool|string|string[]|null $default_value Optional. Default value if none set.
		 *
		 * @return bool|string|string[]|null The field value.
		 *
		 * @see Field::add_value() For setting the value.
		 * @see Field::get_default_value() For the configured default.
		 *
		 * @example
		 *          ```php
		 *          $value = $field->get_value();
		 *          $value = $field->get_value( 'fallback' ); // With custom fallback
		 *          ```
		 */
		public function get_value( $default_value = null ) {
			return $this->get_attribute( 'value', $default_value ?? $this->get_default_value() );
		}

		/**
		 * Get the field's configured default value.
		 *
		 * Returns the default value specified in the field configuration array.
		 *
		 * @since 1.0.0
		 *
		 * @return bool|string|numeric|array<int|string, mixed>|null The default value or null.
		 *
		 * @see Field::get_value() Uses this as fallback.
		 *
		 * @example
		 *          ```php
		 *          $default = $field->get_default_value();
		 *          ```
		 */
		public function get_default_value() {
			return $this->get_attribute( 'default' );
		}

		// =====================================================================
		// Field Identification Methods
		// =====================================================================

		/**
		 * Get the field ID.
		 *
		 * Returns the unique identifier for this field, used in form names,
		 * database storage, and HTML element IDs.
		 *
		 * @since 1.0.0
		 *
		 * @return string|null The field ID or null if not set.
		 *
		 * @example
		 *          ```php
		 *          $id = $field->get_id();
		 *          // Returns: 'my_field_id'
		 *          ```
		 */
		public function get_id(): ?string {
			return $this->get_attribute( 'id' );
		}

		/**
		 * Get the field title/label.
		 *
		 * Returns the human-readable title displayed as the field label.
		 *
		 * @since 1.0.0
		 *
		 * @return string|null The field title or null if not set.
		 *
		 * @example
		 *          ```php
		 *          $title = $field->get_title();
		 *          // Returns: 'Email Address'
		 *          ```
		 */
		public function get_title(): ?string {
			return $this->get_attribute( 'title' );
		}

		/**
		 * Generate the form field name attribute.
		 *
		 * Creates the name attribute for form submission in the format:
		 * - Regular: settings_id[field_id]
		 * - Group: settings_id[field_id][]
		 *
		 * @since 1.0.0
		 *
		 * @param bool $is_group Whether this is a group/multi-value field. Default false.
		 *
		 * @return string The generated name attribute value.
		 *
		 * @see Field::get_settings_id() For the settings ID portion.
		 * @see Field::get_id() For the field ID portion.
		 *
		 * @example
		 *          ```php
		 *          $name = $field->get_name();
		 *          // Returns: 'my_settings[my_field]'
		 *
		 *          $name = $field->get_name( true );
		 *          // Returns: 'my_settings[my_field][]'
		 *          ```
		 */
		public function get_name( bool $is_group = false ): string {
			$id         = $this->get_id();
			$setting_id = $this->get_settings_id();

			return $is_group ? sprintf( '%s[%s][]', $setting_id, $id ) : sprintf( '%s[%s]', $setting_id, $id );
		}

		/**
		 * Generate private option name.
		 *
		 * Creates a unique option name for private fields stored as separate
		 * WordPress options (not in the main settings array).
		 *
		 * @since 1.0.0
		 *
		 * @return string The private option name in format: _settings_id__field_id
		 *
		 * @see Field::is_private() For checking private status.
		 *
		 * @example
		 *          ```php
		 *          $private_name = $field->get_private_name();
		 *          // Returns: '_my_settings__api_key'
		 *          ```
		 */
		public function get_private_name(): string {
			$id         = $this->get_id();
			$setting_id = $this->get_settings_id();

			return sprintf( '_%s__%s', $setting_id, $id );
		}

		/**
		 * Check if field is marked as private.
		 *
		 * Private fields are stored as separate WordPress options rather than
		 * in the main settings array, useful for sensitive data like API keys.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if field is private, false otherwise.
		 *
		 * @see Field::get_private_name() For private option name generation.
		 *
		 * @example
		 *          ```php
		 *          if ( $field->is_private() ) {
		 *              // Handle private field differently
		 *          }
		 *          ```
		 */
		public function is_private(): bool {
			return true === $this->get_attribute( 'private', false );
		}

		/**
		 * Get datalist element ID.
		 *
		 * Generates the ID for the HTML5 datalist element associated with this field.
		 *
		 * @since 1.0.0
		 *
		 * @return string The datalist ID in format: field_id-datalist
		 *
		 * @see Field::get_datalist_markup() For datalist HTML generation.
		 *
		 * @example
		 *          ```php
		 *          $datalist_id = $field->get_datalist_id();
		 *          // Returns: 'my_field-datalist'
		 *          ```
		 */
		public function get_datalist_id(): string {
			return sprintf( '%s-datalist', $this->get_id() );
		}

		// =====================================================================
		// Field Type Methods
		// =====================================================================

		/**
		 * Get the normalized field type.
		 *
		 * Returns the field type after applying type aliases. For example,
		 * 'toggle' becomes 'checkbox', 'select2' becomes 'select'.
		 *
		 * @since 1.0.0
		 *
		 * @return string The normalized field type.
		 *
		 * @see Field::get_raw_type() For the original type.
		 * @see Field::get_type_alias() For the alias mappings.
		 *
		 * @example
		 *          ```php
		 *          // If raw type is 'toggle'
		 *          $type = $field->get_type();
		 *          // Returns: 'checkbox'
		 *          ```
		 */
		public function get_type(): string {
			$type  = $this->get_raw_type();
			$alias = $this->get_type_alias();
			$keys  = array_keys( $alias );

			if ( in_array( $type, $keys, true ) ) {
				return $alias[ $type ];
			}

			return $type;
		}

		/**
		 * Get the original/raw field type.
		 *
		 * Returns the field type exactly as specified in the configuration,
		 * without applying any aliases.
		 *
		 * @since 1.0.0
		 *
		 * @return string The raw field type. Defaults to 'text'.
		 *
		 * @see Field::get_type() For the normalized type.
		 *
		 * @example
		 *          ```php
		 *          $raw_type = $field->get_raw_type();
		 *          // Returns: 'toggle' (before alias applied)
		 *          ```
		 */
		public function get_raw_type(): string {
			return $this->get_attribute( 'type', 'text' );
		}

		/**
		 * Get field type alias mappings.
		 *
		 * Returns array mapping special field types to their base types.
		 * Used for determining which input method to use.
		 *
		 * @since 1.0.0
		 *
		 * @return string[] Associative array of type aliases.
		 *
		 * @see Field::get_type() Uses these aliases.
		 *
		 * @example
		 *          ```php
		 *          $aliases = $field->get_type_alias();
		 *          // Returns: array(
		 *          //     'tiny-text' => 'text',
		 *          //     'toggle'    => 'checkbox',
		 *          //     ...
		 *          // )
		 *          ```
		 */
		public function get_type_alias(): array {

			return array(
				'tiny-text'          => 'text',
				'small-text'         => 'text',
				'regular-text'       => 'text',
				'large-text'         => 'text',
				'code'               => 'text',
				'select2'            => 'select',
				'wc-enhanced-select' => 'select',
				'toggle'             => 'checkbox',
			);
		}

		/**
		 * Check if field type is a group type.
		 *
		 * Group type fields contain nested field configurations.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if type is 'group', false otherwise.
		 *
		 * @see Field::group_input() For group field rendering.
		 * @see Field::get_group_fields() For nested fields access.
		 *
		 * @example
		 *          ```php
		 *          if ( $field->is_type_group() ) {
		 *              $nested = $field->get_group_fields();
		 *          }
		 *          ```
		 */
		public function is_type_group(): bool {
			return 'group' === $this->get_type();
		}

		/**
		 * Get list of input types that use grouped/multiple inputs.
		 *
		 * These types render multiple input elements (radio buttons, checkboxes)
		 * and require special handling for labels and values.
		 *
		 * @since 1.0.0
		 *
		 * @return string[] Array of group input type names.
		 *
		 * @example
		 *          ```php
		 *          $group_types = $field->group_inputs();
		 *          // Returns: array( 'radio', 'checkbox', 'toggle', 'group' )
		 *          ```
		 */
		public function group_inputs(): array {
			return array( 'radio', 'checkbox', 'toggle', 'group' );
		}

		// =====================================================================
		// Options Methods
		// =====================================================================

		/**
		 * Get available options for select/radio/checkbox fields.
		 *
		 * Returns the options array configured for fields that offer choices.
		 *
		 * @since 1.0.0
		 *
		 * @return string[]|array<string, string> Options as key-value pairs.
		 *
		 * @example
		 *          ```php
		 *          $options = $field->get_options();
		 *          // Returns: array( 'yes' => 'Yes', 'no' => 'No' )
		 *          ```
		 */
		public function get_options(): array {
			return $this->get_attribute( 'options', array() );
		}

		// =====================================================================
		// Sanitization and Escape Methods
		// =====================================================================

		/**
		 * Check if field has a custom sanitize callback.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if custom sanitize_callback is set.
		 *
		 * @see Field::get_sanitize_callback() For retrieving the callback.
		 */
		public function has_sanitize_callback(): bool {
			return $this->has_attribute( 'sanitize_callback' );
		}

		/**
		 * Check if field has a custom escape callback.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if custom escape_callback is set.
		 *
		 * @see Field::get_escape_callback() For retrieving the callback.
		 */
		public function has_escape_callback(): bool {
			return $this->has_attribute( 'escape_callback' );
		}

		/**
		 * Get the sanitization callback function name.
		 *
		 * Returns the appropriate sanitization function for cleaning input data
		 * before storing in the database. Uses custom callback if set, otherwise
		 * determines based on field type.
		 *
		 * @since 1.0.0
		 *
		 * @return string The sanitization function name.
		 *
		 * @see Field::has_sanitize_callback() For checking custom callback.
		 *
		 * @example
		 *          ```php
		 *          $callback = $field->get_sanitize_callback();
		 *          $clean_value = call_user_func( $callback, $dirty_value );
		 *          ```
		 *
		 * @example Type-based defaults:
		 *          - email: 'sanitize_email'
		 *          - url: 'sanitize_url'
		 *          - textarea: 'sanitize_textarea_field'
		 *          - color: 'sanitize_hex_color'
		 *          - number: 'absint'
		 *          - default: 'sanitize_text_field'
		 */
		public function get_sanitize_callback(): string {

			$type = $this->get_type();

			if ( $this->has_sanitize_callback() ) {
				return $this->get_attribute( 'sanitize_callback' );
			}

			switch ( $type ) {
				case 'email':
					return 'sanitize_email';
				case 'url':
					return 'sanitize_url';
				case 'textarea':
					return 'sanitize_textarea_field';
				case 'color':
					return 'sanitize_hex_color';
				case 'number':
					return 'absint';
				default:
					return 'sanitize_text_field';
			}
		}

		/**
		 * Get the escape callback function name.
		 *
		 * Returns the appropriate escaping function for safely outputting data
		 * from the database. Uses custom callback if set, otherwise determines
		 * based on field type.
		 *
		 * @since 1.0.0
		 *
		 * @return string The escape function name.
		 *
		 * @see Field::has_escape_callback() For checking custom callback.
		 *
		 * @example
		 *          ```php
		 *          $callback = $field->get_escape_callback();
		 *          $safe_value = call_user_func( $callback, $db_value );
		 *          ```
		 *
		 * @example Type-based defaults:
		 *          - email: 'sanitize_email'
		 *          - url: 'esc_url'
		 *          - textarea: 'esc_textarea'
		 *          - color: 'sanitize_hex_color'
		 *          - number: 'absint'
		 *          - default: 'esc_html'
		 */
		public function get_escape_callback(): string {

			$type = $this->get_type();

			if ( $this->has_escape_callback() ) {
				return $this->get_attribute( 'escape_callback' );
			}

			switch ( $type ) {
				case 'email':
					return 'sanitize_email';
				case 'url':
					return 'esc_url';
				case 'textarea':
					return 'esc_textarea';
				case 'color':
					return 'sanitize_hex_color';
				case 'number':
					return 'absint';
				default:
					return 'esc_html';
			}
		}

		// =====================================================================
		// Attribute Methods
		// =====================================================================

		/**
		 * Get the complete field configuration array.
		 *
		 * Returns all field attributes as originally configured plus any
		 * modifications made during processing.
		 *
		 * @since 1.0.0
		 *
		 * @return string[]|array<string, mixed> The field configuration array.
		 *
		 * @example
		 *          ```php
		 *          $config = $field->get_field();
		 *          // Returns: array( 'id' => 'my_field', 'type' => 'text', ... )
		 *          ```
		 */
		public function get_field(): array {
			return $this->field;
		}

		/**
		 * Check if field has a specific attribute.
		 *
		 * @since 1.0.0
		 *
		 * @param string $attribute The attribute name to check.
		 *
		 * @return bool True if attribute exists, false otherwise.
		 *
		 * @see Field::get_attribute() For retrieving attribute values.
		 *
		 * @example
		 *          ```php
		 *          if ( $field->has_attribute( 'description' ) ) {
		 *              echo $field->get_attribute( 'description' );
		 *          }
		 *          ```
		 */
		public function has_attribute( string $attribute ): bool {
			$field = $this->get_field();

			return isset( $field[ $attribute ] );
		}

		/**
		 * Get a specific field attribute value.
		 *
		 * Retrieves an attribute from the field configuration, returning
		 * the default value if the attribute is not set.
		 *
		 * @since 1.0.0
		 *
		 * @param string                    $attribute     The attribute name to retrieve.
		 * @param string|string[]|null|bool $default_value Optional. Default if not set. Default null.
		 *
		 * @return string|string[]|null|bool The attribute value or default.
		 *
		 * @see Field::has_attribute() For existence checking.
		 *
		 * @example
		 *          ```php
		 *          $placeholder = $field->get_attribute( 'placeholder', 'Enter value...' );
		 *          $required = $field->get_attribute( 'required', false );
		 *          ```
		 */
		public function get_attribute( string $attribute, $default_value = null ) {
			$field = $this->get_field();

			return $field[ $attribute ] ?? $default_value;
		}

		/**
		 * Check if field should be shown in REST API.
		 *
		 * Validates the show_in_rest attribute to determine REST API visibility.
		 * Returns false if attribute is missing, false, or empty string.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if field should be included in REST API.
		 *
		 * @see Field::get_rest_value() For REST API value retrieval.
		 * @see Field::get_rest_type() For REST schema type.
		 *
		 * @example
		 *          ```php
		 *          if ( $field->has_show_in_rest() ) {
		 *              $schema['properties'][ $field->get_id() ] = array(
		 *                  'type' => $field->get_rest_type(),
		 *              );
		 *          }
		 *          ```
		 */
		public function has_show_in_rest(): bool {

			if ( ! $this->has_attribute( 'show_in_rest' ) ) {
				return false;
			}

			if ( false === $this->get_attribute( 'show_in_rest' ) ) {
				return false;
			}

			if ( is_string( $this->get_attribute( 'show_in_rest' ) ) && $this->is_empty_string( $this->get_attribute( 'show_in_rest' ) ) ) {
				return false;
			}

			return true;
		}

		// =====================================================================
		// CSS Class Methods
		// =====================================================================

		/**
		 * Get available field size CSS class names.
		 *
		 * Returns WordPress standard text input size classes.
		 *
		 * @since 1.0.0
		 *
		 * @return string[] Array of size class names.
		 *
		 * @see Field::prepare_classes() Uses these for size class handling.
		 *
		 * @example
		 *          ```php
		 *          $sizes = $field->get_field_size_css_classes();
		 *          // Returns: array( 'regular-text', 'small-text', 'tiny-text', 'large-text' )
		 *          ```
		 */
		public function get_field_size_css_classes(): array {
			return array( 'regular-text', 'small-text', 'tiny-text', 'large-text' );
		}

		/**
		 * Prepare and merge CSS classes for field elements.
		 *
		 * Combines user-specified classes with default classes, handling
		 * size class conflicts (user size classes override defaults).
		 *
		 * @since 1.0.0
		 *
		 * @param string|string[] $classes       User-specified class names.
		 * @param string|string[] $default_value Default class names.
		 *
		 * @return string[] Merged array of unique class names.
		 *
		 * @see Field::get_field_size_css_classes() For size class detection.
		 *
		 * @example
		 *          ```php
		 *          $classes = $field->prepare_classes( 'my-class small-text', 'regular-text' );
		 *          // Returns: array( 'my-class', 'small-text' )
		 *          // Note: 'regular-text' removed because user specified 'small-text'
		 *          ```
		 */
		public function prepare_classes( $classes, $default_value = '' ): array {

			$default_classnames = is_array( $default_value ) ? $default_value : explode( ' ', $default_value );
			$setting_classnames = is_array( $classes ) ? $classes : explode( ' ', $classes );

			$classnames                = array();
			$remove_default_size_class = false;

			/**
			 * Settings Classes.
			 *
			 * @var string[] $setting_classnames
			 */
			foreach ( $setting_classnames as $setting_classname ) {
				if ( in_array( $setting_classname, $this->get_field_size_css_classes(), true ) ) {
					$remove_default_size_class = true;
				}
			}

			/**
			 * Default Classes.
			 */
			foreach ( $default_classnames as $default_classname ) {
				if ( $remove_default_size_class && in_array( $default_classname, $this->get_field_size_css_classes(), true ) ) {
					continue;
				}
				$classnames[] = $default_classname;
			}

			return array_unique( array_merge( $setting_classnames, $classnames ) );
		}

		/**
		 * Get field CSS class attribute value.
		 *
		 * Returns the class attribute from field configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param string $default_value Optional. Default class if none set.
		 *
		 * @return bool|string|string[]|null The class value.
		 *
		 * @example
		 *          ```php
		 *          $class = $field->get_css_class( 'regular-text' );
		 *          ```
		 */
		public function get_css_class( string $default_value = '' ) {
			return $this->get_attribute( 'class', $default_value );
		}

		// =====================================================================
		// Suffix Methods
		// =====================================================================

		/**
		 * Get field suffix text.
		 *
		 * Returns the suffix displayed after the input field (e.g., units like "px", "%").
		 *
		 * @since 1.0.0
		 *
		 * @return string|null The suffix text or null.
		 *
		 * @see Field::has_suffix() For checking suffix existence.
		 *
		 * @example
		 *          ```php
		 *          $suffix = $field->get_suffix();
		 *          // Returns: 'px'
		 *          ```
		 */
		public function get_suffix(): ?string {
			return $this->get_attribute( 'suffix' );
		}

		/**
		 * Check if field has a suffix.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if suffix is configured.
		 *
		 * @see Field::get_suffix() For retrieving the suffix.
		 */
		public function has_suffix(): bool {
			return $this->has_attribute( 'suffix' );
		}

		// =====================================================================
		// Unit Input Methods
		// =====================================================================

		/**
		 * Get available CSS units.
		 *
		 * Returns the default list of CSS units for unit input fields.
		 *
		 * @since 1.0.0
		 *
		 * @return string[] Array of unit strings.
		 *
		 * @see Field::unit_input() Uses these as default units.
		 *
		 * @example
		 *          ```php
		 *          $units = $field->get_available_units();
		 *          // Returns: array( 'px', 'em', 'rem', '%', 'vh', 'vw', ... )
		 *          ```
		 */
		public function get_available_units(): array {
			return array(
				'px',
				'em',
				'rem',
				'%',
				'vh',
				'vw',
				'vmin',
				'vmax',
				'fr',
				's',
				'ms',
			);
		}

		/**
		 * Parse unit value string into components.
		 *
		 * Extracts the numeric value and unit from a CSS-style value string.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value The unit value string (e.g., "10px", "1.5em").
		 *
		 * @return string[] Array with 'value' and 'unit' keys.
		 *
		 * @see Field::unit_input() Uses this for value parsing.
		 * @see Field::get_unit_markup() Uses this for unit selection.
		 *
		 * @example
		 *          ```php
		 *          $parsed = $field->parse_unit( '10px' );
		 *          // Returns: array( 'value' => '10', 'unit' => 'px' )
		 *
		 *          $parsed = $field->parse_unit( '1.5em' );
		 *          // Returns: array( 'value' => '1.5', 'unit' => 'em' )
		 *          ```
		 */
		public function parse_unit( string $value ): array {

			// Regular expression to match number (including decimals) and unit.
			// Matches: optional sign, digits, optional decimal point and digits, unit.
			$pattern = '/^(?<value>[-]?[[:digit:]]+\.?[[:digit:]]*)(?<unit>[[:alpha:]%]+)$/';

			preg_match( $pattern, trim( $value ), $matches );

			if ( $this->is_empty_array( $matches ) ) {
				return array(
					'value' => $value,
					'unit'  => '',
				);
			}

			return $matches;
		}

		/**
		 * Generate unit selector dropdown markup.
		 *
		 * Creates an HTML select element for choosing CSS units.
		 *
		 * @since 1.0.0
		 *
		 * @param string $id    The base element ID.
		 * @param string $value The current value including unit.
		 *
		 * @return string HTML select element markup.
		 *
		 * @see Field::parse_unit() For extracting current unit.
		 * @see Field::get_available_units() For default units list.
		 *
		 * @example
		 *          ```php
		 *          $markup = $field->get_unit_markup( 'my_field', '10px' );
		 *          // Returns: '<select id="my_field-unit"><option selected...>px</option>...</select>'
		 *          ```
		 */
		public function get_unit_markup( string $id, string $value ): string {

			$_id   = sprintf( '%s-unit', $id );
			$units = $this->get_attribute( 'units', $this->get_available_units() );

			['unit' => $unit] = $this->parse_unit( $value );

			$options = array();

			foreach ( $units as $u ) {
				$options[] = sprintf( '<option %s value="%s">%s</option>', selected( $unit, $u, false ), esc_attr( $u ), esc_html( $u ) );
			}

			return sprintf( '<select id="%s">%s</select>', esc_attr( $_id ), implode( '', $options ) );
		}

		// =====================================================================
		// Input Markup Methods - Text Inputs
		// =====================================================================

		/**
		 * Generate text input field markup.
		 *
		 * Creates HTML for text-type inputs including text, email, url, password,
		 * search, and other single-line text inputs.
		 *
		 * @since 1.0.0
		 *
		 * @param string $css_class Optional. Default CSS class. Default 'regular-text'.
		 *
		 * @return string The HTML input markup.
		 *
		 * @see Field::get_input_markup() Calls this for text-type fields.
		 *
		 * @example
		 *          ```php
		 *          $html = $field->text_input();
		 *          $html = $field->text_input( 'small-text' );
		 *          ```
		 */
		public function text_input( string $css_class = 'regular-text' ): string {

			$id                    = $this->get_settings()->get_field_id( $this->get_id() );
			$class                 = $this->get_css_class();
			$type                  = $this->get_type();
			$additional_attributes = $this->get_attribute( 'html_attributes', array() );
			$escape_callback       = $this->get_escape_callback();
			$value                 = map_deep( $this->get_value(), $escape_callback );
			$raw_type              = $this->get_raw_type();
			$system_class          = array( $css_class );

			if ( 'code' === $raw_type ) {
				$system_class[] = 'code';
			}

			if ( 'color' === $raw_type ) {
				$system_class[] = 'color';
			}

			$attributes = array(
				'id'    => $id,
				'type'  => $type,
				'class' => $this->prepare_classes( $class, $system_class ),
				'name'  => $this->get_name(),
				'value' => $value,
			);

			if ( $this->has_attribute( 'html_datalist' ) ) {
				$attributes['list'] = $this->get_datalist_id();
			}

			if ( $this->has_attribute( 'description' ) ) {
				$attributes['aria-describedby'] = sprintf( '%s-description', $id );
			}

			if ( $this->has_attribute( 'required' ) ) {
				$attributes['required'] = true;
			}

			if ( $this->has_attribute( 'placeholder' ) ) {
				$attributes['placeholder'] = $this->get_attribute( 'placeholder' );
			}

			$wrapper_classes = array(
				'has-suffix' => $this->has_suffix(),
				'input-container',
			);

			$suffix_markup = $this->has_suffix() ? sprintf( '<span class="input-suffix">%s</span>', $this->get_suffix() ) : '';

			return sprintf( '<div class="%s"><span class="input-field"><input %s /></span> %s</div>', esc_attr( $this->get_css_classes( $wrapper_classes ) ), $this->get_html_attributes( $attributes, $additional_attributes ), $suffix_markup );
		}

		/**
		 * Generate unit input field markup.
		 *
		 * Creates HTML for number inputs with unit selection (e.g., "10px", "2em").
		 * Includes a number input, unit dropdown, and hidden field for combined value.
		 *
		 * @since 1.0.0
		 *
		 * @param string $css_class Optional. Default CSS class. Default 'small-text'.
		 *
		 * @return string The HTML input markup with unit selector.
		 *
		 * @see Field::parse_unit() For value parsing.
		 * @see Field::get_unit_markup() For unit dropdown.
		 *
		 * @example
		 *          ```php
		 *          $html = $field->unit_input();
		 *          ```
		 */
		public function unit_input( string $css_class = 'small-text' ): string {

			$id                    = $this->get_settings()->get_field_id( $this->get_id() );
			$class                 = $this->get_css_class();
			$additional_attributes = $this->get_attribute( 'html_attributes', array() );
			$units                 = $this->get_attribute( 'units', array() );
			$escape_callback       = $this->get_escape_callback();
			$value                 = map_deep( $this->get_value(), $escape_callback );
			$system_class          = array( $css_class );

			['value' => $input] = $this->parse_unit( $value );

			$attributes = array(
				'id'    => $id,
				'type'  => 'number',
				'class' => $this->prepare_classes( $class, $system_class ),
				'value' => $input,
				'step'  => 'any',
			);

			$has_suffix = count( $units ) < 2;

			if ( $this->has_attribute( 'description' ) ) {
				$attributes['aria-describedby'] = sprintf( '%s-description', $id );
			}

			if ( $this->has_attribute( 'required' ) ) {
				$attributes['required'] = true;
			}

			if ( $this->has_attribute( 'placeholder' ) ) {
				$attributes['placeholder'] = $this->get_attribute( 'placeholder' );
			}

			$wrapper_classes = array(
				'has-suffix' => $has_suffix,
				'input-container',
				'unit-input-container',
				'number-unit-input-container',
			);

			$unit_markup = sprintf( '<span class="input-unit">%s</span>', $this->get_unit_markup( $id, $value ) );

			$unit_hidden_input = sprintf( '<input class="input-unit-value" readonly name="%s" type="hidden" value="%s" />', $this->get_name(), $value );

			$suffix_markup = $has_suffix ? sprintf( '<span class="input-suffix">%s</span>', $units[0] ) : $unit_markup;

			$attrs = $this->get_html_attributes( $additional_attributes, $attributes );

			return sprintf( '<div class="%s"><span class="input-field"><input %s /></span> %s %s </div>', esc_attr( $this->get_css_classes( $wrapper_classes ) ), $attrs, $suffix_markup, $unit_hidden_input );
		}

		/**
		 * Generate textarea input markup.
		 *
		 * Creates HTML for multi-line text input fields.
		 *
		 * @since 1.0.0
		 *
		 * @param string $css_class Optional. Default CSS class. Default 'regular-text'.
		 *
		 * @return string The HTML textarea markup.
		 *
		 * @see Field::get_input_markup() Calls this for textarea type.
		 *
		 * @example
		 *          ```php
		 *          $html = $field->textarea_input();
		 *          ```
		 */
		public function textarea_input( string $css_class = 'regular-text' ): string {

			$id                    = $this->get_settings()->get_field_id( $this->get_id() );
			$class                 = $this->get_css_class();
			$type                  = $this->get_type();
			$additional_attributes = $this->get_attribute( 'html_attributes', array() );

			$escape_callback = $this->get_escape_callback();
			$value           = map_deep( $this->get_value(), $escape_callback );

			$attributes = array(
				'id'    => $id,
				'type'  => $type,
				'class' => $this->prepare_classes( $class, $css_class ),
				'name'  => $this->get_name(),
			);

			if ( $this->has_attribute( 'description' ) ) {
				$attributes['aria-describedby'] = sprintf( '%s-description', $id );
			}

			if ( $this->has_attribute( 'required' ) ) {
				$attributes['required'] = true;
			}

			if ( $this->has_attribute( 'placeholder' ) ) {
				$attributes['placeholder'] = $this->get_attribute( 'placeholder' );
			}

			return sprintf( '<textarea %s>%s</textarea>', $this->get_html_attributes( $attributes, $additional_attributes ), $value );
		}

		// =====================================================================
		// Input Markup Methods - Selection Inputs
		// =====================================================================

		/**
		 * Generate checkbox/radio input markup.
		 *
		 * Creates HTML for checkbox, radio, and toggle inputs. Handles both
		 * single options and multiple option groups.
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML fieldset with inputs markup.
		 *
		 * @see Field::get_input_markup() Calls this for checkbox/radio/toggle types.
		 *
		 * @example
		 *          ```php
		 *          $html = $field->check_input();
		 *          ```
		 */
		public function check_input(): string {

			$id       = $this->get_settings()->get_field_id( $this->get_id() );
			$type     = $this->get_type();
			$title    = $this->get_title();
			$name     = $this->get_name();
			$value    = $this->get_value();
			$options  = $this->get_options();
			$raw_type = $this->get_raw_type();

			$is_toggle   = 'toggle' === $raw_type;
			$is_checkbox = 'checkbox' === $type;

			// Group checkbox. Options will be an array.
			if ( $is_checkbox && count( $options ) > 1 ) {
				$name = $this->get_name( true );
			}

			// Single checkbox. Option will be string.
			if ( $is_checkbox && $this->is_empty_array( $options ) ) {
				$options = array( 'yes' => $title );
			}

			// Check radio input have options declared.
			if ( 'radio' === $type && $this->is_empty_array( $options ) ) {
				/* translators: 1: Input field ID, 2: Field title. */
				$message = sprintf( esc_html__( 'Input Field: "%1$s". Title: "%2$s" need options to choose. "option"=>["key"=>"value"]' ), $id, $title );
				wp_trigger_error( __METHOD__, $message );

				return '';
			}

			$inputs           = array();
			$is_single_option = count( $options ) === 1;

			/**
			 * Group Options.
			 *
			 * @var array<string, string> $options
			 */
			foreach ( $options as $option_key => $option_value ) {

				$uniq_id = $this->get_settings()->get_field_id( $id, $option_key );

				$attributes = array(
					'id'      => $uniq_id,
					'type'    => $type,
					'name'    => $name,
					'value'   => esc_attr( $option_key ),
					'checked' => ( 'checkbox' === $type ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ), true ) : $value == $option_key, // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				);

				$option_description = '';
				if ( is_array( $option_value ) && isset( $option_value['description'] ) ) {
					$option_description = sprintf( '<p class="description" id="%s-description">%s</p>', $uniq_id, $option_value['description'] );
				}

				if ( is_array( $option_value ) && isset( $option_value['label'] ) ) {
					$option_value = $option_value['label'];
				}

				if ( $is_toggle ) {
					$attributes['class'] = array( 'toggle' );
				}

				if ( $is_single_option ) {
					$uniq_id          = $id;
					$attributes['id'] = $id;
				}

				$inputs[] = sprintf( '<label for="%s"><input %s /><span>%s</span></label> %s', esc_attr( $uniq_id ), $this->get_html_attributes( $attributes ), esc_html( $option_value ), wp_kses_post( $option_description ) );
			}

			return sprintf( '<fieldset><legend class="screen-reader-text">%s</legend>%s</fieldset>', $title, implode( '<br />', $inputs ) );
		}

		/**
		 * Generate select dropdown markup.
		 *
		 * Creates HTML for select dropdowns including support for multiple
		 * selection and enhanced select libraries (Select2, WooCommerce).
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML select element markup.
		 *
		 * @see Field::get_input_markup() Calls this for select types.
		 *
		 * @example
		 *          ```php
		 *          $html = $field->select_input();
		 *          ```
		 */
		public function select_input(): string {

			$id                    = $this->get_settings()->get_field_id( $this->get_id() );
			$type                  = $this->get_type();
			$title                 = $this->get_title();
			$value                 = $this->get_value();
			$is_multiple           = $this->has_attribute( 'multiple' );
			$options               = $this->get_options();
			$class                 = $this->get_css_class();
			$name                  = $this->get_name( $is_multiple );
			$additional_attributes = $this->get_attribute( 'html_attributes', array() );

			$raw_type     = $this->get_raw_type();
			$system_class = array( 'regular-text' );

			if ( 'select2' === $raw_type ) {
				$system_class[] = 'select2';
			}

			if ( 'wc-enhanced-select' === $raw_type ) {
				$system_class[] = 'wc-enhanced-select';
			}

			$attributes = array(
				'id'       => $id,
				'name'     => $name,
				'class'    => $this->prepare_classes( $class, $system_class ),
				'multiple' => $is_multiple,
			);

			if ( $this->has_attribute( 'description' ) ) {
				$attributes['aria-describedby'] = sprintf( '%s-description', $id );
			}

			if ( $this->has_attribute( 'required' ) ) {
				$attributes['required'] = true;
			}

			if ( $this->has_attribute( 'placeholder' ) ) {
				$attributes['placeholder'] = $this->get_attribute( 'placeholder' );
			}

			$inputs = array();

			foreach ( $options as $option_key => $option_value ) {
				$selected = ( $is_multiple ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ), true ) : $value == $option_key; // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				$inputs[] = sprintf( '<option %s value="%s"><span>%s</span></option>', $this->get_html_attributes( array( 'selected' => $selected ) ), esc_attr( $option_key ), esc_html( $option_value ) );
			}

			return sprintf( '<select %s>%s</select>', $this->get_html_attributes( $attributes, $additional_attributes ), implode( '', $inputs ) );
		}

		// =====================================================================
		// Input Markup Methods - Custom Input
		// =====================================================================

		/**
		 * Handle custom field type rendering.
		 *
		 * Delegates to the parent settings object's custom_field method for
		 * rendering non-standard field types. Triggers error if method not implemented.
		 *
		 * @since 1.0.0
		 *
		 * @return string The custom field HTML markup.
		 *
		 * @throws \WP_Exception If custom_field method not implemented.
		 *
		 * @see Field::get_input_markup() Calls this for unknown types.
		 *
		 * @example Implementing custom fields in Settings class:
		 *          ```php
		 *          public function custom_field( Field $field ): string {
		 *              if ( 'color-picker' === $field->get_type() ) {
		 *                  return '<input type="color" ... />';
		 *              }
		 *              return '';
		 *          }
		 *          ```
		 */
		public function custom_input(): string {

			$type = $this->get_type();

			if ( method_exists( $this->get_settings(), 'custom_field' ) ) {
				return $this->get_settings()->custom_field( $this );
			}

			/* translators: 1: Field type, 2: Settings class name. */
			$message = sprintf( esc_html__( 'Field: "%1$s" not implemented. Please add "custom_field" method in "%2$s" to implement.' ), $type, get_class( $this->get_settings() ) );
			wp_trigger_error( '', $message );

			return '';
		}

		// =====================================================================
		// Group Field Methods
		// =====================================================================

		/**
		 * Get nested Field objects for group type.
		 *
		 * Creates Field instances for each nested field in a group configuration.
		 *
		 * @since 1.0.0
		 *
		 * @return Field[] Array of Field objects for nested fields.
		 *
		 * @see Field::is_type_group() For group type checking.
		 * @see Field::group_input() Uses this for rendering.
		 *
		 * @example
		 *          ```php
		 *          foreach ( $field->get_group_fields() as $nested_field ) {
		 *              echo $nested_field->get_input_markup();
		 *          }
		 *          ```
		 */
		public function get_group_fields(): array {

			$name         = $this->get_name();
			$group_value  = $this->get_value( array() );
			$group_fields = $this->get_attribute( 'fields', array() );

			$fields = array();

			/**
			 * Group Filed object array
			 *
			 * @var array<string, mixed> $group_fields $group_fields
			 */

			foreach ( $group_fields as $field ) {

				$_field = ( new self( $this->get_settings(), $field, $group_value ) )->add_settings_id( $name );

				$fields[] = $_field;
			}

			return $fields;
		}

		/**
		 * Get REST API values for group fields.
		 *
		 * Collects escaped values from all nested fields that should be
		 * included in REST API responses.
		 *
		 * @since 1.0.0
		 *
		 * @return array<string, string|string[]> Associative array of field IDs to values.
		 *
		 * @see Field::has_show_in_rest() For REST visibility check.
		 *
		 * @example
		 *          ```php
		 *          $rest_values = $field->get_rest_group_values();
		 *          // Returns: array( 'sub_field_1' => 'value1', 'sub_field_2' => 'value2' )
		 *          ```
		 */
		public function get_rest_group_values(): array {

			$values = array();

			foreach ( $this->get_group_fields() as $field ) {

				if ( false === $field->has_show_in_rest() ) {
					continue;
				}

				$id              = $field->get_id();
				$escape_callback = $this->get_escape_callback();
				$value           = map_deep( $field->get_value(), $escape_callback );

				$values[ $id ] = $value;
			}

			return $values;
		}

		/**
		 * Get REST API value for this field.
		 *
		 * Returns the escaped field value suitable for REST API responses.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed The escaped field value.
		 *
		 * @see Field::get_escape_callback() For the escape function.
		 *
		 * @example
		 *          ```php
		 *          $rest_value = $field->get_rest_value();
		 *          ```
		 */
		public function get_rest_value() {
			$escape_callback = $this->get_escape_callback();

			return map_deep( $this->get_value(), $escape_callback );
		}

		/**
		 * Get all values from group fields.
		 *
		 * Collects raw values from all nested fields in a group.
		 *
		 * @since 1.0.0
		 *
		 * @return array<string, mixed> Associative array of field IDs to values.
		 *
		 * @see Field::get_group_fields() For nested field access.
		 *
		 * @example
		 *          ```php
		 *          $all_values = $field->get_group_values();
		 *          ```
		 */
		public function get_group_values(): array {

			$values = array();

			foreach ( $this->get_group_fields() as $field ) {
				$id            = $field->get_id();
				$value         = $field->get_value();
				$values[ $id ] = $value;
			}

			return $values;
		}

		/**
		 * Get a specific nested field's value.
		 *
		 * Retrieves the value of a single field within a group by its ID.
		 *
		 * @since 1.0.0
		 *
		 * @param string                    $field_id      The nested field ID to find.
		 * @param bool|null|string|string[] $default_value Optional. Default if not found.
		 *
		 * @return bool|null|string|string[] The field value or default.
		 *
		 * @see Field::get_group_fields() For nested field access.
		 *
		 * @example
		 *          ```php
		 *          $sub_value = $field->get_group_value( 'sub_field_id', 'default' );
		 *          ```
		 */
		public function get_group_value( string $field_id, $default_value = null ) {

			foreach ( $this->get_group_fields() as $field ) {
				$id = $field->get_id();
				if ( $id === $field_id ) {
					return $field->get_value( $default_value );
				}
			}

			return $default_value;
		}

		/**
		 * Generate group field input markup.
		 *
		 * Creates HTML for group-type fields containing multiple nested inputs
		 * within a fieldset. Supports various nested field types including text,
		 * select, checkbox, radio, textarea, and unit inputs.
		 *
		 * @since 1.0.0
		 *
		 * @param string $css_class Optional. Default CSS class for inputs. Default 'small-text'.
		 *
		 * @return string The HTML fieldset markup containing all nested fields.
		 *
		 * @see Field::get_group_fields() For nested field retrieval.
		 * @see Field::get_input_markup() Calls this for group type.
		 *
		 * @example
		 *          ```php
		 *          $html = $field->group_input();
		 *          ```
		 */
		public function group_input( string $css_class = 'small-text' ): string {

			$group_id     = $this->get_id();
			$group_title  = $this->get_title();
			$group_fields = $this->get_group_fields();

			$inputs = array();

			// @TODO: Add Conditional for GROUP.
			// @TODO: Add Tooltip for GROUP.

			foreach ( $group_fields as $field ) {

				$field_id          = $field->get_id();
				$uniq_id           = $this->get_settings()->get_group_field_id( $group_id, $field_id );
				$field_title       = $field->get_title();
				$field_type        = $field->get_type();
				$raw_field_type    = $field->get_raw_type();
				$field_name        = $field->get_name();
				$field_options     = $field->get_options();
				$field_placeholder = $field->get_attribute( 'placeholder' );
				$field_required    = $field->has_attribute( 'required' );
				$field_suffix      = $field->get_suffix();
				$has_field_suffix  = $field->has_suffix();
				$field_classes     = $this->prepare_classes( $field->get_css_class(), $css_class );
				$escape_callback   = $this->get_escape_callback();
				$field_value       = map_deep( $field->get_value(), $escape_callback );
				$field_attributes  = $field->get_attribute( 'html_attributes', array() );

				$has_condition = $field->has_attribute( 'condition' );

				$condition = $has_condition ? array(
					'inert'                             => true,
					'data-storepress-conditional-field' => $field->get_attribute( 'condition', array() ),
				) : array();

				$conditional_attr = $this->get_html_attributes( $condition );

				$attributes = array(
					'id'          => $uniq_id,
					'type'        => $field_type,
					'class'       => $field_classes,
					'name'        => $field_name,
					'value'       => $field_value,
					'placeholder' => $field_placeholder,
					'required'    => $field_required,
				);

				$is_toggle   = 'toggle' === $raw_field_type;
				$is_checkbox = ( 'checkbox' === $field_type );

				if ( $is_checkbox ) {
					$attributes['type'] = 'checkbox';
				}

				// Group checkbox name.
				if ( $is_checkbox && count( $field_options ) > 1 ) {
					$attributes['name'] = $field->get_name( true );
				}

				if ( in_array( $field_type, $this->group_inputs(), true ) ) {

					$attributes['class'] = array();

					// Single checkbox.
					if ( $is_checkbox && $this->is_empty_array( $field_options ) ) {
						$attributes['value']   = 'yes';
						$attributes['checked'] = 'yes' === $field_value;

						if ( $is_toggle ) {
							$attributes['class'][] = 'toggle';
						}

						$tooltip_markup  = $field->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $field->get_attribute( 'tooltip' ) ) ) : '';
						$required_markup = $field->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';

						$inputs[] = sprintf( '<ul %s class="input-wrapper single-input-wrapper">', $conditional_attr );
						$inputs[] = sprintf( '<li class="group-field-inputs"><label for="%s"><input %s /><span>%s</span> %s %s</label></li>', esc_attr( $uniq_id ), $this->get_html_attributes( $attributes ), esc_html( $field_title ), $required_markup, $tooltip_markup );
						$inputs[] = '</ul>';
						continue;
					}

					// Checkbox and Radio.
					/**
					 * Group Options.
					 *
					 * @var array<string, string> $field_options
					 */

					$tooltip_markup  = $field->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $field->get_attribute( 'tooltip' ) ) ) : '';
					$required_markup = $field->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';

					$inputs[] = sprintf( '<ul %s class="input-wrapper multiple-input-wrapper"><li class="group-field-label"><span class="input-label-wrapper"><span class="input-label">%s</span> %s %s</span></li><li class="group-field-inputs"><ul>', $conditional_attr, esc_html( $field_title ), $required_markup, $tooltip_markup );

					foreach ( $field_options as $option_key => $option_value ) {
						$uniq_id               = $this->get_settings()->get_group_field_id( $group_id, $field_id, $option_key );
						$attributes['value']   = esc_attr( $option_key );
						$attributes['checked'] = is_array( $field_value ) ? in_array( $option_key, $field_value, true ) : $option_key == $field_value;  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
						$attributes['id']      = $uniq_id;

						if ( $is_toggle ) {
							$attributes['class'][] = 'toggle';
						}

						$attributes['required'] = false;

						$inputs[] = sprintf( '<li><label for="%s"><input %s /><span>%s</span></label></li>', esc_attr( $uniq_id ), $this->get_html_attributes( $attributes ), esc_html( $option_value ) );
					}
					$inputs[] = '</ul></li></ul>';
				} elseif ( 'select' === $field_type ) {
					/**
					 * Group Options.
					 *
					 * @var array<string, string> $field_options
					 */

					$options = array();
					foreach ( $field_options as $option_key => $option_value ) {
						$is_selected = is_array( $field_value ) ? in_array( $option_key, $field_value, true ) : $option_key == $field_value;  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
						$options[]   = sprintf( '<option %s value="%s">%s</option>', selected( $is_selected, true, false ), esc_attr( $option_key ), esc_html( $option_value ) );
					}

					$tooltip_markup  = $field->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $field->get_attribute( 'tooltip' ) ) ) : '';
					$required_markup = $field->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';

					$inputs[] = sprintf( '<ul %s class="input-wrapper select-input-wrapper">', $conditional_attr );
					$inputs[] = sprintf( '<li class="group-field-label"><label for="%s"><span class="input-label-wrapper"><span class="input-label">%s</span> %s %s</span></label></li>', esc_attr( $uniq_id ), esc_html( $field_title ), $required_markup, $tooltip_markup );
					$inputs[] = sprintf( '<li class="group-field-inputs"><select %s>%s</select></li>', $this->get_html_attributes( $attributes ), implode( '', $options ) );
					$inputs[] = '</ul>';
				} elseif ( 'textarea' === $field_type ) {
					// Input box.
					$attributes['value'] = false;
					$tooltip_markup      = $field->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $field->get_attribute( 'tooltip' ) ) ) : '';
					$required_markup     = $field->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';

					$inputs[] = sprintf( '<ul %s class="input-wrapper textarea-input-wrapper">', $conditional_attr );
					$inputs[] = sprintf( '<li class="group-field-label"><label for="%s"><span class="input-label-wrapper"><span class="input-label">%s</span> %s %s</span></label></li><li class="group-field-inputs"><textarea %s>%s</textarea></li>', esc_attr( $uniq_id ), esc_html( $field_title ), $required_markup, $tooltip_markup, $this->get_html_attributes( $attributes, $field_attributes ), $field_value );
					$inputs[] = '</ul>';
				} elseif ( 'unit' === $field_type ) {

					$units = $field->get_attribute( 'units', array() );

					$has_suffix = count( $units ) < 2;

					['value' => $input] = $this->parse_unit( $field_value );

					$attributes = array(
						'id'          => $uniq_id,
						'type'        => 'number',
						'class'       => $field_classes,
						'value'       => $input,
						'placeholder' => $field_placeholder,
						'required'    => $field_required,
						'step'        => 'any',
					);

					$unit_markup       = sprintf( '<span class="input-unit">%s</span>', $this->get_unit_markup( $uniq_id, $field_value ) );
					$unit_hidden_input = sprintf( '<input class="input-unit-value" readonly name="%s" type="hidden" value="%s" />', $field_name, $field_value );
					$suffix_markup     = $has_suffix ? sprintf( '<span class="input-suffix">%s</span>', $units[0] ) : $unit_markup;

					$datalist_markup = '';
					if ( $field->has_attribute( 'html_datalist' ) ) {

						$datalist_id        = sprintf( '%s-datalist', $uniq_id );
						$attributes['list'] = $datalist_id;

						$datalist_markup = sprintf( '<datalist id="%s">', $datalist_id );
						$datalist_items  = $field->get_attribute( 'html_datalist' );
						$is_numeric      = wp_is_numeric_array( $datalist_items );
						foreach ( $datalist_items as $value => $label ) {
							if ( $is_numeric ) {
								$datalist_markup .= sprintf( '<option value="%s"></option>', $label );
							} else {
								$datalist_markup .= sprintf( '<option value="%s" label="%s"></option>', $value, $label );
							}
						}
						$datalist_markup .= '</datalist>';
					}

					$wrapper_classes = array(
						'input-container',
						'unit-input-container',
						'has-suffix' => $has_suffix,
					);

					$tooltip_markup  = $field->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $field->get_attribute( 'tooltip' ) ) ) : '';
					$required_markup = $field->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';

					$inputs[] = sprintf( '<ul %s class="input-wrapper unit-input-wrapper">', $conditional_attr );
					$inputs[] = sprintf(
						'<li class="group-field-label"><label for="%s"><span class="input-label-wrapper"><span class="input-label">%s</span> %s %s</span></label></li><li class="group-field-inputs"><span class="%s"><span class="input-field"><input %s /></span> %s %s</span>%s</li>',
						esc_attr( $uniq_id ),
						esc_html( $field_title ),
						$required_markup,
						$tooltip_markup,
						esc_attr( $this->get_css_classes( $wrapper_classes ) ),
						$this->get_html_attributes( $attributes, $field_attributes ),
						$suffix_markup,
						$unit_hidden_input,
						$datalist_markup
					);
					$inputs[] = '</ul>';
				} else {

					$datalist_markup = '';
					if ( $field->has_attribute( 'html_datalist' ) ) {

						$datalist_id        = sprintf( '%s-datalist', $uniq_id );
						$attributes['list'] = $datalist_id;

						$datalist_markup = sprintf( '<datalist id="%s">', $datalist_id );
						$datalist_items  = $field->get_attribute( 'html_datalist' );
						$is_numeric      = wp_is_numeric_array( $datalist_items );
						foreach ( $datalist_items as $value => $label ) {
							if ( $is_numeric ) {
								$datalist_markup .= sprintf( '<option value="%s"></option>', $label );
							} else {
								$datalist_markup .= sprintf( '<option value="%s" label="%s"></option>', $value, $label );
							}
						}
						$datalist_markup .= '</datalist>';
					}

					$suffix_markup = $has_field_suffix ? sprintf( '<span class="input-suffix">%s</span>', $field_suffix ) : '';

					$wrapper_classes = array(
						'input-container',
						'has-suffix' => $has_field_suffix,
					);

					$tooltip_markup  = $field->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $field->get_attribute( 'tooltip' ) ) ) : '';
					$required_markup = $field->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';

					$inputs[] = sprintf( '<ul %s class="input-wrapper text-input-wrapper">', $conditional_attr );
					$inputs[] = sprintf( '<li class="group-field-label"><label for="%s"><span class="input-label-wrapper"><span class="input-label">%s</span> %s %s</span></label></li><li class="group-field-inputs"><span class="%s"><span class="input-field"><input %s /></span> %s </span>%s</li>', esc_attr( $uniq_id ), esc_html( $field_title ), $required_markup, $tooltip_markup, esc_attr( $this->get_css_classes( $wrapper_classes ) ), $this->get_html_attributes( $attributes, $field_attributes ), $suffix_markup, $datalist_markup );
					$inputs[] = '</ul>';
				}
			}

			return sprintf( '<fieldset class="group-input-fields"><legend class="screen-reader-text">%s</legend>%s</fieldset>', esc_html( $group_title ), implode( '', $inputs ) );
		}

		// =====================================================================
		// REST API Methods
		// =====================================================================

		/**
		 * Get REST API schema type for this field.
		 *
		 * Determines the appropriate JSON Schema primitive type based on the
		 * field type and configuration.
		 *
		 * @since 1.0.0
		 *
		 * @return string The JSON Schema type: 'string', 'number', 'array', or 'object'.
		 *
		 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types
		 *
		 * @example
		 *          ```php
		 *          $type = $field->get_rest_type();
		 *          // Returns: 'string' for text fields
		 *          // Returns: 'number' for number fields
		 *          // Returns: 'array' for multi-select or checkbox groups
		 *          // Returns: 'object' for group fields
		 *          ```
		 */
		public function get_rest_type(): string {

			$type        = $this->get_type();
			$options     = $this->get_options();
			$is_single   = $this->is_empty_array( $options );
			$is_multiple = $this->has_attribute( 'multiple' );

			switch ( $type ) {
				case 'textarea':
				case 'email':
				case 'url':
				case 'text':
				case 'regular-text':
				case 'color':
				case 'unit':
				case 'small-text':
				case 'tiny-text':
				case 'large-text':
				case 'radio':
				case 'code':
					return 'string';
				case 'number':
					return 'number';
				case 'toggle':
				case 'checkbox':
					return $is_single ? 'string' : 'array';
				case 'select2':
				case 'select':
				case 'wc-enhanced-select':
					return $is_multiple ? 'array' : 'string';
				case 'group':
					return 'object';
			}

			return 'string';
		}

		// =====================================================================
		// Markup Generation Methods
		// =====================================================================

		/**
		 * Get the input markup based on field type.
		 *
		 * Main dispatcher method that calls the appropriate input rendering
		 * method based on the field type.
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML input markup.
		 *
		 * @see Field::text_input() For text-type fields.
		 * @see Field::textarea_input() For textarea fields.
		 * @see Field::check_input() For checkbox/radio/toggle fields.
		 * @see Field::select_input() For select fields.
		 * @see Field::group_input() For group fields.
		 * @see Field::custom_input() For custom field types.
		 *
		 * @example
		 *          ```php
		 *          $markup = $field->get_input_markup();
		 *          ```
		 *
		 * @example Supported types:
		 *          - text, regular-text, code, range, search, url, password
		 *          - unit (number with unit selector)
		 *          - color, number, small-text, tiny-text, large-text
		 *          - radio, checkbox, toggle
		 *          - select, select2, wc-enhanced-select
		 *          - group
		 *          - textarea
		 *          - custom types (via custom_input)
		 */
		public function get_input_markup(): string {
			$type = $this->get_type();

			switch ( $type ) {
				case 'text':
				case 'regular-text':
				case 'code':
				case 'range':
				case 'search':
				case 'url':
				case 'password':
					return $this->text_input();
				case 'unit':
					return $this->unit_input();
				case 'color':
				case 'number':
				case 'small-text':
					return $this->text_input( 'small-text' );
				case 'tiny-text':
					return $this->text_input( 'tiny-text' );
				case 'large-text':
					return $this->text_input( 'large-text' );
				case 'radio':
				case 'checkbox':
				case 'toggle':
					return $this->check_input();
				case 'select':
				case 'select2':
				case 'wc-enhanced-select':
					return $this->select_input();
				case 'group':
					return $this->group_input();
				case 'textarea':
					return $this->textarea_input();
				default:
					return $this->custom_input();
			}
		}

		/**
		 * Generate field label markup.
		 *
		 * Creates the HTML label element for the field, including required
		 * indicator and tooltip if configured.
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML label markup.
		 *
		 * @see Field::get_tooltip_markup() For tooltip generation.
		 * @see Field::get_required_markup() For required indicator.
		 *
		 * @example
		 *          ```php
		 *          $label = $field->get_label_markup();
		 *          // Returns: '<label for="field_id"><span class="input-label-wrapper">...</span></label>'
		 *          ```
		 */
		public function get_label_markup(): string {

			$id             = $this->get_id();
			$title          = sprintf( '<span class="input-label">%s</span>', $this->get_title() );
			$type           = $this->get_type();
			$tooltip_markup = $this->get_tooltip_markup();

			if ( in_array( $type, $this->group_inputs(), true ) ) {
				return sprintf( '<span class="input-label-wrapper">%s %s</span>', $title, $tooltip_markup );
			}

			$required_markup = $this->get_required_markup();

			return sprintf( '<label for="%s"><span class="input-label-wrapper">%s %s %s</span></label>', esc_attr( $id ), $title, $required_markup, $tooltip_markup );
		}

		/**
		 * Generate field description markup.
		 *
		 * Creates the HTML paragraph element for the field description if configured.
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML description markup or empty string.
		 *
		 * @example
		 *          ```php
		 *          $desc = $field->get_description_markup();
		 *          // Returns: '<p class="description" id="field_id-description">Help text here</p>'
		 *          ```
		 */
		public function get_description_markup(): string {
			$id = $this->get_id();

			return $this->has_attribute( 'description' ) ? sprintf( '<p class="description" id="%s-description">%s</p>', esc_attr( $id ), wp_kses_post( $this->get_attribute( 'description' ) ) ) : '';
		}

		/**
		 * Generate tooltip markup.
		 *
		 * Creates the HTML span element for displaying a tooltip if configured.
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML tooltip markup or empty string.
		 *
		 * @example
		 *          ```php
		 *          $tooltip = $field->get_tooltip_markup();
		 *          // Returns: '<span data-storepress-tooltip="Help text"><span class="help-tooltip"></span></span>'
		 *          ```
		 */
		public function get_tooltip_markup(): string {
			return $this->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $this->get_attribute( 'tooltip' ) ) ) : '';
		}

		/**
		 * Generate required field indicator markup.
		 *
		 * Creates the HTML span element showing the required asterisk if field is required.
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML required indicator or empty string.
		 *
		 * @example
		 *          ```php
		 *          $required = $field->get_required_markup();
		 *          // Returns: '<span class="required">*</span>' if required
		 *          ```
		 */
		public function get_required_markup(): string {
			return $this->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';
		}

		/**
		 * Generate datalist element markup.
		 *
		 * Creates the HTML5 datalist element for autocomplete suggestions if configured.
		 *
		 * @since 1.0.0
		 *
		 * @return string The HTML datalist markup or empty string.
		 *
		 * @see Field::get_datalist_id() For the datalist ID.
		 *
		 * @example
		 *          ```php
		 *          $datalist = $field->get_datalist_markup();
		 *          // Returns: '<datalist id="field_id-datalist"><option value="opt1">...</datalist>'
		 *          ```
		 */
		public function get_datalist_markup(): string {

			$type = $this->get_type();

			if ( in_array( $type, array( 'select', 'checkbox', 'radio' ), true ) ) {
				return '';
			}

			$datalist_id         = $this->get_datalist_id();
			$datalist_attributes = $this->get_attribute( 'html_datalist', array() );

			$datalist_markup = '';
			if ( ! $this->is_empty_array( $datalist_attributes ) ) {
				$datalist_markup = sprintf( '<datalist id="%s">', $datalist_id );
				$is_numeric      = wp_is_numeric_array( $datalist_attributes );
				foreach ( $datalist_attributes as $value => $label ) {
					if ( $is_numeric ) {
						$datalist_markup .= sprintf( '<option value="%s"></option>', $label );
					} else {
						$datalist_markup .= sprintf( '<option value="%s" label="%s"></option>', $value, $label );
					}
				}
				$datalist_markup .= '</datalist>';
			}

			return $datalist_markup;
		}

		/**
		 * Get conditional display attributes.
		 *
		 * Returns HTML attributes for conditional field visibility based on
		 * other field values.
		 *
		 * @since 1.0.0
		 *
		 * @return array<string, mixed> Conditional attributes array or empty array.
		 *
		 * @example
		 *          ```php
		 *          $attrs = $field->conditional_attribute();
		 *          // Returns: array( 'inert' => true, 'data-storepress-conditional-field' => array(...) )
		 *          ```
		 */
		public function conditional_attribute(): array {
			$has_condition = $this->has_attribute( 'condition' );

			if ( ! $has_condition ) {
				return array();
			}

			$condition = $this->get_attribute( 'condition', array() );

			return array(
				'inert'                             => true,
				'data-storepress-conditional-field' => $condition,
			);
		}

		// =====================================================================
		// Display Method
		// =====================================================================

		/**
		 * Display the complete field row markup.
		 *
		 * Generates the full table row HTML for the field including label,
		 * input, datalist, description, and conditional attributes.
		 *
		 * @since 1.0.0
		 *
		 * @return string The complete HTML table row markup for the field.
		 *
		 * @see Field::get_label_markup() For label generation.
		 * @see Field::get_input_markup() For input generation.
		 * @see Field::get_description_markup() For description generation.
		 * @see Field::get_datalist_markup() For datalist generation.
		 * @see Field::conditional_attribute() For conditional display.
		 *
		 * @example
		 *          ```php
		 *          echo $field->display();
		 *          // Outputs: '<tr><th scope="row">...</th><td>...</td></tr>'
		 *          ```
		 *
		 * @example Full width field (no label column):
		 *          ```php
		 *          // With 'full_width' => true in field config
		 *          echo $field->display();
		 *          // Outputs: '<tr><td colspan="2" class="td-full">...</td></tr>'
		 *          ```
		 */
		public function display(): string {
			$label            = $this->get_label_markup();
			$description      = $this->get_description_markup();
			$input            = $this->get_input_markup();
			$full_width       = $this->has_attribute( 'full_width' );
			$datalist         = $this->get_datalist_markup();
			$conditional_attr = $this->get_html_attributes( $this->conditional_attribute() );

			// <span class="help-tooltip"></span>
			// <span class="help-modal"></span>

			$get_tag = $this->get_attribute( 'add_tag', null );

			$data_tag_attrs = array(
				'data-tag' => is_string( $get_tag ) ? $get_tag : $this->get_var( $get_tag[0] ),
			);

			if ( $get_tag && wp_is_numeric_array( $get_tag ) ) {
				$data_tag_attrs['style']                            = array();
				$data_tag_attrs['style']['--_tag_background-color'] = $this->get_var( $get_tag[1] );
				$data_tag_attrs['style']['--_tag_text-color']       = $this->get_var( $get_tag[2] );
			}

			$column_data_attrs = array();

			$row_markup_start = sprintf( '<tr %s>', $conditional_attr );
			$row_markup_end   = '</tr>';

			if ( $full_width ) {

				$column_data_attrs['colspan'] = '2';
				$column_data_attrs['class']   = 'td-full';

				return sprintf(
					'%s<td %s>%s %s %s</td>%s',
					$row_markup_start,
					$this->get_html_attributes( $data_tag_attrs, $column_data_attrs ),
					$input,
					$datalist,
					$description,
					$row_markup_end
				);
			}

			return sprintf(
				'%s<th scope="row" %s>%s</th><td %s>%s %s %s</td>%s',
				$row_markup_start,
				$this->get_html_attributes( $data_tag_attrs ),
				$label,
				$this->get_html_attributes( $column_data_attrs ),
				$input,
				$datalist,
				$description,
				$row_markup_end
			);
		}
	}
}
