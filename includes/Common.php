<?php
	/**
	 * Admin Settings Common Methods for Classes.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

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
	 * Generates an HTML attributes string from one or more attribute arrays.
	 *
	 * Converts associative arrays of attributes into a properly escaped HTML
	 * attribute string. Supports multiple arrays via spread operator, with later
	 * arrays overriding earlier ones. Handles special cases for class (arrays
	 * become space-separated), style (arrays become CSS declarations), and
	 * boolean attributes.
	 *
	 * @param array<string, ?mixed> ...$attribute_sets Variable number of attribute arrays to merge.
	 *                                            Special keys:
	 *                                            - '_exclude': list<string> Attribute names to skip.
	 *
	 * @return string Space-separated HTML attributes string, escaped for output.
	 *
	 * @since 1.0.0
	 *
	 * @example Basic usage with common attributes
	 * ```php
	 * $attributes = [
	 * 'id'          => 'my-element',
	 * 'class'       => 'btn btn-primary',
	 * 'href'        => 'https://example.com',
	 * 'target'      => '_blank',
	 * 'tabindex'    => 0,
	 * ];
	 *
	 * $attrs = $this->get_html_attributes($attributes);
	 * // Returns: 'id="my-element" class="btn btn-primary" href="https://example.com" target="_blank" tabindex="0"
	 *
	 * // Usage in markup:
	 * // <a <?php echo esc_attr($attrs); ?>>Link</a>
	 * ```
	 *
	 * @example Boolean attributes and value filtering
	 * ```php
	 * $is_disabled = true;
	 * $is_readonly = false;
	 * $custom_attr = null;
	 *
	 * $attributes = [
	 * 'type'        => 'text',
	 * 'name'        => 'username',
	 * 'disabled'    => $is_disabled,  // Added as boolean attribute
	 * 'readonly'    => $is_readonly,  // Skipped (false)
	 * 'placeholder' => $custom_attr,  // Skipped (null)
	 * 'required'    => true,          // Added as boolean attribute
	 * 'value'       => '',            // Skipped (empty string)
	 * ];
	 *
	 * $attrs = $this->get_html_attributes($attributes);
	 * // Returns: 'type="text" name="username" disabled required'
	 * ```
	 *
	 * @example Basic usage with single array:
	 * ```php
	 * $attrs = $this->get_html_attributes( [
	 *     'id'    => 'my-element',
	 *     'class' => [ 'btn', 'btn-primary' ],
	 *     'data-active' => true,
	 * ] );
	 * // Result: 'id="my-element" class="btn btn-primary" data-active'
	 * ```
	 *
	 * @example Merging multiple arrays with overrides:
	 * ```php
	 * $defaults = [
	 *     'class'       => [ 'card' ],
	 *     'data-theme'  => 'light',
	 * ];
	 * $custom = [
	 *     'class'      => [ 'card-featured' ],
	 *     'data-theme' => 'dark',
	 *     'id'         => 'featured-card',
	 * ];
	 * $attrs = $this->get_html_attributes( $defaults, $custom );
	 * // Result: 'class="card-featured" data-theme="dark" id="featured-card"'
	 * ```
	 *
	 * @example Using _exclude to skip specific attributes:
	 * ```php
	 * $attrs = $this->get_html_attributes(
	 *     [
	 *         'id'       => 'my-form',
	 *         'action'   => '/submit',
	 *         'method'   => 'post',
	 *         'class'    => [ 'form', 'ajax-form' ],
	 *         '_exclude' => [ 'action', 'method' ],
	 *     ]
	 * );
	 * // Result: 'id="my-form" class="form ajax-form"'
	 * ```
	 *  // Note: 'class' array is processed via get_css_classes()
	 *  // Note: 'style' array is processed via get_inline_styles()
	 *  // Note: Other arrays are JSON encoded for data attributes
	 */
	public function get_html_attributes( array ...$attribute_sets ): string {

		// Merge all attribute arrays.
		$attributes = $this->array_merge( ...$attribute_sets );

		// Extract and remove _exclude from attributes.
		$exclude = (array) ( $attributes['_exclude'] ?? array() );
		unset( $attributes['_exclude'] );

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

			// If $attribute_name is class or style or value is array.
			if ( is_array( $attribute_value ) ) {
				if ( 'class' === $attribute_name ) {
					$attribute_value = $this->get_css_classes( $attribute_value );
					if ( $this->is_empty_string( $attribute_value ) ) {
						continue;
					}
				} elseif ( 'style' === $attribute_name ) {
					$attribute_value = $this->get_inline_styles( $attribute_value );
					if ( $this->is_empty_string( $attribute_value ) ) {
						continue;
					}
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
		return '' === trim( $check_value );
	}

	/**
	 * Generates a space-separated string of CSS classes from various input formats.
	 *
	 * Accepts multiple arguments of mixed types and intelligently processes them:
	 * - Strings are added directly as class names
	 * - Arrays with numeric keys treat values as class names
	 * - Arrays with string keys treat keys as class names when values are truthy
	 *
	 * @param string|array<int|string, ?mixed> ...$css_classes_args Variable number of arguments.
	 *        Each argument can be:
	 *        - string: Added directly as a class name.
	 *        - array<int, string>: Indexed array of class names.
	 *        - array<string, mixed>: Associative array where keys are class names
	 *          and values determine inclusion (truthy = include, falsy = exclude).
	 *
	 * @return string Space-separated string of unique CSS class names.
	 *
	 * @since   1.0.0
	 *
	 * @example Basic usage with strings and arrays
	 * ```php
	 * // Simple strings
	 * $classes = $this->get_css_classes('btn', 'btn-primary');
	 * // Returns: 'btn btn-primary'
	 *
	 * // Array with numeric keys (list of classes)
	 * $classes = $this->get_css_classes(['card', 'card-body', 'shadow']);
	 * // Returns: 'card card-body shadow'
	 * ```
	 *
	 * @example Conditional classes using associative arrays
	 * ```php
	 * $is_active   = true;
	 * $is_disabled = false;
	 * $has_icon    = 'left';
	 *
	 * $classes = $this->get_css_classes(
	 *     'btn',
	 *     [
	 *         'is-active'   => $is_active,    // Added (true)
	 *         'is-disabled' => $is_disabled,  // Skipped (false)
	 *         'has-icon'    => $has_icon,     // Added (truthy string)
	 *         'is-loading'  => null,          // Skipped (null)
	 *     ]
	 * );
	 * // Returns: 'btn is-active has-icon'
	 * ```
	 *
	 * @example Complex mixed arguments
	 * ```php
	 * $block_classes = ['wp-block', 'alignwide'];
	 * $user_class    = 'custom-class';
	 * $is_featured   = true;
	 * $extra_classes = [];
	 *
	 * $classes = $this->get_css_classes(
	 *     'base-block',
	 *     $block_classes,
	 *     $user_class,
	 *     [
	 *         'is-featured'    => $is_featured,   // Added
	 *         'has-extras'     => $extra_classes, // Skipped (empty array)
	 *         'custom-variant' => '',             // Skipped (empty string)
	 *     ],
	 *     ['duplicate', 'wp-block'] // Duplicates are removed
	 * );
	 * // Returns: 'base-block wp-block alignwide custom-class is-featured duplicate'
	 * ```
	 */
	public function get_css_classes( ...$css_classes_args ): string {
		$classes = array();

		foreach ( $css_classes_args as $arg ) {
			if ( is_string( $arg ) && ! $this->is_empty_string( $arg ) ) {
				$classes[] = $arg;
				continue;
			}

			if ( ! is_array( $arg ) ) {
				continue;
			}

			foreach ( $arg as $key => $value ) {
				if ( is_int( $key ) ) {
					if ( is_string( $value ) && ! $this->is_empty_string( $value ) ) {
						$classes[] = $value;
					}
					continue;
				}

				if ( false === $value || null === $value ) {
					continue;
				}

				if ( is_string( $value ) && $this->is_empty_string( $value ) ) {
					continue;
				}

				if ( is_array( $value ) && $this->is_empty_array( $value ) ) {
					continue;
				}

				$classes[] = $key;
			}
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Generates a semicolon-separated string of inline CSS styles from various input formats.
	 *
	 * Accepts multiple arguments of mixed types and intelligently processes them:
	 * - Strings are added directly as raw style declarations
	 * - Arrays with numeric keys treat values as raw style declarations
	 * - Arrays with string keys treat keys as CSS properties and values as property values
	 *
	 * Later property declarations override earlier ones when using associative arrays,
	 * allowing for easy default/override patterns.
	 *
	 * @param string|array<int|string, ?mixed> ...$inline_styles_args Variable number of arguments.
	 *                                                                                          Each argument can be:
	 *                                                                                          - string: Added directly as a raw style declaration if non-empty.
	 *                                                                                          - array: Processed based on key type:
	 *                                                                                          - Numeric keys: Values are added as raw style declarations if they're non-empty strings.
	 *                                                                                          - String keys: Keys are used as CSS properties, values as property values.
	 *                                                                                          (null, bool, array, and empty string values are skipped).
	 *
	 * @return string Semicolon-separated string of CSS style declarations.
	 *                Property names and values are escaped using esc_attr().
	 *
	 * @since   1.0.0
	 *
	 * @example Basic usage with strings and arrays
	 * ```php
	 * // Raw style string
	 * $styles = $this->get_inline_styles('color:red;font-size:14px');
	 * // Returns: 'color:red;font-size:14px'
	 *
	 * // Associative array of properties
	 * $styles = $this->get_inline_styles([
	 *     'color'       => 'blue',
	 *     'font-size'   => '16px',
	 *     'font-weight' => 'bold',
	 * ]);
	 * // Returns: 'color:blue;font-size:16px;font-weight:bold'
	 * ```
	 *
	 * @example Conditional styles with value filtering
	 * ```php
	 * $custom_color  = '#ff5733';
	 * $custom_margin = null;
	 * $is_visible    = true;
	 *
	 * $styles = $this->get_inline_styles([
	 *     'background-color' => $custom_color,   // Added
	 *     'margin'           => $custom_margin,  // Skipped (null)
	 *     'display'          => $is_visible,     // Skipped (bool)
	 *     'padding'          => '',              // Skipped (empty string)
	 *     'opacity'          => 0.8,             // Added (numeric value)
	 *     'z-index'          => 10,              // Added (integer value)
	 * ]);
	 * // Returns: 'background-color:#ff5733;opacity:0.8;z-index:10'
	 * ```
	 *
	 * @example Merging defaults with overrides
	 * ```php
	 * $default_styles = [
	 *     'color'      => 'black',
	 *     'font-size'  => '14px',
	 *     'padding'    => '10px',
	 * ];
	 *
	 * $user_styles = [
	 *     'color'         => 'navy',      // Overrides default
	 *     'border-radius' => '4px',       // New property
	 * ];
	 *
	 * $raw_style = 'text-transform:uppercase';
	 *
	 * $styles = $this->get_inline_styles(
	 *     $default_styles,
	 *     $user_styles,
	 *     $raw_style,
	 *     ['line-height:1.5'] // Numeric array with raw declaration
	 * );
	 * // Returns: 'text-transform:uppercase;line-height:1.5;color:navy;font-size:14px;padding:10px;border-radius:4px'
	 * // Note: Raw strings appear first, then merged associative properties (later values override earlier)
	 * ```
	 */
	public function get_inline_styles( ...$inline_styles_args ): string {
		$merged = array();
		$styles = array();

		foreach ( $inline_styles_args as $styles_array ) {

			if ( is_string( $styles_array ) && ! $this->is_empty_string( $styles_array ) ) {
				$styles[] = $styles_array;
				continue;
			}

			if ( ! is_array( $styles_array ) ) {
				continue;
			}

			foreach ( $styles_array as $property => $value ) {

				if ( is_int( $property ) ) {
					if ( is_string( $value ) && ! $this->is_empty_string( $value ) ) {
						$styles[] = $value;
					}
					continue;
				}

				if ( is_null( $value ) || is_bool( $value ) || is_array( $value ) ) {
					continue;
				}

				if ( is_string( $value ) && $this->is_empty_string( $value ) ) {
					continue;
				}

				$merged[ $property ] = $value;
			}
		}

		foreach ( $merged as $property => $value ) {
			$styles[] = sprintf( '%s:%s', esc_attr( $property ), esc_attr( $value ) );
		}

		return implode( ';', $styles );
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
	 * Recursively merges multiple associative arrays with intelligent deep merging.
	 *
	 * Unlike array_merge() or wp_parse_args(), this method preserves string keys
	 * and recursively merges nested arrays. When the same key exists in multiple
	 * arrays and both values are arrays, they are merged using wp_parse_args().
	 * Otherwise, later values overwrite earlier ones.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string|int, mixed> ...$arrays Variable number of arrays to merge.
	 *
	 * @return array<string|int, mixed> The merged array with nested arrays combined.
	 *
	 * @example Basic merge with nested arrays:
	 * ```php
	 * $defaults = [
	 *     'color'    => 'blue',
	 *     'settings' => [ 'enabled' => false, 'timeout' => 30 ],
	 * ];
	 * $custom = [
	 *     'color'    => 'red',
	 *     'settings' => [ 'enabled' => true ],
	 * ];
	 * $result = $this->array_merge( $defaults, $custom );
	 * // Result: [
	 * //     'color'    => 'red',
	 * //     'settings' => [ 'enabled' => true, 'timeout' => 30 ],
	 * // ]
	 * ```
	 *
	 * @example Merging three arrays with progressive overrides:
	 * ```php
	 * $base   = [ 'font' => [ 'size' => 14, 'family' => 'Arial' ] ];
	 * $theme  = [ 'font' => [ 'size' => 16 ], 'color' => 'black' ];
	 * $user   = [ 'font' => [ 'weight' => 'bold' ] ];
	 * $result = $this->array_merge( $base, $theme, $user );
	 * // Result: [
	 * //     'font'  => [ 'size' => 16, 'family' => 'Arial', 'weight' => 'bold' ],
	 * //     'color' => 'black',
	 * // ]
	 * ```
	 *
	 * @example Non-array values overwrite completely:
	 * ```php
	 * $first  = [ 'items' => [ 'a', 'b', 'c' ], 'count' => 3 ];
	 * $second = [ 'items' => [ 'x', 'y' ], 'count' => 2 ];
	 * $result = $this->array_merge( $first, $second );
	 * // Result: [ 'items' => [ 'x', 'y' ], 'count' => 2 ]
	 * // Note: Indexed arrays are replaced, not merged.
	 * ```
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
	 * Retrieves a list of HTML boolean (valueless) attributes.
	 *
	 * Returns an array of HTML attributes that are boolean in nature and don't
	 * require a value (e.g., `<input required>` instead of `<input required="required">`).
	 * Custom attributes can be merged with the default list.
	 *
	 * Note: 'checked' and 'selected' are intentionally excluded because WordPress's
	 * checked() and selected() helper functions return `checked="checked"` and
	 * `selected="selected"` respectively, not empty attributes.
	 *
	 * @param string[] $attributes Optional. Additional boolean attributes
	 *                                          to merge with defaults. Default empty array.
	 *
	 * @return string[] Combined array of boolean attribute names.
	 *
	 * @since 1.0.0
	 *
	 * @link https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#boolean-attributes
	 *
	 * @example Get default valueless attributes:
	 * ```php
	 * $attributes = $this->get_html_valueless_attributes();
	 * // Result: [
	 * //     'allowfullscreen',
	 * //     'autofocus',
	 * //     'default',
	 * //     'formnovalidate',
	 * //     'inert',
	 * //     'itemscope',
	 * //     'multiple',
	 * //     'required',
	 * //     'open',
	 * //     'hidden',
	 * //     'contenteditable',
	 * //     'draggable',
	 * // ]
	 *
	 * if ( in_array( 'required', $attributes, true ) ) {
	 *     // Handle as valueless attribute.
	 * }
	 * ```
	 *
	 * @example Add custom boolean attributes:
	 * ```php
	 * $custom = [
	 *     'disabled',
	 *     'readonly',
	 *     'novalidate',
	 * ];
	 * $attributes = $this->get_html_valueless_attributes( $custom );
	 * // Result includes both default and custom attributes merged.
	 * ```
	 *
	 * @example Use with wp_kses attribute preparation:
	 * ```php
	 * $valueless = $this->get_html_valueless_attributes();
	 * $kses_attr = [];
	 *
	 * foreach ( [ 'type', 'name', 'required', 'autofocus' ] as $attr ) {
	 *     $kses_attr[ $attr ] = in_array( $attr, $valueless, true )
	 *         ? [ 'valueless' => 'y' ]
	 *         : true;
	 * }
	 * // Result: [
	 * //     'type'      => true,
	 * //     'name'      => true,
	 * //     'required'  => [ 'valueless' => 'y' ],
	 * //     'autofocus' => [ 'valueless' => 'y' ],
	 * // ]
	 * ```
	 */
	public function get_html_valueless_attributes( array $attributes = array() ): array {
		// DO NOT ADD: "checked" or "selected" as empty attribute.
		// WP checked and selected function return is not empty attribute.

		$default = array(
			'allowfullscreen',
			'autofocus',
			'default',
			'formnovalidate',
			'inert',
			'itemscope',
			'multiple',
			'required',
			'open',
			'hidden',
			'contenteditable',
			'draggable',
		);

		return $this->array_merge( $default, $attributes );
	}

	/**
	 * Prepares HTML tag attributes for use with wp_kses().
	 *
	 * Transforms a simplified attribute definition array into the format required
	 * by wp_kses(). Handles three attribute types:
	 * - Boolean/empty attributes (e.g., 'required', 'hidden') → marked as valueless
	 * - Attributes with specific options (arrays) → passed through as-is
	 * - Standard attributes (strings) → set to true (any value allowed)
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, list<string|array<string, mixed>>> $tags Simplified tag definitions.
	 *        Keys are tag names, values are lists of allowed attributes.
	 *
	 * @return array<string, array<string, true|array<string, mixed>>> Formatted array
	 *         compatible with wp_kses() $allowed_html parameter.
	 *
	 * @example Basic usage with common attributes:
	 * ```php
	 * $tags = [
	 *     'input' => [ 'type', 'name', 'value', 'required', 'autofocus' ],
	 *     'a'     => [ 'href', 'target', 'class' ],
	 * ];
	 * $allowed = $this->prepare_kses_args( $tags );
	 * // Result: [
	 * //     'input' => [
	 * //         'type'      => true,
	 * //         'name'      => true,
	 * //         'value'     => true,
	 * //         'required'  => [ 'valueless' => 'y' ],
	 * //         'autofocus' => [ 'valueless' => 'y' ],
	 * //     ],
	 * //     'a' => [
	 * //         'href'   => true,
	 * //         'target' => true,
	 * //         'class'  => true,
	 * //     ],
	 * // ]
	 * $clean_html = wp_kses( $html, $allowed );
	 * ```
	 *
	 * @example Mixed attribute types with validation rules:
	 * ```php
	 * $tags = [
	 *     'iframe' => [
	 *         'src',
	 *         'width',
	 *         'height',
	 *         'allowfullscreen',
	 *         'loading' => [ 'values' => [ 'lazy', 'eager' ] ],
	 *     ],
	 *     'details' => [ 'open', 'class', 'id' ],
	 * ];
	 * $allowed = $this->prepare_kses_args( $tags );
	 * // Result: [
	 * //     'iframe' => [
	 * //         'src'             => true,
	 * //         'width'           => true,
	 * //         'height'          => true,
	 * //         'allowfullscreen' => [ 'valueless' => 'y' ],
	 * //         'loading'         => [ 'values' => [ 'lazy', 'eager' ] ],
	 * //     ],
	 * //     'details' => [
	 * //         'open'  => [ 'valueless' => 'y' ],
	 * //         'class' => true,
	 * //         'id'    => true,
	 * //     ],
	 * // ]
	 * ```
	 *
	 * @example Form elements with multiple boolean attributes:
	 * ```php
	 * $tags = [
	 *     'select'   => [ 'name', 'id', 'multiple', 'required', 'autofocus' ],
	 *     'textarea' => [ 'name', 'rows', 'cols', 'required', 'contenteditable' ],
	 * ];
	 * $allowed = $this->prepare_kses_args( $tags );
	 * // Result: [
	 * //     'select' => [
	 * //         'name'      => true,
	 * //         'id'        => true,
	 * //         'multiple'  => [ 'valueless' => 'y' ],
	 * //         'required'  => [ 'valueless' => 'y' ],
	 * //         'autofocus' => [ 'valueless' => 'y' ],
	 * //     ],
	 * //     'textarea' => [
	 * //         'name'            => true,
	 * //         'rows'            => true,
	 * //         'cols'            => true,
	 * //         'required'        => [ 'valueless' => 'y' ],
	 * //         'contenteditable' => [ 'valueless' => 'y' ],
	 * //     ],
	 * // ]
	 * ```
	 *  @see wp_kses_check_attr_val()
	 */
	public function prepare_kses_args( array $tags = array() ): array {

		// DO NOT ADD: "checked" or "selected" as empty attribute.
		// WP checked and selected function return is not empty attribute.

		$empty_attributes = $this->get_html_valueless_attributes();

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
			'svg'   => array( 'class', 'style', 'aria-hidden', 'aria-labelledby', 'role', 'xmlns', 'width', 'height', 'viewbox' ),
			'g'     => array( 'fill' ),
			'title' => array( 'title' ),
			'path'  => array( 'd', 'fill' ),
			'table' => array( 'class', 'role' ),
		);

		$allowed_args = $this->prepare_kses_args( $tags );

		$extra_args = $this->prepare_kses_args( $args );

		return $this->array_merge( $defaults, $allowed_args, $extra_args );
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

		return $this->array_merge( $defaults, $allowed_args, $extra_args );
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

		return $this->array_merge( $defaults, $allowed_args, $extra_args );
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
	 * Recursively merges user arguments into default values with deep array support.
	 *
	 * Similar to wp_parse_args() but with true recursive merging for nested arrays.
	 * User-provided values in $args override corresponding values in  $defaults,
	 * while nested arrays are merged recursively rather than replaced entirely.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string|int, mixed> $args     User-provided arguments to merge.
	 * @param array<string|int, mixed> $defaults Default values to merge into.
	 *
	 * @return array<string|int, mixed> Merged array with user args taking precedence.
	 *
	 * @example Basic nested configuration merge:
	 * ```php
	 * $defaults = [
	 *     'enabled' => false,
	 *     'cache'   => [
	 *         'driver'  => 'file',
	 *         'ttl'     => 3600,
	 *         'prefix'  => 'app_',
	 *     ],
	 * ];
	 * $args = [
	 *     'enabled' => true,
	 *     'cache'   => [
	 *         'driver' => 'redis',
	 *     ],
	 * ];
	 * $config = $this->array_merge_deep( $args, $defaults );
	 * // Result: [
	 * //     'enabled' => true,
	 * //     'cache'   => [
	 * //         'driver' => 'redis',
	 * //         'ttl'    => 3600,
	 * //         'prefix' => 'app_',
	 * //     ],
	 * // ]
	 * ```
	 *
	 * @example Multi-level deep merge for block settings:
	 * ```php
	 * $defaults = [
	 *     'typography' => [
	 *         'font' => [
	 *             'family' => 'Arial',
	 *             'size'   => '16px',
	 *             'weight' => 400,
	 *         ],
	 *         'lineHeight' => 1.5,
	 *     ],
	 *     'spacing' => [
	 *         'padding' => '20px',
	 *     ],
	 * ];
	 * $args = [
	 *     'typography' => [
	 *         'font' => [
	 *             'size'   => '18px',
	 *             'weight' => 700,
	 *         ],
	 *     ],
	 * ];
	 * $settings = $this->array_merge_deep( $args, $defaults );
	 * // Result: [
	 * //     'typography' => [
	 * //         'font' => [
	 * //             'family' => 'Arial',
	 * //             'size'   => '18px',
	 * //             'weight' => 700,
	 * //         ],
	 * //         'lineHeight' => 1.5,
	 * //     ],
	 * //     'spacing' => [
	 * //         'padding' => '20px',
	 * //     ],
	 * // ]
	 * ```
	 *
	 * @example Non-array values completely replace defaults:
	 * ```php
	 * $defaults = [
	 *     'items'  => [ 'apple', 'banana', 'cherry' ],
	 *     'config' => [ 'debug' => false ],
	 * ];
	 * $args = [
	 *     'items'  => [ 'orange' ],
	 *     'config' => null,
	 * ];
	 * $result = $this->array_merge_deep( $args, $defaults );
	 * // Result: [
	 * //     'items'  => [ 'orange' ],
	 * //     'config' => null,
	 * // ]
	 * // Note: Non-associative arrays and non-array values replace entirely.
	 * ```
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
