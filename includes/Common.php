<?php
	/**
	 * Admin Settings Common Methods for Classes.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

trait Common {

	/**
	 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
	 *
	 * @param mixed $variable      Variable.
	 * @param mixed $default_value Default value.
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function get_var( &$variable, $default_value = null ) {
		return true === isset( $variable ) ? $variable : $default_value;
	}

	/**
	 * Get $_REQUEST data if set, otherwise return a default value or null. Prevents notices when data is not set.
	 *
	 * @param string $variable      Variable.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function http_request_var( string $variable = '', $default_value = null ) {
		$request_data = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $this->is_empty_string( $variable ) ) {
			return $this->is_empty_array( $request_data ) ? false : $request_data;
		}

		return $this->get_var( $request_data[ $variable ], $default_value );
	}

	/**
	 * Get $_GET data if set, otherwise return a default value or null. Prevents notices when data is not set.
	 *
	 * @param string $variable      Variable.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function http_get_var( string $variable = '', $default_value = null ) {
		$get_data = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $this->is_empty_string( $variable ) ) {
			return $this->is_empty_array( $get_data ) ? false : $get_data;
		}

		return $this->get_var( $get_data[ $variable ], $default_value );
	}

	/**
	 * Get $_POST data if set, otherwise return a default value or null. Prevents notices when data is not set.
	 *
	 * @param string $variable      Variable.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function http_post_var( string $variable = '', $default_value = null ) {
		$post_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $this->is_empty_string( $variable ) ) {
			return $this->is_empty_array( $post_data ) ? false : $post_data;
		}

		return $this->get_var( $post_data[ $variable ], $default_value );
	}

	/**
	 * Create HTML Attributes from given array
	 *
	 * @param array<string, mixed> $attributes Attribute array.
	 * @param string[]             $exclude    Exclude attribute. Default array.
	 *
	 * @return string
	 */
	public function get_html_attributes( array $attributes, array $exclude = array() ): string {
		$attrs = array();

		foreach ( $attributes as $attribute_name => $attribute_value ) {
			// Exclude attribute.
			if ( in_array( $attribute_name, $exclude, true ) ) {
				continue;
			}

			// Skip if attribute value is blank.
			if ( is_string( $attribute_value ) && $this->is_empty_string( $attribute_value ) ) {
				continue;
			}

			// Skip if attribute value is null.
			if ( is_null( $attribute_value ) ) {
				continue;
			}

			// Skip if attribute value is boolean false.
			if ( false === $attribute_value ) {
				continue;
			}

			// If attribute is class and value is array.
			if ( is_array( $attribute_value ) ) {
				if ( 'class' === $attribute_name ) {
					$attribute_value = $this->get_css_classes( $attribute_value );
				} else {
					$attribute_value = wp_json_encode( $attribute_value );
				}
			}

			// If attribute is boolean true only use attribute name.
			if ( true === $attribute_value ) {
				$attrs[] = sprintf( '%s', esc_attr( $attribute_name ) );
				continue;
			}

			$attrs[] = sprintf( '%s="%s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
		}

		return implode( ' ', array_unique( $attrs ) );
	}

	/**
	 * Check is string is empty.
	 *
	 * @param string $check_value Check value.
	 *
	 * @return bool
	 */
	public function is_empty_string( string $check_value = '' ): bool {
		return 0 === strlen( trim( $check_value ) );
	}

	/**
	 * Array to css class.
	 *
	 * @param array<int|string, ?mixed> $classes_array css classes array.
	 *
	 * @return string
	 * @since  1.0.0
	 * @example
	 * <code>
	 *   ['class-a', 'class-b']
	 *   // or
	 *   ['class-a'=>true, 'class-b'=>false, 'class-c'=>'', 'class-e'=>null, 'class-d'=>'hello']
	 * </code>
	 */
	public function get_css_classes( array $classes_array = array() ): string {
		$classes = array();
		foreach ( $classes_array as $class_name => $should_include ) {
			// Is class assign by numeric array. Like: ['class-a', 'class-b'].
			if ( is_int( $class_name ) ) {
				if ( ! is_string( $should_include ) ) {
					continue;
				}

				if ( $this->is_empty_string( $should_include ) ) {
					continue;
				}

				$classes[] = $should_include;
				continue;
			}

			if ( false === $should_include ) {
				continue;
			}

			if ( is_string( $should_include ) && $this->is_empty_string( $should_include ) ) {
				continue;
			}

			if ( is_null( $should_include ) ) {
				continue;
			}

			if ( is_array( $should_include ) && $this->is_empty_array( $should_include ) ) {
				continue;
			}

			// Is class assign by associative array.
			// Like: ['class-a'=>true, 'class-b'=>false, class-c'=>'', 'class-d'=>'hello', 'class-x'=>null, 'class-y'=>array()].
			$classes[] = $class_name;
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Check is array is empty.
	 *
	 * @param array<int|string, ?mixed> $check_value Check value.
	 *
	 * @return bool
	 */
	public function is_empty_array( array $check_value = array() ): bool {
		return 0 === count( $check_value );
	}

	/**
	 * Generate Inline Style from array
	 *
	 * @param array<string, mixed> $inline_styles_array Inline style as array.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function get_inline_styles( array $inline_styles_array = array() ): string {
		$styles = array();

		foreach ( $inline_styles_array as $property => $value ) {

			if ( is_null( $value ) ) {
				continue;
			}

			if ( is_bool( $value ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				continue;
			}

			if ( is_string( $value ) && $this->is_empty_string( $value ) ) {
				continue;
			}

			$styles[] = sprintf(
				'%s: %s;',
				esc_attr( $property ),
				esc_attr( $value )
			);
		}

		return implode( ' ', array_unique( $styles ) );
	}

	/**
	 * Converts a bool to a 'yes' or 'no'.
	 *
	 * @param bool|string $check_value Bool to convert. If a string is passed it will first be converted to a bool.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function boolean_to_string( $check_value ): string {

		$value = $this->string_to_boolean( $check_value );
		return true === $value ? 'yes' : 'no';
	}

	/**
	 * Converts a string (e.g. 'yes' or 'no') to a bool.
	 * Recognizing words like Yes, No, Off, On, both string and native types of true and false,
	 * and is not case-sensitive when validating strings.
	 *
	 * @param string|bool|null $check_value String to convert. If a bool is passed it will be returned as-is.
	 *
	 * @return boolean
	 * @since      1.0.0
	 */
	public function string_to_boolean( $check_value ): bool {

		return filter_var( $check_value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Merge array.
	 *
	 * @param array<string|int, mixed> ...$arrays arrays.
	 *
	 * @return array<string|int, mixed>
	 */
	public function array_merge( array ...$arrays ): array {
		$result = array();

		foreach ( $arrays as $array ) {
			foreach ( $array as $key => $value ) {
				if ( isset( $result[ $key ] ) && is_array( $result[ $key ] ) && is_array( $value ) ) {
					$result[ $key ] = wp_parse_args( $result[ $key ], $value );
				} else {
					$result[ $key ] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * Preapre KSES Arguments.
	 *
	 * @param array<string, mixed> $tags Allowed Tags.
	 *
	 * @return array<string, mixed>
	 * @see wp_kses_check_attr_val()
	 */
	public function prepare_kses_args( array $tags = array() ): array {

		$empty_attributes = array(
			'allowfullscreen',
			'autofocus',
			'checked',
			'default',
			'formnovalidate',
			'inert',
			'itemscope',
			'multiple',
			'required',
			'open',
			'selected',
			'hidden',
			'contenteditable',
			'draggable',
		);

		return array_reduce(
			array_keys( $tags ),
			function ( $result, $tag ) use ( $tags, $empty_attributes ) {
				foreach ( $tags[ $tag ] as $key => $value ) {
					if ( in_array( $value, $empty_attributes, true ) ) {
						$result[ $tag ][ $value ] = array( 'valueless' => 'y' );
					} elseif ( is_array( $value ) ) {
						$result[ $tag ][ $key ] = $value;
					} else {
						$result[ $tag ][ $value ] = true;
					}
				}

				return $result;
			},
			array()
		);
	}

	/**
	 * Returns an array of allowed HTML tags and attributes for a given context.
	 *
	 * @param array<string, string[]> $args extra argument.
	 *
	 * @return array<string, mixed>
	 */
	public function get_kses_allowed_html( array $args = array() ): array {

		$defaults = wp_kses_allowed_html( 'post' );

		$tags = array(
			'svg'   => array( 'class', 'aria-hidden', 'aria-labelledby', 'role', 'xmlns', 'width', 'height', 'viewbox' ),
			'g'     => array( 'fill' ),
			'title' => array( 'title' ),
			'path'  => array( 'd', 'fill' ),
			'table' => array( 'class', 'role' ),
		);

		$allowed_args = $this->prepare_kses_args( $tags );

		$extra_args = $this->prepare_kses_args( $args );

		return $this->array_merge( $extra_args, $allowed_args, $defaults );
	}

	/**
	 * Returns an array of allowed HTML tags and attributes for a given context.
	 *
	 * @param array<string, string[]> $args extra argument.
	 *
	 * @return array<string, mixed>
	 */
	public function get_kses_allowed_input_html( array $args = array() ): array {

		$defaults = wp_kses_allowed_html( 'post' );

		$allowed_attributes = array( 'action', 'method', 'list', 'autocomplete', 'data-*', 'readonly', 'disabled', 'type', 'width', 'size', 'id', 'class', 'style', 'checked', 'selected', 'multiple', 'name', 'inputmode', 'pattern', 'required', 'label', 'aria-label', 'aria-describedby', 'value', 'step', 'min', 'max', 'placeholder' );
		$tags               = array(
			'form'     => $allowed_attributes,
			'input'    => $allowed_attributes,
			'textarea' => $allowed_attributes,
			'optgroup' => $allowed_attributes,
			'option'   => $allowed_attributes,
			'select'   => $allowed_attributes,
			'datalist' => $allowed_attributes,
			'tr'       => array( 'inert' ),
			'ul'       => array( 'inert' ),
		);

		$allowed_args = $this->prepare_kses_args( $tags );

		$extra_args = $this->prepare_kses_args( $args );

		return $this->array_merge( $extra_args, $allowed_args, $defaults );
	}

	/**
	 * Returns an array of allowed HTML tags and attributes for dialog box.
	 *
	 * @param array<string, string[]> $args extra argument.
	 *
	 * @return array<string, mixed>
	 */
	public function get_kses_allowed_dialog_html( array $args = array() ): array {

		$defaults = wp_kses_allowed_html( 'post' );

		$allowed_attributes = array( 'list', 'autocomplete', 'data-*', 'readonly', 'disabled', 'type', 'width', 'size', 'id', 'class', 'style', 'checked', 'selected', 'multiple', 'name', 'inputmode', 'pattern', 'required', 'label', 'aria-label', 'aria-describedby', 'value', 'step', 'min', 'max', 'placeholder' );
		$tags               = array(
			'form'     => array( 'method', 'data-*' ),
			'button'   => $allowed_attributes,
			'input'    => $allowed_attributes,
			'textarea' => $allowed_attributes,
			'optgroup' => $allowed_attributes,
			'option'   => $allowed_attributes,
			'select'   => $allowed_attributes,
			'datalist' => $allowed_attributes,
			'tr'       => array( 'inert' ),
			'ul'       => array( 'inert' ),
			'li'       => array( 'inert' ),
		);

		$allowed_args = $this->prepare_kses_args( $tags );

		$extra_args = $this->prepare_kses_args( $args );

		return $this->array_merge( $extra_args, $allowed_args, $defaults );
	}

	/**
	 * Check is array is all empty values.
	 *
	 * @param array<int|string, ?mixed> $items Check array.
	 *
	 * @return bool
	 */
	public function is_array_each_empty_value( array $items = array() ): bool {
		$checked = array_map(
			function ( $value ) {
				if ( is_array( $value ) && ! $this->is_array_each_empty_value( $value ) ) {
					return true;
				}

				if ( is_string( $value ) && ! $this->is_empty_string( $value ) ) {
					return true;
				}

				if ( true === $value ) {
					return true;
				}

				return false;
			},
			$items
		);

		return ! in_array( true, array_unique( $checked ), true );
	}

	/**
	 * Check is given array is numeric or not.
	 *
	 * @param string[]|array<string|int, mixed> $items Array items.
	 *
	 * @return bool
	 */
	public function is_numeric_array( array $items ): bool {
		return array_keys( $items ) === range( 0, count( $items ) - 1 );
	}

	/**
	 * Merge Array in deep.
	 *
	 * @param array<string|int, mixed> $args     Array.
	 * @param array<string|int, mixed> $defaults Default array.
	 *
	 * @return array<string|int, mixed>
	 */
	public function array_merge_deep( array $args, array $defaults ): array {
		$new_args = $defaults;

		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
				$new_args[ $key ] = $this->array_merge_deep( $value, $new_args[ $key ] );
			} else {
				$new_args[ $key ] = $value;
			}
		}

		return $new_args;
	}
}
