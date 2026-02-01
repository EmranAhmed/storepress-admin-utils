<?php
	/**
	 * Block Support API Class
	 *
	 * Provides utility methods for extracting and processing WordPress block editor
	 * support styles (background, color, spacing, border, shadow, dimensions) from
	 * block attributes. Works with WordPress Style Engine to generate CSS classes
	 * and inline styles.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\AdminUtils\Helpers;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Traits\HelperMethodsTrait;

	/**
	 * Block Supports Helper Class.
	 *
	 * Extracts style properties from block attributes and converts them into
	 * CSS classes and declarations using WordPress Style Engine. Supports all
	 * major block editor style features including background, color, spacing,
	 * border, shadow, and dimensions.
	 *
	 * @name BlockSupports
	 *
	 * @since 1.0.0
	 *
	 * @see wp_style_engine_get_styles() WordPress Style Engine function.
	 */
class BlockSupports {

	use HelperMethodsTrait;

	/**
	 * Block attributes array.
	 *
	 * Contains all attributes passed to the block, including style-related
	 * attributes like backgroundColor, textColor, gradient, borderColor,
	 * and the nested style array with detailed styling options.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	private array $attributes;

	/**
	 * Constructor.
	 *
	 * Initializes the BlockSupports instance with block attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes containing style data.
	 *
	 * @example
	 * <code>
	 * // Initialize with block attributes from render callback.
	 * $supports = new BlockSupports( $attributes );
	 *
	 * // Initialize with custom attributes array.
	 * $supports = new BlockSupports( array(
	 *     'backgroundColor' => 'primary',
	 *     'textColor'       => 'white',
	 *     'style'           => array(
	 *         'spacing' => array( 'padding' => '20px' ),
	 *     ),
	 * ) );
	 * </code>
	 */
	public function __construct( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Get style props for specific supports.
	 *
	 * Processes specified block supports and returns CSS classes and declarations
	 * using WordPress Style Engine.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $supports List of supports to include.
	 *                           Valid values: 'background', 'color', 'spacing',
	 *                           'border', 'shadow', 'dimensions'.
	 *
	 * @return array{
	 *     classes: array<string, bool>,
	 *     classnames: string,
	 *     css: string,
	 *     declarations: array<string, string>
	 * } Array containing CSS classes, class names string, inline CSS, and declarations.
	 *
	 * @see self::get_all_styles() For getting all supports with optional exclusions.
	 * @see wp_style_engine_get_styles() WordPress function used internally.
	 *
	 * @example
	 * <code>
	 * $props = new BlockSupports( $attributes );
	 *
	 * // Get background styles only.
	 * $hero_props = $props->get_styles( array( 'background' ) );
	 * // Returns: ['classes' => ['has-background' => true], 'classnames' => 'has-background', ...]
	 *
	 * // Get multiple style supports for a card element.
	 * $card_props = $props->get_styles( array( 'border', 'shadow', 'spacing' ) );
	 *
	 * // Get color styles for content area.
	 * $content_props = $props->get_styles( array( 'color' ) );
	 *
	 * // Use in block render callback.
	 * $styles = $props->get_styles( array( 'background', 'color' ) );
	 * printf( '<div class="%s" style="%s">', esc_attr( $styles['classnames'] ), esc_attr( $styles['css'] ) );
	 * </code>
	 */
	public function get_styles( array $supports ): array {

		$support_styles = array();

		foreach ( $supports as $support ) {
			$method = "get_{$support}_style";
			if ( method_exists( $this, $method ) ) {
				$support_styles[ $support ] = $this->$method();
			}
		}

		$styles      = wp_style_engine_get_styles( $support_styles );
		$class_names = isset( $styles['classnames'] ) ? explode( ' ', $styles['classnames'] ) : array();

		// Explicitly add for background image.
		if ( isset( $styles['declarations']['background-image'] ) ) {
			$class_names[] = 'has-background';
		}

		$uniq_classnames = array_unique( $class_names );

		return array(
			'classes'      => array_fill_keys( $uniq_classnames, true ),
			'classnames'   => implode( ' ', $uniq_classnames ),
			'css'          => $styles['css'] ?? '',
			'declarations' => $styles['declarations'] ?? array(),
		);
	}

	/**
	 * Get all supports except excluded ones.
	 *
	 * Retrieves style props for all available supports, optionally excluding
	 * specified ones. Useful when you want most styles but need to handle
	 * certain supports separately.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $exclude Supports to exclude from processing.
	 *                          Valid values: 'background', 'color', 'spacing',
	 *                          'border', 'shadow', 'dimensions'.
	 *
	 * @return array{
	 *     classes: array<string, bool>,
	 *     classnames: string,
	 *     css: string,
	 *     declarations: array<string, string>
	 * } Array containing CSS classes, class names string, inline CSS, and declarations.
	 *
	 * @see self::get_styles() For getting specific supports only.
	 *
	 * @example
	 * <code>
	 * $props = new BlockSupports( $attributes );
	 *
	 * // Get all styles without any exclusions.
	 * $all_styles = $props->get_all_styles();
	 *
	 * // Get all styles except background (handle background separately).
	 * $wrapper_styles = $props->get_all_styles( array( 'background' ) );
	 *
	 * // Exclude multiple supports.
	 * $inner_styles = $props->get_all_styles( array( 'background', 'dimensions' ) );
	 *
	 * // Use in render callback.
	 * $styles = $props->get_all_styles( array( 'shadow' ) );
	 * echo '<div class="' . esc_attr( $styles['classnames'] ) . '">';
	 * </code>
	 */
	public function get_all_styles( array $exclude = array() ): array {
		$all_supports = array( 'background', 'color', 'spacing', 'border', 'shadow', 'dimensions' );
		$supports     = array_diff( $all_supports, $exclude );

		return $this->get_styles( $supports );
	}

	/**
	 * Get background style props.
	 *
	 * Extracts background-related style properties from block attributes including
	 * background image, size, position, repeat, and attachment. Automatically sets
	 * sensible defaults when background image is present.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Background style properties for Style Engine.
	 *                              Keys: backgroundImage, backgroundSize, backgroundPosition,
	 *                              backgroundRepeat, backgroundAttachment.
	 *
	 * @see wp-includes/block-supports/background.php WordPress core background support.
	 * @see self::get_styles() Main method that uses this internally.
	 *
	 * @example
	 * <code>
	 * // block.json configuration for background support:
	 * // "supports": {
	 * //     "background": {
	 * //         "backgroundImage": true,
	 * //         "backgroundSize": true,
	 * //         "backgroundRepeat": true,
	 * //         "__experimentalSkipSerialization": ["backgroundImage"]
	 * //     }
	 * // }
	 *
	 * $props = new BlockSupports( $attributes );
	 * $background = $props->get_background_style();
	 * // Returns: ['backgroundImage' => [...], 'backgroundSize' => 'cover', ...]
	 *
	 * // With contain size, position defaults to center.
	 * $attributes = array(
	 *     'style' => array(
	 *         'background' => array(
	 *             'backgroundImage' => array( 'url' => 'image.jpg' ),
	 *             'backgroundSize'  => 'contain',
	 *         ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $bg = $props->get_background_style();
	 * // backgroundPosition will be '50% 50%' (centered).
	 * </code>
	 */
	public function get_background_style(): array {

		/**
		 * In block.json:
		 * "supports": {
		 *     "background": {
		 *         "backgroundImage": true,
		 *         "backgroundSize": true,
		 *         "backgroundRepeat": true,
		 *         "__experimentalSkipSerialization": ["backgroundImage"]
		 *     }
		 * }
		 */

		$styles                         = array();
		$styles['backgroundImage']      = $this->attributes['style']['background']['backgroundImage'] ?? null;
		$styles['backgroundSize']       = $this->attributes['style']['background']['backgroundSize'] ?? null;
		$styles['backgroundPosition']   = $this->attributes['style']['background']['backgroundPosition'] ?? null;
		$styles['backgroundRepeat']     = $this->attributes['style']['background']['backgroundRepeat'] ?? null;
		$styles['backgroundAttachment'] = $this->attributes['style']['background']['backgroundAttachment'] ?? null;

		if ( ! is_null( $styles['backgroundImage'] ) ) {
			$styles['backgroundSize'] = $styles['backgroundSize'] ?? 'cover';

			// If the background size is set to `contain` and no position is set, set the position to `center`.
			if ( 'contain' === $styles['backgroundSize'] && ! $styles['backgroundPosition'] ) {
				$styles['backgroundPosition'] = '50% 50%';
			}
		}

		return $styles;
	}

	/**
	 * Get color style props.
	 *
	 * Extracts color-related style properties from block attributes including
	 * background color, gradient, and text color. Supports both preset colors
	 * (from theme palette) and custom color values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Color style properties for Style Engine.
	 *                              Keys: background, gradient, text.
	 *
	 * @see wp-includes/block-supports/colors.php WordPress core color support.
	 * @see self::get_styles() Main method that uses this internally.
	 *
	 * @example
	 * <code>
	 * // block.json configuration for color support:
	 * // "supports": {
	 * //     "color": {
	 * //         "text": true,
	 * //         "background": true,
	 * //         "gradients": true,
	 * //         "__experimentalSkipSerialization": ["gradients", "background"]
	 * //     }
	 * // }
	 *
	 * // With preset colors from theme palette.
	 * $attributes = array(
	 *     'backgroundColor' => 'primary',
	 *     'textColor'       => 'white',
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $color = $props->get_color_style();
	 * // Returns: ['background' => 'var:preset|color|primary', 'text' => 'var:preset|color|white']
	 *
	 * // With custom color values.
	 * $attributes = array(
	 *     'style' => array(
	 *         'color' => array(
	 *             'background' => '#ff0000',
	 *             'text'       => '#ffffff',
	 *         ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $color = $props->get_color_style();
	 * // Returns: ['background' => '#ff0000', 'text' => '#ffffff']
	 *
	 * // With gradient.
	 * $attributes = array( 'gradient' => 'vivid-cyan-blue-to-vivid-purple' );
	 * $props = new BlockSupports( $attributes );
	 * $color = $props->get_color_style();
	 * // Returns: ['gradient' => 'var:preset|gradient|vivid-cyan-blue-to-vivid-purple']
	 * </code>
	 */
	public function get_color_style(): array {

		/**
		 * In block.json:
		 * "supports": {
		 *     "color": {
		 *         "text": false,
		 *         "background": true,
		 *         "gradients": true,
		 *         "__experimentalSkipSerialization": ["gradients", "background"]
		 *     }
		 * }
		 */

		$styles = array();

		$preset_background_color = array_key_exists( 'backgroundColor', $this->attributes ) ? "var:preset|color|{$this->attributes['backgroundColor']}" : null;
		$custom_background_color = $this->attributes['style']['color']['background'] ?? null;

		$preset_gradient_color = array_key_exists( 'gradient', $this->attributes ) ? "var:preset|gradient|{$this->attributes['gradient']}" : null;
		$custom_gradient_color = $this->attributes['style']['color']['gradient'] ?? null;

		$preset_text_color = array_key_exists( 'textColor', $this->attributes ) ? "var:preset|color|{$this->attributes['textColor']}" : null;
		$custom_text_color = $this->attributes['style']['color']['text'] ?? null;

		$styles['background'] = $preset_background_color ?? $custom_background_color;
		$styles['gradient']   = $preset_gradient_color ?? $custom_gradient_color;
		$styles['text']       = $preset_text_color ?? $custom_text_color;

		return $styles;
	}

	/**
	 * Get spacing style props.
	 *
	 * Extracts spacing-related style properties from block attributes including
	 * padding, margin, and block gap values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Spacing style properties for Style Engine.
	 *                              Keys: padding, margin, blockGap.
	 *
	 * @see wp-includes/block-supports/spacing.php WordPress core spacing support.
	 * @see self::get_styles() Main method that uses this internally.
	 *
	 * @example
	 * <code>
	 * // block.json configuration for spacing support:
	 * // "supports": {
	 * //     "spacing": {
	 * //         "margin": ["top", "bottom"],
	 * //         "padding": true,
	 * //         "blockGap": true,
	 * //         "__experimentalSkipSerialization": true
	 * //     }
	 * // }
	 *
	 * // With uniform padding.
	 * $attributes = array(
	 *     'style' => array(
	 *         'spacing' => array( 'padding' => '20px' ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $spacing = $props->get_spacing_style();
	 * // Returns: ['padding' => '20px', 'margin' => null, 'blockGap' => null]
	 *
	 * // With individual side values.
	 * $attributes = array(
	 *     'style' => array(
	 *         'spacing' => array(
	 *             'padding' => array( 'top' => '10px', 'bottom' => '20px' ),
	 *             'margin'  => array( 'top' => '0', 'bottom' => '2rem' ),
	 *         ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $spacing = $props->get_spacing_style();
	 *
	 * // With preset spacing values.
	 * $attributes = array(
	 *     'style' => array(
	 *         'spacing' => array(
	 *             'blockGap' => 'var:preset|spacing|40',
	 *         ),
	 *     ),
	 * );
	 * </code>
	 */
	public function get_spacing_style(): array {

		/**
		 * In block.json:
		 * "supports": {
		 *     "spacing": {
		 *         "margin": ["top", "bottom"],
		 *         "padding": true,
		 *         "blockGap": true,
		 *         "__experimentalSkipSerialization": true
		 *     }
		 * }
		 */
		return array(
			'padding'  => $this->attributes['style']['spacing']['padding'] ?? null,
			'margin'   => $this->attributes['style']['spacing']['margin'] ?? null,
			'blockGap' => $this->attributes['style']['spacing']['blockGap'] ?? null,
		);
	}

	/**
	 * Get border style props.
	 *
	 * Extracts border-related style properties from block attributes including
	 * border radius, style, width, color, and individual side borders. Supports
	 * both preset border colors and custom color values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Border style properties for Style Engine.
	 *                              Keys: radius, style, width, color, top, right, bottom, left.
	 *
	 * @see wp-includes/block-supports/border.php WordPress core border support.
	 * @see self::get_styles() Main method that uses this internally.
	 *
	 * @example
	 * <code>
	 * // block.json configuration for border support:
	 * // "supports": {
	 * //     "__experimentalBorder": {
	 * //         "color": true,
	 * //         "radius": true,
	 * //         "style": true,
	 * //         "width": true,
	 * //         "__experimentalSkipSerialization": ["color", "radius", "style", "width"]
	 * //     }
	 * // }
	 *
	 * // With uniform border.
	 * $attributes = array(
	 *     'borderColor' => 'primary',
	 *     'style'       => array(
	 *         'border' => array(
	 *             'width'  => '2px',
	 *             'style'  => 'solid',
	 *             'radius' => '8px',
	 *         ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $border = $props->get_border_style();
	 * // Returns: ['color' => 'var:preset|color|primary', 'width' => '2px', ...]
	 *
	 * // With individual side borders.
	 * $attributes = array(
	 *     'style' => array(
	 *         'border' => array(
	 *             'top'    => array( 'width' => '1px', 'color' => '#000', 'style' => 'solid' ),
	 *             'bottom' => array( 'width' => '2px', 'color' => '#333', 'style' => 'dashed' ),
	 *         ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $border = $props->get_border_style();
	 *
	 * // With custom border radius values per corner.
	 * $attributes = array(
	 *     'style' => array(
	 *         'border' => array(
	 *             'radius' => array(
	 *                 'topLeft'     => '10px',
	 *                 'bottomRight' => '10px',
	 *             ),
	 *         ),
	 *     ),
	 * );
	 * </code>
	 */
	public function get_border_style(): array {

		/**
		 * In block.json:
		 * "supports": {
		 *     "__experimentalBorder": {
		 *         "color": true,
		 *         "radius": true,
		 *         "style": true,
		 *         "width": true,
		 *         "__experimentalSkipSerialization": ["color", "radius", "style", "width"]
		 *     }
		 * }
		 */

		$preset_border_color = array_key_exists( 'borderColor', $this->attributes ) ? "var:preset|color|{$this->attributes['borderColor']}" : null;
		$custom_border_color = $this->attributes['style']['border']['color'] ?? null;

		$styles = array(
			'radius' => $this->attributes['style']['border']['radius'] ?? null,
			'style'  => $this->attributes['style']['border']['style'] ?? null,
			'width'  => $this->attributes['style']['border']['width'] ?? null,
			'color'  => $preset_border_color ?? $custom_border_color,
		);

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$border             = $this->attributes['style']['border'][ $side ] ?? null;
			$border_side_values = array(
				'width' => isset( $border['width'] ) ? $border['width'] : null,
				'color' => isset( $border['color'] ) ? $border['color'] : null,
				'style' => isset( $border['style'] ) ? $border['style'] : null,
			);
			$styles[ $side ]    = $border_side_values;
		}

		return $styles;
	}

