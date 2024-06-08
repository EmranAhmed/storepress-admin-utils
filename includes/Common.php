<?php
	/**
	 * Admin Settings Common Methods for Classes.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

trait Common {

	/**
	 * Create HTML Attributes from given array
	 *
	 * @param array $attributes Attribute array.
	 * @param array $exclude    Exclude attribute. Default is empty array.
	 *
	 * @return string
	 */
	public function get_html_attributes( array $attributes, array $exclude = array() ): string {

		$attrs = array_map(
			function ( $key ) use ( $attributes, $exclude ) {

				// Exclude attribute.
				if ( in_array( $key, $exclude, true ) ) {
					return '';
				}

				$value = $attributes[ $key ];

				// If attribute value is null.
				if ( is_null( $value ) ) {
					return '';
				}

				// If attribute value is boolean.
				if ( is_bool( $value ) ) {
					return $value ? $key : '';
				}

				// If attribute value is array.
				if ( is_array( $value ) ) {
					$value = $this->get_css_classes( $value );
				}

				return sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
			},
			array_keys( $attributes )
		);

		return implode( ' ', $attrs );
	}

	/**
	 * Array to css class.
	 *
	 * @param array $classes_array css classes array.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_css_classes( array $classes_array = array() ): string {

		$classes = array();
		foreach ( $classes_array as $class_name => $should_include ) {

			// Is class assign by numeric array. Like: ['class-a', 'class-b'].
			if ( is_numeric( $class_name ) && ! is_string( $class_name ) ) {
				$classes[] = esc_attr( $should_include );
				continue;
			}

			// Is class assign by associative array.
			// Like: ['class-a'=>true, 'class-b'=>false, class-c'=>'', 'class-d'=>'hello'].
			if ( ! empty( $should_include ) ) {
				$classes[] = esc_attr( $class_name );
			}
		}
		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Generate Inline Style from array
	 *
	 * @param array $inline_styles_array Inline style as array.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_inline_styles( array $inline_styles_array = array() ): string {

		$styles = array();

		foreach ( $inline_styles_array as $property => $value ) {
			if ( is_null( $value ) ) {
				continue;
			}
			$styles[] = sprintf( '%s: %s;', esc_attr( $property ), esc_attr( $value ) );
		}

		return implode( ' ', $styles );
	}

	/**
	 * Converts a bool to a 'yes' or 'no'.
	 *
	 * @param bool|string $value Bool to convert. If a string is passed it will first be converted to a bool.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function boolean_to_string( $value ): string {
		if ( ! is_bool( $value ) ) {
			$value = $this->string_to_boolean( $value );
		}

		return true === $value ? 'yes' : 'no';
	}

	/**
	 * Converts a string (e.g. 'yes' or 'no') to a bool.
	 *
	 * @param string|bool $value String to convert. If a bool is passed it will be returned as-is.
	 *
	 * @return boolean
	 * @since      1.0.0
	 */
	public function string_to_boolean( $value ): bool {
		$value = $value ?? '';

		return is_bool( $value ) ? $value : ( 'yes' === strtolower( $value ) || 1 === $value || 'true' === strtolower( $value ) || '1' === $value );
	}

	/**
	 * Returns an array of allowed HTML tags and attributes for a given context.
	 *
	 * @param array $args extra argument.
	 *
	 * @return array
	 */
	public function get_kses_allowed_html( array $args = array() ): array {

		$defaults = wp_kses_allowed_html( 'post' );

		$tags = array(
			'svg'   => array( 'class', 'data-*', 'aria-hidden', 'aria-labelledby', 'role', 'xmlns', 'width', 'height', 'viewbox', 'height' ),
			'g'     => array( 'fill' ),
			'title' => array( 'title' ),
			'path'  => array( 'd', 'fill' ),
			'table' => array( 'class', 'role' ),
		);

		$allowed_args = array_reduce(
			array_keys( $tags ),
			function ( $carry, $tag ) use ( $tags ) {
				$carry[ $tag ] = array_fill_keys( $tags[ $tag ], true );
				return $carry;
			},
			array()
		);

		return array_merge( $defaults, $allowed_args, $args );
	}

	/**
	 * Returns an array of allowed HTML tags and attributes for a given context.
	 *
	 * @param array $args extra argument.
	 *
	 * @return array
	 */
	public function get_kses_allowed_input_html( array $args = array() ): array {

		$defaults = wp_kses_allowed_html( 'post' );

		$allowed_attributes = array( 'disabled', 'type', 'width', 'size', 'id', 'class', 'style', 'checked', 'selected', 'multiple', 'name', 'required', 'label', 'aria-label', 'aria-describedby', 'value', 'step', 'mix', 'max', 'placeholder' );
		$tags               = array(
			'input'    => $allowed_attributes,
			'textarea' => $allowed_attributes,
			'optgroup' => $allowed_attributes,
			'option'   => $allowed_attributes,
			'select'   => $allowed_attributes,
		);

		$allowed_args = array_reduce(
			array_keys( $tags ),
			function ( $carry, $tag ) use ( $tags ) {
				$carry[ $tag ] = array_fill_keys( $tags[ $tag ], true );
				return $carry;
			},
			array()
		);

		return array_merge( $defaults, $allowed_args, $args );
	}
}
