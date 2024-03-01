<?php
	/**
	 * Admin Settings Field
	 *
	 * @package    StorePress/AdminUtils
	 * @version    1.0
	 */

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );


if ( ! class_exists( '\StorePress\AdminUtils\Field' ) ) {
	/**
	 * Admin Settings Field
	 *
	 * @name Field
	 */
	class Field {

		/**
		 * @var array
		 */
		private array $field;
		/**
		 * @var Settings
		 */
		private Settings $settings;

		/**
		 * @var string
		 */
		private string $settings_id;

		/**
		 * Field
		 *
		 * @param array $field field Array.
		 */
		public function __construct( array $field ) {
			$this->field = $field;
		}

		/***
		 * @param Settings $settings
		 * @param array    $values
		 *
		 * @return self
		 */
		public function add_settings( Settings $settings, array $values = array() ): Field {
			$this->settings = $settings;

			if ( empty( $values ) ) {
				$this->populate_option_values();
			} else {
				$this->populate_from_values( $values );
			}

			$this->field['show_in_rest'] = $this->get_attribute( 'show_in_rest', true );

			return $this;
		}

		/**
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
		 * @param array $values
		 *
		 * @return void
		 */
		private function populate_from_values( array $values ): void {

			$id    = $this->get_id();
			$value = $values[ $id ] ?? null;
			$this->add_value( $value );
		}

		/***
		 * @return Settings
		 */
		public function get_settings(): Settings {
			return $this->settings;
		}

		/**
		 * @param mixed $value
		 *
		 * @return self
		 */
		public function add_value( $value ): Field {
			$this->field['value'] = $value;

			return $this;
		}

		/**
		 * @return string
		 */
		public function get_settings_id(): string {
			return $this->settings_id ?? $this->get_settings()->get_settings_id();
		}

		/**
		 * @param string $settings_id
		 *
		 * @return self
		 */
		public function add_settings_id( string $settings_id = '' ): self {
			$this->settings_id = $settings_id;

			return $this;
		}

		/**
		 * @return mixed|null
		 */
		public function get_default_value() {
			return $this->get_attribute( 'default' );
		}

		/**
		 * @param boolean $is_group
		 *
		 * @return string
		 */
		public function get_name( bool $is_group = false ): string {
			$id         = $this->get_id();
			$setting_id = $this->get_settings_id();

			return $is_group ? sprintf( '%s[%s][]', $setting_id, $id ) : sprintf( '%s[%s]', $setting_id, $id );
		}

		/**
		 * @return string
		 */
		public function get_private_name(): string {
			$id         = $this->get_id();
			$setting_id = $this->get_settings_id();

			return sprintf( '_%s__%s', $setting_id, $id );
		}

		/**
		 * @return bool
		 */
		public function is_private(): bool {
			return true === $this->get_attribute( 'private', false );
		}

		/**
		 * @param $default
		 *
		 * @return mixed|null
		 */
		public function get_value( $default = null ) {
			return $this->get_attribute( 'value', $default ?? $this->get_default_value() );
		}

		/**
		 * @return array
		 */
		public function get_options(): array {
			return $this->get_attribute( 'options', array() );
		}

		/**
		 * @return string
		 */
		public function get_type(): string {
			$type  = $this->get_raw_type();
			$alias = $this->get_type_alias();
			$keys  = array_keys( $alias );

			if ( in_array( $type, $keys ) ) {
				return $alias[ $type ];
			}

			return $type;
		}

		/**
		 * @return string
		 */
		public function get_raw_type(): string {
			return $this->get_attribute( 'type', 'text' );
		}

		/**
		 * @return bool
		 */
		public function has_sanitize_callback(): bool {
			return $this->has_attribute( 'sanitize_callback' );
		}

		/**
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
					break;
				case 'url':
					return 'sanitize_url';
					break;
				case 'textarea':
					return 'sanitize_textarea_field';
					break;
				case 'color':
					return 'sanitize_hex_color';
					break;
				case 'number':
					return 'absint';
					break;
				default:
					return 'sanitize_text_field';
					break;
			}
		}

		/**
		 * Escape data before display from database. Escape data on output.
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
					break;
				case 'url':
					return 'esc_url';
					break;
				case 'textarea':
					return 'esc_textarea';
					break;
				case 'color':
					return 'sanitize_hex_color';
					break;
				case 'number':
					return 'absint';
					break;
				default:
					return 'esc_html';
					break;
			}
		}

		/**
		 * @return bool
		 */
		public function is_type_group(): bool {
			return 'group' === $this->get_type();
		}

		/**
		 * @return string|null
		 */
		public function get_id(): ?string {
			return $this->get_attribute( 'id' );
		}

		public function get_field_size_css_classes(): array {
			return array( 'regular-text', 'small-text', 'tiny-text', 'large-text' );
		}

		/**
		 * @param mixed $classes
		 * @param mixed $default
		 *
		 * @return string[]
		 */
		public function prepare_classes( $classes, $default = '' ): array {

			$default_classnames = is_array( $default ) ? $default : explode( ' ', $default );
			$setting_classnames = is_array( $classes ) ? $classes : explode( ' ', $classes );

			$classnames                = array();
			$remove_default_size_class = false;

			foreach ( $setting_classnames as $setting_classname ) {
				if ( in_array( $setting_classname, $this->get_field_size_css_classes() ) ) {
					$remove_default_size_class = true;
				}
			}

			foreach ( $default_classnames as $default_classname ) {
				if ( $remove_default_size_class && in_array( $default_classname, $this->get_field_size_css_classes() ) ) {
					continue;
				}
				$classnames[] = $default_classname;
			}

			return array_unique( array_merge( $setting_classnames, $classnames ) );
		}

		/**
		 * @return string|array
		 */
		public function get_css_class() {
			return $this->get_attribute( 'class', '' );
		}

		/**
		 * @return string
		 */
		public function get_suffix(): ?string {
			return $this->get_attribute( 'suffix' );
		}

		/**
		 * @return string|null
		 */
		public function get_title(): ?string {
			return $this->get_attribute( 'title' );
		}

		/**
		 * @return array
		 */
		public function get_field(): array {
			return $this->field;
		}


		/**
		 * @param string $attribute
		 *
		 * @return bool
		 */
		public function has_attribute( string $attribute ): bool {
			$field = $this->get_field();

			return isset( $field[ $attribute ] );
		}

		/**
		 * @param string $attribute
		 * @param mixed  $default . Default null.
		 *
		 * @return mixed|null
		 */
		public function get_attribute( string $attribute, $default = null ) {
			$field = $this->get_field();

			return $field[ $attribute ] ?? $default;
		}

		public function group_inputs(): array {
			return array( 'radio', 'checkbox', 'group' );
		}

		/**
		 * @param array $attrs
		 * @param array $additional_attrs . Default array
		 *
		 * @return string
		 */
		public function get_html_attributes( array $attrs, array $additional_attrs = array() ): string {

			$attributes = wp_parse_args( $additional_attrs, $attrs );

			return implode(
				' ',
				array_map(
					function ( $key ) use ( $attributes ) {

						if ( is_bool( $attributes[ $key ] ) ) {
							return $attributes[ $key ] ? $key : '';
						}

								$value = $attributes[ $key ];

						if ( in_array( $key, array( 'class' ) ) ) {

							if ( is_array( $attributes[ $key ] ) ) {
										// Class name sanitized.
										$value = implode( ' ', array_unique( $attributes[ $key ] ) );
							}
						}

								return sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
					},
					array_keys( $attributes )
				)
			);
		}

		public function custom_input(): string {

			$type = $this->get_type();

			if ( method_exists( $this->get_settings(), 'custom_field' ) ) {
				return $this->get_settings()->custom_field( $this );
			}

			$message = sprintf( 'Field: "%s" not implemented. Please add "Settings::custom_field" method to implement.', $type );
			$this->get_settings()->trigger_error( '', $message );

			return '';
		}

		public function text_input( $css_class = 'regular-text' ): string {

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

			return sprintf( '<input %s> %s', $this->get_html_attributes( $attributes, $additional_attributes ), $this->get_suffix() );
		}

		public function textarea_input( $css_class = 'regular-text' ): string {

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

			return sprintf( '<textarea %s>%s</textarea>', $this->get_html_attributes( $attributes, $additional_attributes ), $value );
		}

		public function check_input(): string {

			$id      = $this->get_id();
			$type    = $this->get_type();
			$title   = $this->get_title();
			$name    = $this->get_name();
			$value   = $this->get_value();
			$options = $this->get_options();

			// group checkbox
			if ( 'checkbox' === $type && count( $options ) > 1 ) {
				$name = $this->get_name( true );
			}

			// single checkbox
			if ( 'checkbox' === $type && empty( $options ) ) {
				$options = array( 'yes' => $title );
			}

			// check radio input have options declared.
			if ( 'radio' === $type && empty( $options ) ) {
				$message = sprintf( 'Input Field: "%s". Title: "%s" need options to choose.', $id, $title );
				$this->get_settings()->trigger_error( '', $message );

				return '';
			}

			$inputs = array();

			foreach ( $options as $option_key => $option_value ) {
				$uniq_id = sprintf( '%s-%s', $id, $option_key );

				$attributes = array(
					'id'      => $uniq_id,
					'type'    => $type,
					'name'    => $name,
					'value'   => esc_attr( $option_key ),
					'checked' => ( 'checkbox' === $type ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ) ) : $value === $option_key,
				);

				$inputs[] = sprintf( '<label for="%s"><input %s /><span>%s</span></label>', esc_attr( $uniq_id ), $this->get_html_attributes( $attributes ), esc_html( $option_value ) );
			}

			return sprintf( '<fieldset><legend class="screen-reader-text">%s</legend>%s</fieldset>', $title, implode( '<br />', $inputs ) );
		}

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
				$selected = ( $is_multiple ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ) ) : $value === $option_key;
				$inputs[] = sprintf( '<option %s value="%s"><span>%s</span></option>', $this->get_html_attributes( array( 'selected' => $selected ) ), esc_attr( $option_key ), esc_html( $option_value ) );
			}

			return sprintf( '<select %s>%s</select>', $this->get_html_attributes( $attributes, $additional_attributes ), implode( '', $inputs ) );
		}

		/**
		 * @return self[]
		 */
		public function get_group_fields(): array {

			$name         = $this->get_name();
			$group_value  = $this->get_value( array() );
			$group_fields = $this->get_attribute( 'fields', array() );

			$fields = array();

			foreach ( $group_fields as $field ) {
				$fields[] = ( new Field( $field ) )->add_settings( $this->get_settings(), $group_value )->add_settings_id( $name );
			}

			return $fields;
		}

		public function get_rest_group_values(): array {

			$values = array();

			foreach ( $this->get_group_fields() as $field ) {

				if ( empty( $field->get_attribute( 'show_in_rest' ) ) ) {
					continue;
				}

				$id              = $field->get_id();
				$escape_callback = $this->get_escape_callback();
				$value           = map_deep( $field->get_value(), $escape_callback );

				$values[ $id ] = $value;
			}

			return $values;
		}

		public function get_rest_value() {
			$escape_callback = $this->get_escape_callback();

			return map_deep( $this->get_value(), $escape_callback );
		}

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
		 * @param string $field_id
		 * @param mixed  $default
		 *
		 * @return mixed|null
		 */
		public function get_group_value( string $field_id, $default = null ) {

			foreach ( $this->get_group_fields() as $field ) {
				$id = $field->get_id();
				if ( $id === $field_id ) {
					return $field->get_value( $default );
				}
			}

			return $default;
		}

		public function group_input( $css_class = 'small-text' ): string {

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

				// Group checkbox name
				if ( 'checkbox' === $field_type && $field_options && count( $field_options ) > 1 ) {
					$attributes['name'] = $field->get_name( true );
				}

				if ( in_array( $field_type, $this->group_inputs() ) ) {

					$attributes['class'] = array();

					// Single checkbox
					if ( 'checkbox' === $field_type && empty( $field_options ) ) {
						$attributes['value']   = 'yes';
						$attributes['checked'] = 'yes' === $field_value;

						$inputs[] = sprintf( '<p class="input-wrapper"><label for="%s"><input %s /><span>%s</span></label></p>', esc_attr( $uniq_id ), $this->get_html_attributes( $attributes ), esc_html( $field_title ) );

						continue;
					}

					// Checkbox and Radio
					$inputs[] = '<ul class="input-wrapper">';
					foreach ( $field_options as $option_key => $option_value ) {
						$uniq_id               = sprintf( '%s-%s-%s__group', $id, $field_id, $option_key );
						$attributes['value']   = esc_attr( $option_key );
						$attributes['checked'] = is_array( $field_value ) ? in_array( $option_key, $field_value ) : $option_key == $field_value;
						$attributes['id']      = $uniq_id;
						$inputs[]              = sprintf( '<li><label for="%s"><input %s /><span>%s</span></label></li>', esc_attr( $uniq_id ), $this->get_html_attributes( $attributes ), esc_html( $option_value ) );
					}
					$inputs[] = '</ul>';

				} else {
					// Input

					if ( 'textarea' === $field_type ) {
						$attributes['value'] = false;
						$inputs[]            = sprintf( '<p class="input-wrapper"><label for="%s"><span>%s</span></label> <textarea %s>%s</textarea></p>', esc_attr( $uniq_id ), esc_html( $field_title ), $this->get_html_attributes( $attributes, $field_attributes ), $field_value );
					} else {
						$inputs[] = sprintf( '<p class="input-wrapper"><label for="%s"><span>%s</span></label> <input %s /> %s</p>', esc_attr( $uniq_id ), esc_html( $field_title ), $this->get_html_attributes( $attributes, $field_attributes ), esc_html( $field_suffix ) );
					}
				}
			}

			return sprintf( '<fieldset class="group-input-wrapper"><legend class="screen-reader-text">%s</legend>%s</fieldset>', esc_html( $title ), implode( '', $inputs ) );
		}

		public function get_rest_type(): ?string {

			$type        = $this->get_type();
			$options     = $this->get_options();
			$is_single   = empty( $options );
			$is_multiple = $this->has_attribute( 'multiple' );

			// array( 'number', 'integer', 'string', 'boolean', 'array', 'object' )

			switch ( $type ) {
				case 'textarea';
				case 'email';
				case 'url';
				case 'text';
				case 'regular-text';
				case 'color';
				case 'small-text';
				case 'tiny-text';
				case 'large-text';
				case 'radio';
				case 'code';
					return 'string';
					break;
				case 'number';
					return 'number';
					break;
				case 'checkbox';
					return $is_single ? 'string' : 'array';
					break;
				case 'select2';
				case 'select';
					return $is_multiple ? 'array' : 'string';
					break;
				case 'group';
					return 'object';
					break;
			}

			return 'string';
		}

		/**
		 * @return string
		 * @todo Label based on input
		 */
		public function get_label_markup(): string {

			$id    = $this->get_id();
			$title = $this->get_title();
			$type  = $this->get_type();

			if ( in_array( $type, $this->group_inputs() ) ) {
				return $title;
			}
			$required_markup = '';
			if ( $this->has_attribute( 'required' ) ) {
				$required_markup = '<span class="required">*</span>';
			}

			return sprintf( '<label for="%s">%s %s</label>', esc_attr( $id ), esc_html( $title ), $required_markup );
		}

		/**
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

		/***
		 * @return string
		 * @todo Add More Fields
		 * @see  Settings::sanitize_fields()
		 */
		public function get_input_markup(): string {
			$type = $this->get_type();
			// input, code, textarea, select, select2, regular-text, small-text, tiny-text, large-text, color

			switch ( $type ) {
				case 'text';
				case 'regular-text';
				case 'code';
					return $this->text_input();
					break;
				case 'color';
				case 'number';
				case 'small-text';
					return $this->text_input( 'small-text' );
					break;
				case 'tiny-text';
					return $this->text_input( 'tiny-text' );
					break;
				case 'large-text';
					return $this->text_input( 'large-text' );
					break;
				case 'radio';
				case 'checkbox';
					return $this->check_input();
					break;
				case 'select';
				case 'select2';
					return $this->select_input();
					break;
				case 'group';
					return $this->group_input();
					break;
				case 'textarea';
					return $this->textarea_input();
					break;
				default:
					return $this->custom_input();
					break;
			}
		}

		/**
		 * @return string
		 */
		public function get_description_markup(): string {
			$id = $this->get_id();

			return $this->has_attribute( 'description' ) ? sprintf( '<p class="description" id="%s-description">%s</p>', esc_attr( $id ), wp_kses_post( $this->get_attribute( 'description' ) ) ) : '';
		}

		/**
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
