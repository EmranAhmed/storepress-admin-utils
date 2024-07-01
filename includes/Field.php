<?php
	/**
	 * Admin Settings Field Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Field' ) ) {
	/**
	 * Admin Settings Field Class.
	 *
	 * @name Field
	 */
	class Field {

		use Common;

		/**
		 * Single field.
		 *
		 * @var string[]|array<string, mixed>
		 */
		private array $field;
		/**
		 * Setting Object.
		 *
		 * @var Settings
		 */
		private Settings $settings;

		/**
		 * Setting ID.
		 *
		 * @var string
		 */
		private string $settings_id;

		/**
		 * Construct Field
		 *
		 * @param string[]|array<string, mixed> $field field Array.
		 */
		public function __construct( array $field ) {
			$this->field = $field;
		}

		/**
		 * Add Settings.
		 *
		 * @param Settings             $settings Settings Object.
		 * @param array<string, mixed> $values Settings values. Default is: array().
		 *
		 * @return self
		 */
		public function add_settings( Settings $settings, array $values = array() ): Field {
			$this->settings = $settings;

			if ( $this->is_empty_array( $values ) ) {
				$this->populate_option_values();
			} else {
				$this->populate_from_values( $values );
			}

			$this->field['show_in_rest'] = $this->get_attribute( 'show_in_rest', true );

			return $this;
		}

		/**
		 * Populate all values.
		 *
		 * @return void
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
		 * Populate from passed values.
		 *
		 * @param array<string, mixed> $values Values.
		 *
		 * @return void
		 */
		private function populate_from_values( array $values ): void {

			$id    = $this->get_id();
			$value = $values[ $id ] ?? null;
			$this->add_value( $value );
		}

		/**
		 * Get Settings Object.
		 *
		 * @return Settings
		 */
		public function get_settings(): Settings {
			return $this->settings;
		}

		/**
		 * Add value.
		 *
		 * @param string|string[]|numeric|bool|null $value Pass value.
		 *
		 * @return self
		 */
		public function add_value( $value ): Field {
			$this->field['value'] = $value;

			return $this;
		}

		/**
		 * Get settings id.
		 *
		 * @return string
		 */
		public function get_settings_id(): string {
			return $this->settings_id ?? $this->get_settings()->get_settings_id();
		}

		/**
		 * Add settings id.
		 *
		 * @param string $settings_id Settings ID.
		 *
		 * @return self
		 */
		public function add_settings_id( string $settings_id = '' ): self {
			$this->settings_id = $settings_id;

			return $this;
		}

		/**
		 * Get default value.
		 *
		 * @return string|string[]|bool|numeric|null
		 */
		public function get_default_value() {
			return $this->get_attribute( 'default' );
		}

		/**
		 * Generate setting name
		 *
		 * @param boolean $is_group Pass group name to get name based on group.
		 *
		 * @return string
		 */
		public function get_name( bool $is_group = false ): string {
			$id         = $this->get_id();
			$setting_id = $this->get_settings_id();

			return $is_group ? sprintf( '%s[%s][]', $setting_id, $id ) : sprintf( '%s[%s]', $setting_id, $id );
		}

		/**
		 * Generate private name.
		 *
		 * @return string
		 */
		public function get_private_name(): string {
			$id         = $this->get_id();
			$setting_id = $this->get_settings_id();

			return sprintf( '_%s__%s', $setting_id, $id );
		}

		/**
		 * Check field is private or not.
		 *
		 * @return bool
		 */
		public function is_private(): bool {
			return true === $this->get_attribute( 'private', false );
		}

		/**
		 * Get value
		 *
		 * @param bool|string|string[]|null $default_value Default value.
		 *
		 * @return bool|string|string[]|null
		 */
		public function get_value( $default_value = null ) {
			return $this->get_attribute( 'value', $default_value ?? $this->get_default_value() );
		}

		/**
		 * Get available options
		 *
		 * @return string[]|array<string, string>
		 */
		public function get_options(): array {
			return $this->get_attribute( 'options', array() );
		}

		/**
		 * Get field type.
		 *
		 * @return string
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
		 * Get field raw type
		 *
		 * @return string
		 */
		public function get_raw_type(): string {
			return $this->get_attribute( 'type', 'text' );
		}

		/**
		 * Check field has custom sanitize function.
		 *
		 * @return bool
		 */
		public function has_sanitize_callback(): bool {
			return $this->has_attribute( 'sanitize_callback' );
		}

		/**
		 * Check field has custom escaping function.
		 *
		 * @return bool
		 */
		public function has_escape_callback(): bool {
			return $this->has_attribute( 'escape_callback' );
		}

		/**
		 * Sanitize data before insert to database. Clean incoming data.
		 *
		 * @return string
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
		 * Escaping function. escape data before display from database. Escape data on output.
		 *
		 * @return string
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

		/**
		 * Check is group type.
		 *
		 * @return bool
		 */
		public function is_type_group(): bool {
			return 'group' === $this->get_type();
		}

		/**
		 * Get field id.
		 *
		 * @return string|null
		 */
		public function get_id(): ?string {
			return $this->get_attribute( 'id' );
		}

		/**
		 * Get available field sizes.
		 *
		 * @return string[]
		 */
		public function get_field_size_css_classes(): array {
			return array( 'regular-text', 'small-text', 'tiny-text', 'large-text' );
		}

		/**
		 * Prepare field classes.
		 *
		 * @param string|string[] $classes Class names.
		 * @param string|string[] $default_value Default value.
		 *
		 * @return string[]
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
			 *
			 * @var string[] $default_classnames
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
		 * Get field class.
		 *
		 * @return string|string[]
		 */
		public function get_css_class() {
			return $this->get_attribute( 'class', '' );
		}

		/**
		 * Get field suffix.
		 *
		 * @return string|null
		 */
		public function get_suffix(): ?string {
			return $this->get_attribute( 'suffix' );
		}

		/**
		 * Get field title.
		 *
		 * @return string|null
		 */
		public function get_title(): ?string {
			return $this->get_attribute( 'title' );
		}

		/**
		 * Get field data.
		 *
		 * @return string[]|array<string, mixed>
		 */
		public function get_field(): array {
			return $this->field;
		}

		/**
		 * Check has attribute.
		 *
		 * @param string $attribute Attribute name to check.
		 *
		 * @return bool
		 */
		public function has_attribute( string $attribute ): bool {
			$field = $this->get_field();

			return isset( $field[ $attribute ] );
		}

		/**
		 * Check field shown in rest api.
		 *
		 * @return bool
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

		/**
		 * Get attribute.
		 *
		 * @param string                    $attribute Attribute name.
		 * @param string|string[]|null|bool $default_value Default value. Default null.
		 *
		 * @return string|string[]|null|bool
		 */
		public function get_attribute( string $attribute, $default_value = null ) {
			$field = $this->get_field();

			return $field[ $attribute ] ?? $default_value;
		}

		/**
		 * Get group inputs.
		 *
		 * @return string[]
		 */
		public function group_inputs(): array {
			return array( 'radio', 'checkbox', 'group' );
		}

		/**
		 * Get HTML Attributes.
		 *
		 * @param array<string, mixed> $attrs Attributes.
		 * @param array<string, mixed> $additional_attrs Additional attributes. Default array().
		 *
		 * @return string
		 */
		public function get_input_attributes( array $attrs, array $additional_attrs = array() ): string {

			$attributes = wp_parse_args( $additional_attrs, $attrs );

			return $this->get_html_attributes( $attributes );
		}

		/**
		 * Creating custom input field.
		 *
		 * @return string
		 */
		public function custom_input(): string {

			$type = $this->get_type();

			if ( method_exists( $this->get_settings(), 'custom_field' ) ) {
				return $this->get_settings()->custom_field( $this );
			}

			$message = sprintf( 'Field: "%s" not implemented. Please add "Settings::custom_field" method to implement.', $type );
			wp_trigger_error( '', $message );

			return '';
		}

		/**
		 * Text input markup.
		 *
		 * @param string $css_class Input CSS class.
		 *
		 * @return string
		 */
		public function text_input( string $css_class = 'regular-text' ): string {

			$id                    = $this->get_id();
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

			$attributes = array(
				'id'    => $id,
				'type'  => $type,
				'class' => $this->prepare_classes( $class, $system_class ),
				'name'  => $this->get_name(),
				'value' => $value,
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

			return sprintf( '<input %s /> %s', $this->get_input_attributes( $attributes, $additional_attributes ), $this->get_suffix() );
		}

		/**
		 * TextArea Input Markup.
		 *
		 * @param string $css_class TextArea css class.
		 *
		 * @return string
		 */
		public function textarea_input( string $css_class = 'regular-text' ): string {

			$id                    = $this->get_id();
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

			return sprintf( '<textarea %s>%s</textarea>', $this->get_input_attributes( $attributes, $additional_attributes ), $value );
		}

		/**
		 * Checkbox Input
		 *
		 * @return string
		 */
		public function check_input(): string {

			$id      = $this->get_id();
			$type    = $this->get_type();
			$title   = $this->get_title();
			$name    = $this->get_name();
			$value   = $this->get_value();
			$options = $this->get_options();

			// Group checkbox. Options will be an array.
			if ( 'checkbox' === $type && count( $options ) > 1 ) {
				$name = $this->get_name( true );
			}

			// Single checkbox. Option will be string.
			if ( 'checkbox' === $type && $this->is_empty_array( $options ) ) {
				$options = array( 'yes' => $title );
			}

			// Check radio input have options declared.
			if ( 'radio' === $type && $this->is_empty_array( $options ) ) {
				$message = sprintf( 'Input Field: "%s". Title: "%s" need options to choose. "option"=>["key"=>"value"]', $id, $title );
				wp_trigger_error( '', $message );

				return '';
			}

			$inputs = array();

			/**
			 * Group Options.
			 *
			 * @var array<string, string> $options
			 */
			foreach ( $options as $option_key => $option_value ) {
				$uniq_id = sprintf( '%s-%s', $id, $option_key );

				$attributes = array(
					'id'      => $uniq_id,
					'type'    => $type,
					'name'    => $name,
					'value'   => esc_attr( $option_key ),
					'checked' => ( 'checkbox' === $type ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ), true ) : $value === $option_key,
				);

				$inputs[] = sprintf( '<label for="%s"><input %s /><span>%s</span></label>', esc_attr( $uniq_id ), $this->get_input_attributes( $attributes ), esc_html( $option_value ) );
			}

			return sprintf( '<fieldset><legend class="screen-reader-text">%s</legend>%s</fieldset>', $title, implode( '<br />', $inputs ) );
		}

		/**
		 * Select Input box.
		 *
		 * @return string
		 */
		public function select_input(): string {

			$id                    = $this->get_id();
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

			$attributes = array(
				'id'       => $id,
				'type'     => 'select',
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
				$selected = ( $is_multiple ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ), true ) : $value === $option_key;
				$inputs[] = sprintf( '<option %s value="%s"><span>%s</span></option>', $this->get_input_attributes( array( 'selected' => $selected ) ), esc_attr( $option_key ), esc_html( $option_value ) );
			}

			return sprintf( '<select %s>%s</select>', $this->get_input_attributes( $attributes, $additional_attributes ), implode( '', $inputs ) );
		}

		/**
		 * Get group fields.
		 *
		 * @return Field[]
		 */
		public function get_group_fields(): array {

			$name         = $this->get_name();
			$group_value  = $this->get_value( array() );
			$group_fields = $this->get_attribute( 'fields', array() );

			$fields = array();

			/**
			 * Group Filed object array
			 *
			 * @var array<string, string|string[]> $group_fields $group_fields
			 */

			foreach ( $group_fields as $field ) {
				$fields[] = ( new Field( $field ) )->add_settings( $this->get_settings(), $group_value )->add_settings_id( $name );
			}

			return $fields;
		}

		/**
		 * Get REST API Group values.
		 *
		 * @return array<string, string|string[]>
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
		 * Get REST API Value.
		 *
		 * @return mixed
		 */
		public function get_rest_value() {
			$escape_callback = $this->get_escape_callback();

			return map_deep( $this->get_value(), $escape_callback );
		}

		/**
		 * Get Group Values.
		 *
		 * @return array<string, mixed>
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
		 * Get Group value.
		 *
		 * @param string                    $field_id Field ID.
		 * @param bool|null|string|string[] $default_value Default group value.
		 *
		 * @return bool|null|string|string[]
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
		 * Group Input Markup.
		 *
		 * @param string $css_class Css Class.
		 *
		 * @return string
		 */
		public function group_input( string $css_class = 'small-text' ): string {

			$id           = $this->get_id();
			$title        = $this->get_title();
			$group_fields = $this->get_group_fields();

			$inputs = array();

			foreach ( $group_fields as $field ) {

				$field_id          = $field->get_id();
				$uniq_id           = sprintf( '%s-%s__group', $id, $field_id );
				$field_title       = $field->get_title();
				$field_type        = $field->get_type();
				$field_name        = $field->get_name();
				$field_options     = $field->get_options();
				$field_placeholder = $field->get_attribute( 'placeholder' );
				$field_required    = $field->has_attribute( 'required' );
				$field_suffix      = $field->get_suffix();
				$field_classes     = $this->prepare_classes( $field->get_css_class(), $css_class );
				$escape_callback   = $this->get_escape_callback();
				$field_value       = map_deep( $field->get_value(), $escape_callback );
				$field_attributes  = $field->get_attribute( 'html_attributes', array() );

				$attributes = array(
					'id'          => $uniq_id,
					'type'        => $field_type,
					'class'       => $field_classes,
					'name'        => $field_name,
					'value'       => $field_value,
					'placeholder' => $field_placeholder,
					'required'    => $field_required,
				);

				// Group checkbox name.
				if ( 'checkbox' === $field_type && count( $field_options ) > 1 ) {
					$attributes['name'] = $field->get_name( true );
				}

				if ( in_array( $field_type, $this->group_inputs(), true ) ) {

					$attributes['class'] = array();

					// Single checkbox.
					if ( 'checkbox' === $field_type && $this->is_empty_array( $field_options ) ) {
						$attributes['value']   = 'yes';
						$attributes['checked'] = 'yes' === $field_value;

						$inputs[] = sprintf( '<p class="input-wrapper"><label for="%s"><input %s /><span>%s</span></label></p>', esc_attr( $uniq_id ), $this->get_input_attributes( $attributes ), esc_html( $field_title ) );

						continue;
					}

					// Checkbox and Radio.
					$inputs[] = '<ul class="input-wrapper">';
					/**
					 * Group Options.
					 *
					 * @var array<string, string> $field_options
					 */
					foreach ( $field_options as $option_key => $option_value ) {
						$uniq_id               = sprintf( '%s-%s-%s__group', $id, $field_id, $option_key );
						$attributes['value']   = esc_attr( $option_key );
						$attributes['checked'] = is_array( $field_value ) ? in_array( $option_key, $field_value, true ) : $option_key == $field_value;
						$attributes['id']      = $uniq_id;
						$inputs[]              = sprintf( '<li><label for="%s"><input %s /><span>%s</span></label></li>', esc_attr( $uniq_id ), $this->get_input_attributes( $attributes ), esc_html( $option_value ) );
					}
					$inputs[] = '</ul>';

				} elseif ( 'textarea' === $field_type ) {
					// Input box.
						$attributes['value'] = false;
						$inputs[]            = sprintf( '<p class="input-wrapper"><label for="%s"><span>%s</span></label> <textarea %s>%s</textarea></p>', esc_attr( $uniq_id ), esc_html( $field_title ), $this->get_input_attributes( $attributes, $field_attributes ), $field_value );
				} else {
					$inputs[] = sprintf( '<p class="input-wrapper"><label for="%s"><span>%s</span></label> <input %s /> %s</p>', esc_attr( $uniq_id ), esc_html( $field_title ), $this->get_input_attributes( $attributes, $field_attributes ), esc_html( $field_suffix ) );
				}
			}

			return sprintf( '<fieldset class="group-input-wrapper"><legend class="screen-reader-text">%s</legend>%s</fieldset>', esc_html( $title ), implode( '', $inputs ) );
		}

		/**
		 * Get REST Type Primitive Types.
		 *
		 * @return string
		 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types
		 * @example array( 'number', 'integer', 'string', 'boolean', 'array', 'object' )
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
				case 'small-text':
				case 'tiny-text':
				case 'large-text':
				case 'radio':
				case 'code':
					return 'string';
				case 'number':
					return 'number';
				case 'checkbox':
					return $is_single ? 'string' : 'array';
				case 'select2':
				case 'select':
					return $is_multiple ? 'array' : 'string';
				case 'group':
					return 'object';
			}

			return 'string';
		}

		/**
		 * Label Markup.
		 *
		 * @return string
		 * @todo Label based on input
		 */
		public function get_label_markup(): string {

			$id    = $this->get_id();
			$title = $this->get_title();
			$type  = $this->get_type();

			if ( in_array( $type, $this->group_inputs(), true ) ) {
				return $title;
			}
			$required_markup = '';
			if ( $this->has_attribute( 'required' ) ) {
				$required_markup = '<span class="required">*</span>';
			}

			return sprintf( '<label for="%s">%s %s</label>', esc_attr( $id ), esc_html( $title ), $required_markup );
		}

		/**
		 * Get field type alias.
		 *
		 * @return string[]
		 */
		public function get_type_alias(): array {

			return array(
				'tiny-text'    => 'text',
				'small-text'   => 'text',
				'regular-text' => 'text',
				'large-text'   => 'text',
				'code'         => 'text',
				'select2'      => 'select',
			);
		}

		/**
		 * Get Input Markups
		 *
		 * @return string
		 * @todo Add More Fields
		 * @see  Settings::sanitize_fields()
		 * @example: input, code, textarea, select, select2, regular-text, small-text, tiny-text, large-text, color
		 */
		public function get_input_markup(): string {
			$type = $this->get_type();

			switch ( $type ) {
				case 'text':
				case 'regular-text':
				case 'code':
					return $this->text_input();
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
					return $this->check_input();
				case 'select':
				case 'select2':
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
		 * Get field description markup.
		 *
		 * @return string
		 */
		public function get_description_markup(): string {
			$id = $this->get_id();

			return $this->has_attribute( 'description' ) ? sprintf( '<p class="description" id="%s-description">%s</p>', esc_attr( $id ), wp_kses_post( $this->get_attribute( 'description' ) ) ) : '';
		}

		/**
		 * Display generated field
		 *
		 * @return string
		 */
		public function display(): string {
			$label       = $this->get_label_markup();
			$description = $this->get_description_markup();
			$input       = $this->get_input_markup();

			$full_width = $this->get_attribute( 'full_width', false );

			// <span class="help-tip"></span>
			if ( $full_width ) {
				return sprintf( '<tr><td colspan="2" class="td-full">%s %s</td></tr>', $input, $description );
			}

			return sprintf( '<tr><th scope="row">%s </th><td>%s %s</td></tr>', $label, $input, $description );
		}
	}
}
