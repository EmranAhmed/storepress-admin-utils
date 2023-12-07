<?php
	
	namespace StorePress\AdminUtils;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	/**
	 * Admin Settings
	 *
	 * @package    StorePress
	 * @subpackage AdminUtils
	 * @name Field
	 * @version    1.0
	 */
	if ( ! class_exists( '\StorePress\AdminUtils\Field' ) ) {
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
			 * @param array $field
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
				
				$this->field[ 'show_in_rest' ] = $this->get_attribute( 'show_in_rest', true );
				
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
				$this->field[ 'value' ] = $value;
				
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
			 * @return string|null
			 */
			public function get_type(): ?string {
				return $this->get_attribute( 'type' );
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
				
				if ( ! empty( $classes ) && is_string( $classes ) ) {
					$split_classes = array_unique( explode( ' ', $classes ) );
					foreach ( $split_classes as $cls ) {
						if ( in_array( $cls, $this->get_field_size_css_classes() ) ) {
							return $split_classes;
						}
					}
					
					return is_array( $default ) ? array_merge( $split_classes, $default ) : array_merge( $split_classes, array( $default ) );
				}
				
				if ( ! empty( $classes ) && is_array( $classes ) ) {
					$split_classes = array_unique( $classes );
					foreach ( $split_classes as $cls ) {
						if ( in_array( $cls, $this->get_field_size_css_classes() ) ) {
							return $split_classes;
						}
					}
					
					return is_array( $default ) ? array_merge( $split_classes, $default ) : array_merge( $split_classes, array( $default ) );
				}
				
				return is_array( $default ) ? $default : array( $default );
			}
			
			/**
			 * @return string|array
			 */
			public function get_css_class() {
				return $this->get_attribute( 'class' );
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
				
				return implode( ' ', array_map( function ( $key ) use ( $attributes ) {
					
					if ( is_bool( $attributes[ $key ] ) ) {
						return $attributes[ $key ] ? $key : '';
					}
					
					$value = $attributes[ $key ];
					
					if ( in_array( $key, array( 'class' ) ) ) {
						
						if ( is_array( $attributes[ $key ] ) ) {
							$value = implode( ' ', array_unique( $attributes[ $key ] ) );
						}
					}
					
					return sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
				}, array_keys( $attributes ) ) );
			}
			
			public function custom_input(): string {
				$id = $this->get_id();
				
				return '';
			}
			
			public function text_input( $css_class = 'regular-text' ): string {
				
				$id                    = $this->get_id();
				$class                 = $this->get_css_class();
				$type                  = $this->get_type();
				$additional_attributes = $this->get_attribute( 'html_attributes', array() );
				
				$attributes = array(
					'id'    => $id,
					'type'  => $type,
					'class' => $this->prepare_classes( $class, $css_class ),
					'name'  => $this->get_name(),
					'value' => $this->get_value(),
				);
				
				if ( $this->has_attribute( 'description' ) ) {
					$attributes[ 'aria-describedby' ] = sprintf( '%s-description', $id );
				}
				
				if ( $this->has_attribute( 'required' ) ) {
					$attributes[ 'required' ] = true;
				}
				
				if ( $this->has_attribute( 'placeholder' ) ) {
					$attributes[ 'placeholder' ] = $this->get_attribute( 'placeholder' );
				}
				
				return sprintf( '<input %s> %s', $this->get_html_attributes( $attributes, $additional_attributes ), $this->get_suffix() );
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
						'value'   => $option_key,
						'checked' => ( 'checkbox' === $type ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ) ) : $value === $option_key,
					);
					
					$inputs[] = sprintf( '<label for="%s"><input %s /><span>%s</span></label>', $uniq_id, $this->get_html_attributes( $attributes ), esc_attr( $option_value ) );
				}
				
				return sprintf( '<fieldset><legend class="screen-reader-text">%s</legend>%s</fieldset>', $title, implode( '<br />', $inputs ) );
			}
			
			public function select_input(): string {
				
				$id          = $this->get_id();
				$type        = $this->get_type();
				$title       = $this->get_title();
				$value       = $this->get_value();
				$is_multiple = $this->has_attribute( 'multiple' );
				$options     = $this->get_options();
				$class       = $this->get_css_class();
				$name        = $this->get_name( $is_multiple );
				
				
				$attributes = array(
					'id'       => $id,
					'type'     => $type,
					'name'     => $name,
					'class'    => $this->prepare_classes( $class, 'regular-text' ),
					'multiple' => $is_multiple,
				);
				
				$inputs = array();
				
				foreach ( $options as $option_key => $option_value ) {
					$selected = ( $is_multiple ) ? in_array( $option_key, is_array( $value ) ? $value : array( $value ) ) : $value === $option_key;
					$inputs[] = sprintf( '<option %s value="%s"><span>%s</span></option>', $this->get_html_attributes( array( 'selected' => $selected ) ), esc_attr( $option_key ), esc_attr( $option_value ) );
				}
				
				return sprintf( '<select %s>%s</select>', $this->get_html_attributes( $attributes ), implode( '', $inputs ) );
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
					
					$id            = $field->get_id();
					$value         = $field->get_value();
					$values[ $id ] = $value;
				}
				
				return $values;
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
					$field_value       = $field->get_value();
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
						$attributes[ 'name' ] = $field->get_name( true );
					}
					
					if ( in_array( $field_type, $this->group_inputs() ) ) {
						
						$attributes[ 'class' ] = array();
						
						// Single checkbox
						if ( 'checkbox' === $field_type && empty( $field_options ) ) {
							$attributes[ 'value' ]   = 'yes';
							$attributes[ 'checked' ] = 'yes' === $field_value;
							
							$inputs[] = sprintf( '<p class="input-wrapper"><label for="%s"><input %s /><span>%s</span></label></p>', $uniq_id, $this->get_html_attributes( $attributes ), esc_attr( $field_title ) );
							
							continue;
						}
						
						// Checkbox and Radio
						$inputs[] = '<ul>';
						foreach ( $field_options as $option_key => $option_value ) {
							$uniq_id                 = sprintf( '%s-%s-%s__group', $id, $field_id, $option_key );
							$attributes[ 'value' ]   = $option_key;
							$attributes[ 'checked' ] = ! is_array( $field_value ) ? in_array( $option_key, array( $field_value ) ) : in_array( $option_key, $field_value );
							$attributes[ 'id' ]      = $uniq_id;
							$inputs[]                = sprintf( '<li><label for="%s"><input %s /><span>%s</span></label></li>', $uniq_id, $this->get_html_attributes( $attributes ), esc_attr( $option_value ) );
						}
						$inputs[] = '</ul>';
						
					} else {
						// Input
						$inputs[] = sprintf( '<p class="input-wrapper"><label for="%s"><span>%s</span></label> <input %s /> %s</p>', $uniq_id, esc_attr( $field_title ), $this->get_html_attributes( $attributes, $field_attributes ), $field_suffix );
					}
				}
				
				return sprintf( '<fieldset><legend class="screen-reader-text">%s</legend>%s</fieldset>', $title, implode( '', $inputs ) );
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
						return 'string';
						break;
					case 'number';
						return 'number';
						break;
					case 'checkbox';
						return $is_single ? 'string' : 'array';
						break;
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
				
				return sprintf( '<label for="%s">%s</label>', $id, $title );
			}
			
			/***
			 * @return string
			 * @todo Add More Fields
			 * @see  Settings::sanitize_fields()
			 */
			public function get_input_markup(): string {
				$type = $this->get_type();
				// input, regular-text, small-text, tiny-text, large-text, color
				
				switch ( $type ) {
					case 'text';
					case 'regular-text';
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
						return $this->select_input();
						break;
					case 'group';
						return $this->group_input();
						break;
				}
				
				return $this->custom_input();
			}
			
			/**
			 * @return string
			 */
			public function get_description_markup(): string {
				$id = $this->get_id();
				
				return $this->has_attribute( 'description' ) ? sprintf( '<p class="description" id="%s-description">%s</p>', $id, $this->get_attribute( 'description' ) ) : '';
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
