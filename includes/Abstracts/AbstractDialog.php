<?php
	/**
	 * Abstract Dialog Class File.
	 *
	 * Provides a base implementation for creating modal dialog boxes in the WordPress admin.
	 * This class handles dialog rendering, script/style enqueuing, and button generation
	 * using the native HTML `<dialog>` element with WordPress styling conventions.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    3.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractDialog' ) ) {
	/**
	 * Abstract Dialog Class.
	 *
	 * This abstract class provides a complete implementation for rendering modal dialogs
	 * in the WordPress admin area. It uses the native HTML `<dialog>` element and integrates
	 * with WordPress admin styling for a consistent user experience.
	 *
	 * Subclasses must implement:
	 * - `id()`: Unique identifier for the dialog element.
	 * - `title()`: The dialog header title.
	 * - `content()`: The main dialog body content.
	 *
	 * Optionally override:
	 * - `get_buttons()`: Customize the dialog action buttons.
	 * - `get_sub_title()`: Add a subtitle below the title.
	 * - `width()`: Set a custom dialog width.
	 * - `has_capability()`: Control who can see the dialog.
	 * - `use_form()`: Enable or disable form wrapping.
	 * - `init()`: Add custom initialization logic.
	 *
	 * @name AbstractDialog
	 *
	 * @phpstan-use HelperMethodsTrait<AbstractDialog>
	 * @phpstan-use InternalPackageTrait<AbstractDialog>
	 * @phpstan-use MethodShouldImplementTrait<AbstractDialog>
	 *
	 * @see AbstractDeactivationFeedback For a concrete implementation example.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractDialog {

		use HelperMethodsTrait;
		use InternalPackageTrait;
		use MethodShouldImplementTrait;

		// =====================================================================
		// Constructor and Initialization Methods
		// =====================================================================

		/**
		 * Initialize the dialog handler.
		 *
		 * Sets up WordPress action hooks for script/style enqueuing and dialog
		 * rendering in the admin footer. Calls the `init()` method for subclass
		 * custom initialization.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Create a custom dialog:
		 * class MyCustomDialog extends AbstractDialog {
		 *     public function id(): string {
		 *         return 'my-custom-dialog';
		 *     }
		 *     public function title(): string {
		 *         return 'My Dialog Title';
		 *     }
		 *     public function content(): string {
		 *         return '<p>Dialog content here.</p>';
		 *     }
		 * }
		 *
		 * // Initialize on appropriate hook:
		 * add_action( 'admin_init', function() {
		 *     new MyCustomDialog();
		 * } );
		 */
		public function __construct() {
			$this->hooks();
			$this->init();
		}

		/**
		 * Register WordPress action hooks.
		 *
		 * Sets up hooks for enqueuing scripts/styles and rendering the dialog
		 * HTML in the admin footer. Both hooks use priority 20 to ensure
		 * dependencies are loaded first.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::enqueue_scripts() For script/style registration.
		 * @see self::render() For dialog HTML output.
		 */
		final public function hooks(): void {
			// Enqueue dialog scripts and styles with priority 20.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

			// Render dialog HTML in the admin footer.
			add_action( 'admin_footer', array( $this, 'render' ), 20 );
		}

		/**
		 * Custom initialization hook for subclasses.
		 *
		 * Override this method in subclasses to add custom initialization logic
		 * that runs after the constructor completes and hooks are registered.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // In your subclass:
		 * public function init(): void {
		 *     // Add custom initialization
		 *     $this->load_additional_data();
		 * }
		 */
		public function init(): void {}

		// =====================================================================
		// Abstract Methods (Must Be Implemented by Subclasses)
		// =====================================================================

		/**
		 * Get the unique dialog element ID.
		 *
		 * This method must be implemented by subclasses to provide a unique
		 * identifier for the dialog HTML element. This ID is used for CSS
		 * targeting and JavaScript interaction.
		 *
		 * @return string The unique dialog ID (without '#' prefix).
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Simple ID:
		 * public function id(): string {
		 *     return 'my-settings-dialog';
		 * }
		 *
		 * // Dynamic ID based on context:
		 * public function id(): string {
		 *     return sprintf( '%s-confirmation-dialog', $this->get_plugin_slug() );
		 * }
		 */
		abstract public function id(): string;

		/**
		 * Get the dialog title text.
		 *
		 * This method must be implemented by subclasses to provide the title
		 * displayed in the dialog header.
		 *
		 * @return string The dialog title text.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Simple title:
		 * public function title(): string {
		 *     return 'Confirmation';
		 * }
		 *
		 * // Translated title:
		 * public function title(): string {
		 *     return esc_html__( 'Settings', 'my-plugin' );
		 * }
		 */
		abstract public function title(): string;

		/**
		 * Get the dialog body content.
		 *
		 * This method must be implemented by subclasses to provide the main
		 * content displayed in the dialog body. Can include any valid HTML.
		 *
		 * @return string The dialog content HTML.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Simple content:
		 * public function content(): string {
		 *     return '<p>Are you sure you want to proceed?</p>';
		 * }
		 *
		 * // Complex content with form fields:
		 * public function content(): string {
		 *     ob_start();
		 *     ?>
		 *     <div class="form-fields">
		 *         <label for="name">Name:</label>
		 *         <input type="text" id="name" name="name" />
		 *     </div>
		 *     <?php
		 *     return ob_get_clean();
		 * }
		 */
		abstract public function content(): string;

		// =====================================================================
		// Public Getter Methods
		// =====================================================================

		/**
		 * Get the dialog element ID.
		 *
		 * Public accessor for the abstract `id()` method.
		 *
		 * @return string The dialog element ID.
		 *
		 * @since 1.0.0
		 *
		 * @see self::id() The abstract method this wraps.
		 */
		public function get_id(): string {
			return $this->id();
		}

		/**
		 * Get the dialog title.
		 *
		 * Public accessor for the abstract `title()` method.
		 *
		 * @return string The dialog title text.
		 *
		 * @since 1.0.0
		 *
		 * @see self::title() The abstract method this wraps.
		 */
		public function get_title(): string {
			return $this->title();
		}

		/**
		 * Get the dialog subtitle.
		 *
		 * Override this method to add a subtitle below the main title.
		 * Returns an empty string by default (no subtitle).
		 *
		 * @return string The subtitle text, or empty string for no subtitle.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Add a subtitle:
		 * public function get_sub_title(): string {
		 *     return 'Please review before continuing.';
		 * }
		 */
		public function get_sub_title(): string {
			return '';
		}

		/**
		 * Get the dialog content.
		 *
		 * Public accessor for the abstract `content()` method.
		 *
		 * @return string The dialog body content HTML.
		 *
		 * @since 1.0.0
		 *
		 * @see self::content() The abstract method this wraps.
		 */
		public function get_content(): string {
			return $this->content();
		}

		// =====================================================================
		// Dialog Configuration Methods
		// =====================================================================

		/**
		 * Get the dialog width CSS value.
		 *
		 * Override this method to specify a custom width for the dialog.
		 * Return an empty string to use the default width defined in CSS.
		 *
		 * @return string CSS width value (e.g., '500px', '50%', '30rem') or empty string.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Fixed pixel width:
		 * public function width(): string {
		 *     return '600px';
		 * }
		 *
		 * // Responsive percentage width:
		 * public function width(): string {
		 *     return '80%';
		 * }
		 *
		 * // Use default width:
		 * public function width(): string {
		 *     return '';
		 * }
		 */
		public function width(): string {
			return '';
		}

		/**
		 * Check if the current user has capability to view the dialog.
		 *
		 * Override this method to implement capability checks. If this returns
		 * false, the dialog will not be rendered and scripts won't be enqueued.
		 *
		 * @return bool True if user can view the dialog, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Require admin capability:
		 * public function has_capability(): bool {
		 *     return current_user_can( 'manage_options' );
		 * }
		 *
		 * // Check specific screen:
		 * public function has_capability(): bool {
		 *     $screen = get_current_screen();
		 *     return $screen && $screen->id === 'my-plugin-page';
		 * }
		 *
		 * @see self::enqueue_scripts() Where this check is performed.
		 * @see self::render() Where this check is performed.
		 */
		public function has_capability(): bool {

			$this->subclass_should_implement( __FUNCTION__ );

			return true;
		}

		// =====================================================================
		// Button Configuration Methods
		// =====================================================================

		/**
		 * Get the dialog action buttons configuration.
		 *
		 * Returns an array of button configurations for the dialog footer.
		 * Override this method in subclasses to customize the buttons displayed.
		 *
		 * Each button configuration supports:
		 * - `type`: 'button' for `<button>` element or 'link' for `<a>` element.
		 * - `label`: The visible button text.
		 * - `attributes`: HTML attributes array (class, href, type, data-*, etc.).
		 * - `spinner`: (optional) Boolean to show a loading spinner (buttons only).
		 *
		 * Special data attributes:
		 * - `data-action="submit"`: Triggers form submission.
		 * - `data-action="close"`: Closes the dialog.
		 * - `data-label`: Original button text (for restoring after processing).
		 * - `data-processing`: Text shown while processing.
		 *
		 * @return array<int, array{type: string, label: string, attributes: array<string, mixed>, spinner?: bool}> Button configurations.
		 *
		 * @throws \WP_Exception Logs a notice that subclass should implement this method.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Default save/close buttons:
		 * public function get_buttons(): array {
		 *     return array(
		 *         array(
		 *             'type'       => 'button',
		 *             'label'      => __( 'Save' ),
		 *             'attributes' => array(
		 *                 'type'            => 'submit',
		 *                 'data-action'     => 'submit',
		 *                 'data-label'      => __( 'Save' ),
		 *                 'data-processing' => __( 'Saving...' ),
		 *                 'class'           => array( 'button', 'button-primary' ),
		 *             ),
		 *             'spinner'    => true,
		 *         ),
		 *         array(
		 *             'type'       => 'button',
		 *             'label'      => __( 'Close' ),
		 *             'attributes' => array(
		 *                 'type'        => 'button',
		 *                 'data-action' => 'close',
		 *                 'class'       => array( 'button', 'button-secondary' ),
		 *             ),
		 *         ),
		 *     );
		 * }
		 *
		 * // Link buttons (e.g., for external actions):
		 * public function get_buttons(): array {
		 *     return array(
		 *         array(
		 *             'type'       => 'link',
		 *             'label'      => __( 'Buy Now' ),
		 *             'attributes' => array(
		 *                 'href'   => 'https://example.com/buy',
		 *                 'target' => '_blank',
		 *                 'class'  => array( 'button', 'button-primary' ),
		 *             ),
		 *         ),
		 *         array(
		 *             'type'       => 'link',
		 *             'label'      => __( 'Documentation' ),
		 *             'attributes' => array(
		 *                 'href'  => 'https://docs.example.com',
		 *                 'class' => array( 'button', 'button-secondary' ),
		 *             ),
		 *         ),
		 *     );
		 * }
		 *
		 * // No buttons (content-only dialog):
		 * public function get_buttons(): array {
		 *     return array();
		 * }
		 */
		public function get_buttons(): array {

			$this->subclass_should_implement( __FUNCTION__ );

			return array(
				array(
					'type'       => 'button', // button | link.
					'label'      => __( 'Save' ),
					'attributes' => array(
						'type'            => 'submit',
						'data-action'     => 'submit',
						'data-label'      => __( 'Save' ),
						'data-processing' => __( 'Saving...' ),
						'class'           => array( 'button', 'button-primary' ),
					),
					'spinner'    => true,
				),
				array(
					'type'       => 'button', // button | link.
					'label'      => __( 'Close' ),
					'attributes' => array(
						'type'        => 'button',
						'data-action' => 'close',
						'class'       => array( 'button', 'button-secondary' ),
					),
				),
			);
		}

		/**
		 * Generate the HTML markup for all dialog buttons.
		 *
		 * Iterates through the button configurations from `get_buttons()` and
		 * generates the appropriate HTML for each button or link element.
		 *
		 * @return string The concatenated HTML markup for all buttons.
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_buttons() For button configuration source.
		 * @see HelperMethodsTrait::get_html_attributes() For attribute generation.
		 */
		public function generate_button_markup(): string {

			$buttons = $this->has_buttons() ? $this->get_buttons() : array();
			$html    = array();
			foreach ( $buttons as $button ) {
				$is_button   = 'button' === $button['type'];
				$label       = $button['label'];
				$has_spinner = $is_button && isset( $button['spinner'] ) && $button['spinner'];
				$attributes  = $button['attributes'];

				$html[] = $is_button ? sprintf( '<button %s>', $this->get_html_attributes( $attributes ) ) : sprintf( '<a %s>', $this->get_html_attributes( $attributes ) );
				$html[] = $has_spinner ? '<span class="spinner"></span>' : '';
				$html[] = sprintf( '<span class="button-text">%s</span>', esc_html( $label ) );
				$html[] = $is_button ? '</button>' : '</a>';
			}

			return implode( '', $html );
		}

		// =====================================================================
		// Form Wrapper Methods
		// =====================================================================

		/**
		 * Determine if the dialog content should be wrapped in a form element.
		 *
		 * Override this method to disable form wrapping for dialogs that don't
		 * need form submission functionality. When false and no buttons exist,
		 * the form wrapper is omitted entirely.
		 *
		 * @return bool True to wrap content in a form, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Disable form wrapper for informational dialogs:
		 * public function use_form(): bool {
		 *     return false;
		 * }
		 *
		 * @see self::wrapper_start() Where this is checked.
		 * @see self::wrapper_end() Where this is checked.
		 */
		public function use_form(): bool {
			return true;
		}

		/**
		 * Generate the opening form wrapper tag.
		 *
		 * Returns the opening `<form>` tag with `method="dialog"` attribute if
		 * form wrapping is enabled or buttons exist. The dialog method allows
		 * the form to close the dialog on submission.
		 *
		 * @return string The opening form tag HTML, or empty string if not needed.
		 *
		 * @since 1.0.0
		 *
		 * @see self::use_form() For form wrapping preference.
		 * @see self::has_buttons() For button existence check.
		 * @see self::wrapper_end() For the closing tag.
		 */
		public function wrapper_start(): string {

			if ( ! $this->use_form() && ! $this->has_buttons() ) {
				return '';
			}

			$attrs = array(
				'method' => 'dialog',
			);

			return sprintf( '<form %s>', $this->get_html_attributes( $attrs ) );
		}

		/**
		 * Generate the closing form wrapper tag.
		 *
		 * Returns the closing `</form>` tag if form wrapping is enabled or
		 * buttons exist.
		 *
		 * @return string The closing form tag HTML, or empty string if not needed.
		 *
		 * @since 1.0.0
		 *
		 * @see self::use_form() For form wrapping preference.
		 * @see self::has_buttons() For button existence check.
		 * @see self::wrapper_start() For the opening tag.
		 */
		public function wrapper_end(): string {
			if ( ! $this->use_form() && ! $this->has_buttons() ) {
				return '';
			}

			return '</form>';
		}

		/**
		 * Check if the dialog has any buttons configured.
		 *
		 * @return bool True if buttons are configured, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_buttons() For button configuration.
		 * @see HelperMethodsTrait::is_empty_array() For empty check.
		 */
		public function has_buttons(): bool {
			return ! $this->is_empty_array( $this->get_buttons() );
		}

		// =====================================================================
		// Script and Style Methods
		// =====================================================================

		/**
		 * Enqueue scripts and styles for the dialog.
		 *
		 * Registers and enqueues the dialog package scripts and styles.
		 * Only loads assets if the current user has the required capability.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::has_capability() For capability check.
		 * @see InternalPackageTrait::register_package_scripts() For script registration.
		 * @see InternalPackageTrait::enqueue_package_scripts() For script enqueuing.
		 */
		public function enqueue_scripts(): void {
			if ( ! $this->has_capability() ) {
				return;
			}
			$this->register_package_scripts( 'dialog' );
			$this->enqueue_package_scripts( 'dialog' );
		}

		// =====================================================================
		// Rendering Methods
		// =====================================================================

		/**
		 * Render the dialog HTML markup.
		 *
		 * Includes the dialog-box.php template file which outputs the complete
		 * dialog HTML structure. Only renders if the current user has capability.
		 *
		 * The template has access to `$this` and can call all public methods
		 * to retrieve dialog configuration (id, title, content, buttons, etc.).
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::has_capability() For capability check.
		 * @see InternalPackageTrait::get_package_templates_path() For template location.
		 */
		public function render(): void {
			if ( ! $this->has_capability() ) {
				return;
			}

			include $this->get_package_templates_path() . '/dialog-box.php';
		}
	}
}
