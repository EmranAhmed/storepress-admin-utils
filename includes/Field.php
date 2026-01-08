<?php
	/**
	 * Admin Settings Field Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

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
		 * @param array<string, mixed> $values   Settings values. Default is: array().
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
		 * @return bool|string|numeric|array<int|string, mixed>|null
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
		 * Get datalist id.
		 *
		 * @return string
		 */
		public function get_datalist_id(): string {
			return sprintf( '%s-datalist', $this->get_id() );
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
		 * @param string|string[] $classes       Class names.
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
		 * Get field css class.
		 *
		 * @param string $default_value default class.
		 *
		 * @return bool|string|string[]|null
		 */
		public function get_css_class( string $default_value = '' ) {
			return $this->get_attribute( 'class', $default_value );
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
		 * Has field suffix.
		 *
		 * @return bool
		 */
		public function has_suffix(): bool {
			return $this->has_attribute( 'suffix' );
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
		 * @param string                    $attribute     Attribute name.
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
			return array( 'radio', 'checkbox', 'toggle', 'group' );
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
			wp_trigger_error( __METHOD__, $message );

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
		 * Unit input markup.
		 *
		 * @param string $css_class Input CSS class.
		 *
		 * @return string
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
		 * Parse string unit to array.
		 *
		 * @param string $value unit string.
		 *
		 * @return string[]
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
		 * Generate unit input markup.
		 *
		 * @param string $id unit value.
		 * @param string $value unit value.
		 *
		 * @return string
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

		/**
		 * Available units.
		 *
		 * @return string[]
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
		 * TextArea Input Markup.
		 *
		 * @param string $css_class TextArea css class.
		 *
		 * @return string
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

		/**
		 * Checkbox Input
		 *
		 * @return string
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
				$message = sprintf( 'Input Field: "%s". Title: "%s" need options to choose. "option"=>["key"=>"value"]', $id, $title );
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
		 * Select Input box.
		 *
		 * @return string
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
			 * @var array<string, mixed> $group_fields $group_fields
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
		 * @param string                    $field_id      Field ID.
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

		/**
		 * Get REST Type Primitive Types.
		 *
		 * @return string
		 * @see     https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types
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

		/**
		 * Label Markup.
		 *
		 * @return string
		 * @TODO Label based on input
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
		 * Get field type alias.
		 *
		 * @return string[]
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
		 * Get Input Markups
		 *
		 * @return string
		 * @TODO   Add More Fields
		 * @see    Settings::sanitize_fields()
		 * @example: text, unit, code, password, range, search, url, color, number, code, textarea, select, select2, wc-enhanced-select, regular-text, small-text, tiny-text, large-text, color
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
		 * Get field description markup.
		 *
		 * @return string
		 */
		public function get_description_markup(): string {
			$id = $this->get_id();

			return $this->has_attribute( 'description' ) ? sprintf( '<p class="description" id="%s-description">%s</p>', esc_attr( $id ), wp_kses_post( $this->get_attribute( 'description' ) ) ) : '';
		}

		/**
		 * Get field tooltip markup.
		 *
		 * @return string
		 */
		public function get_tooltip_markup(): string {
			return $this->has_attribute( 'tooltip' ) ? sprintf( '<span data-storepress-tooltip="%s"><span class="help-tooltip"></span></span>', esc_html( $this->get_attribute( 'tooltip' ) ) ) : '';
		}

		/**
		 * Get field required markup.
		 *
		 * @return string
		 */
		public function get_required_markup(): string {
			return $this->has_attribute( 'required' ) ? '<span class="required">*</span>' : '';
		}

		/**
		 * Get field description markup.
		 *
		 * @return string
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
		 * Conditional attributes.
		 *
		 * @return array<string, mixed>
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

		/**
		 * Display generated field
		 *
		 * @return string
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