	/**
	 * Get shadow style props.
	 *
	 * Extracts shadow style property from block attributes. Returns the
	 * box-shadow value which can be a preset reference or custom shadow string.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Shadow style value or null if not set.
	 *
	 * @see wp-includes/block-supports/shadow.php WordPress core shadow support.
	 * @see self::get_styles() Main method that uses this internally.
	 *
	 * @example
	 * <code>
	 * // block.json configuration for shadow support:
	 * // "supports": {
	 * //     "shadow": {
	 * //         "__experimentalSkipSerialization": true
	 * //     }
	 * // }
	 *
	 * // With preset shadow from theme.
	 * $attributes = array(
	 *     'style' => array(
	 *         'shadow' => 'var:preset|shadow|natural',
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $shadow = $props->get_shadow_style();
	 * // Returns: 'var:preset|shadow|natural'
	 *
	 * // With custom shadow value.
	 * $attributes = array(
	 *     'style' => array(
	 *         'shadow' => '0 4px 6px rgba(0, 0, 0, 0.1)',
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $shadow = $props->get_shadow_style();
	 * // Returns: '0 4px 6px rgba(0, 0, 0, 0.1)'
	 *
	 * // When no shadow is set.
	 * $props = new BlockSupports( array() );
	 * $shadow = $props->get_shadow_style();
	 * // Returns: null
	 * </code>
	 */
	public function get_shadow_style(): ?string {

		/**
		 * In block.json:
		 * "supports": {
		 *     "shadow": {
		 *         "__experimentalSkipSerialization": true
		 *     }
		 * }
		 */
		return $this->attributes['style']['shadow'] ?? null;
	}

	/**
	 * Get dimensions style props.
	 *
	 * Extracts dimension-related style properties from block attributes including
	 * minimum height and aspect ratio. Handles the mutual exclusivity between
	 * minHeight and aspectRatio to prevent conflicts.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Dimensions style properties for Style Engine.
	 *                              Keys: minHeight, aspectRatio.
	 *
	 * @see wp-includes/block-supports/dimensions.php WordPress core dimensions support.
	 * @see self::get_styles() Main method that uses this internally.
	 *
	 * @example
	 * <code>
	 * // block.json configuration for dimensions support:
	 * // "supports": {
	 * //     "dimensions": {
	 * //         "minHeight": true,
	 * //         "aspectRatio": true
	 * //     }
	 * // }
	 *
	 * // With minimum height.
	 * $attributes = array(
	 *     'minHeight' => '400px',
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $dimensions = $props->get_dimensions_style();
	 * // Returns: ['minHeight' => '400px', 'aspectRatio' => 'unset']
	 *
	 * // With aspect ratio (minHeight gets unset).
	 * $attributes = array(
	 *     'style' => array(
	 *         'dimensions' => array(
	 *             'aspectRatio' => '16/9',
	 *         ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $dimensions = $props->get_dimensions_style();
	 * // Returns: ['minHeight' => 'unset', 'aspectRatio' => ...]
	 *
	 * // With nested minHeight in style.
	 * $attributes = array(
	 *     'style' => array(
	 *         'dimensions' => array(
	 *             'minHeight' => '500px',
	 *         ),
	 *     ),
	 * );
	 * $props = new BlockSupports( $attributes );
	 * $dimensions = $props->get_dimensions_style();
	 * </code>
	 */
	public function get_dimensions_style(): array {

		/**
		 * In block.json:
		 * "supports": {
		 *     "dimensions": {
		 *         "minHeight": true,
		 *         "aspectRatio": true
		 *     }
		 * }
		 */

		$styles                = array();
		$styles['minHeight']   = ( $this->attributes['minHeight'] ?? null ) || ( $this->attributes['style']['dimensions']['minHeight'] ?? null );
		$styles['aspectRatio'] = $this->attributes['style']['dimensions']['minHeight'] ?? null;

		// To ensure the aspect ratio does not get overridden by `minHeight` unset any existing rule.
		if ( isset( $this->attributes['style']['dimensions']['aspectRatio'] ) ) {
			$styles['minHeight'] = 'unset';
		} elseif (
			isset( $this->attributes['style']['dimensions']['minHeight'] ) ||
			isset( $this->attributes['minHeight'] )
		) {
			$styles['aspectRatio'] = 'unset';
		}

		return $styles;
	}
}
