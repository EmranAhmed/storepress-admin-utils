<?php
	/**
	 * Abstract Deactivation Feedback Class File.
	 *
	 * Provides a base implementation for collecting user feedback when a plugin is deactivated.
	 * This class handles the display of a feedback dialog on the plugins page and sends
	 * the collected data to a remote API endpoint.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    3.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Interfaces\HasServiceProviderInterface;

	use StorePress\AdminUtils\ServiceProviders\Internal\DeactivationFeedbackServiceProvider;

	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;
	use StorePress\AdminUtils\Traits\RegisterServiceProviderTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;
	use StorePress\AdminUtils\Traits\PluginCommonTrait;
	use WP_Exception;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractDeactivationFeedback' ) ) {
	/**
	 * Abstract Deactivation Feedback Class.
	 *
	 * This abstract class provides a complete implementation for displaying a feedback dialog
	 * when users attempt to deactivate a plugin. It collects deactivation reasons, optional
	 * user input, and system information, then sends this data to a configurable API endpoint.
	 *
	 * Subclasses must implement:
	 * - `title()`: The dialog title displayed to users.
	 * - `api_url()`: The REST API endpoint to receive feedback data.
	 * - `options()`: Plugin settings to include in the feedback payload.
	 *
	 * Optionally override:
	 * - `get_reasons()`: Customize the list of deactivation reasons.
	 * - `get_buttons()`: Customize the dialog action buttons.
	 * - `sub_title()`: Add a subtitle to the dialog.
	 * - `init()`: Add custom initialization logic.
	 *
	 * @name AbstractDeactivationFeedback
	 *
	 * @method DeactivationFeedbackServiceProvider get_service_provider() Returns the DeactivationFeedbackServiceProvider instance that owns this provider.
	 *
	 * @phpstan-use HelperMethodsTrait<AbstractDeactivationFeedback>
	 * @phpstan-use PluginCommonTrait<AbstractDeactivationFeedback>
	 * @phpstan-use InternalPackageTrait<AbstractDeactivationFeedback>
	 * @phpstan-use RegisterServiceProviderTrait<AbstractDeactivationFeedback>
	 * @phpstan-use CallerTrait<AbstractDeactivationFeedback>
	 * @phpstan-use MethodShouldImplementTrait<AbstractDeactivationFeedback>
	 *
	 * @see DeactivationFeedbackServiceProvider For service provider implementation.
	 * @see HasServiceProviderInterface For the interface contract.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractDeactivationFeedback implements HasServiceProviderInterface {

		use HelperMethodsTrait;
		use PluginCommonTrait;
		use InternalPackageTrait;
		use RegisterServiceProviderTrait;
		use CallerTrait;
		use MethodShouldImplementTrait;

		// =====================================================================
		// Constructor and Initialization Methods
		// =====================================================================

		/**
		 * Initialize the deactivation feedback handler.
		 *
		 * Sets up the caller reference, registers the service provider, and initializes
		 * all necessary hooks for displaying the feedback dialog on the plugins page.
		 *
		 * @param object $caller The caller object (typically the main plugin class instance).
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // In your plugin's main class:
		 * class MyPluginDeactivationFeedback extends AbstractDeactivationFeedback {
		 *     public function title(): string {
		 *         return 'Quick Feedback';
		 *     }
		 *     public function api_url(): string {
		 *         return 'https://example.com/wp-json/myplugin/v1/deactivate';
		 *     }
		 *     public function options(): array {
		 *         return get_option( 'my_plugin_settings', array() );
		 *     }
		 * }
		 *
		 * // Initialize:
		 * new MyPluginDeactivationFeedback( $this );
		 */
		public function __construct( object $caller ) {
			$this->set_caller( $caller );
			$this->register_service_provider( $this );
			$this->register_services();
			$this->hooks();
			$this->init();
		}

		/**
		 * Custom initialization hook for subclasses.
		 *
		 * Override this method in subclasses to add custom initialization logic
		 * that runs after the constructor completes. This is called after hooks
		 * are registered and services are set up.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // In your subclass:
		 * public function init(): void {
		 *     // Add custom initialization logic
		 *     $this->load_additional_resources();
		 * }
		 */
		public function init(): void {}

		// =====================================================================
		// Service Provider Methods
		// =====================================================================

		/**
		 * Create and return the service provider instance.
		 *
		 * This method is called during initialization to create the service provider
		 * that manages the deactivation feedback services (dialog rendering, etc.).
		 *
		 * @param object $caller The caller class instance passed to the service provider.
		 *
		 * @return DeactivationFeedbackServiceProvider The configured service provider instance.
		 *
		 * @since 1.0.0
		 *
		 * @see DeactivationFeedbackServiceProvider
		 * @see RegisterServiceProviderTrait::register_service_provider()
		 */
		public function service_provider( object $caller ): DeactivationFeedbackServiceProvider {
			return new DeactivationFeedbackServiceProvider( $caller );
		}

		// =====================================================================
		// Abstract Methods (Must Be Implemented by Subclasses)
		// =====================================================================

		/**
		 * Get the deactivation dialog title.
		 *
		 * This method must be implemented by subclasses to provide the title
		 * displayed at the top of the feedback dialog.
		 *
		 * @return string The dialog title text.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Simple title:
		 * public function title(): string {
		 *     return 'Quick Feedback';
		 * }
		 *
		 * // Translated title:
		 * public function title(): string {
		 *     return esc_html__( 'Why are you deactivating?', 'my-plugin' );
		 * }
		 *
		 * // Plugin-specific title:
		 * public function title(): string {
		 *     return sprintf( 'Deactivating %s', $this->get_plugin_name() );
		 * }
		 */
		abstract public function title(): string;

		/**
		 * Get the API URL endpoint to send feedback data.
		 *
		 * This method must be implemented by subclasses to specify the REST API
		 * endpoint that will receive the deactivation feedback payload.
		 *
		 * @return string The full URL to the feedback API endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Basic REST API endpoint:
		 * public function api_url(): string {
		 *     return 'https://example.com/wp-json/myplugin/v1/deactivate';
		 * }
		 *
		 * // Dynamic endpoint based on environment:
		 * public function api_url(): string {
		 *     $base = defined( 'WP_DEBUG' ) && WP_DEBUG
		 *         ? 'https://staging.example.com'
		 *         : 'https://api.example.com';
		 *     return $base . '/wp-json/feedback/v1/deactivate';
		 * }
		 *
		 * // Using a constant:
		 * public function api_url(): string {
		 *     return MY_PLUGIN_FEEDBACK_API_URL;
		 * }
		 */
		abstract public function api_url(): string;

		/**
		 * Get the plugin settings to include in the feedback payload.
		 *
		 * This method must be implemented by subclasses to provide the current
		 * plugin settings that will be sent along with the deactivation feedback.
		 * This helps identify configuration issues that may have led to deactivation.
		 *
		 * @return array<string, mixed> The plugin settings array.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Return stored options:
		 * public function options(): array {
		 *     return get_option( 'my_plugin_settings', array() );
		 * }
		 *
		 * // Return filtered/sanitized options:
		 * public function options(): array {
		 *     $options = get_option( 'my_plugin_settings', array() );
		 *     unset( $options['api_key'] ); // Remove sensitive data
		 *     return $options;
		 * }
		 *
		 * // Return empty array if no relevant settings:
		 * public function options(): array {
		 *     return array();
		 * }
		 */
		abstract public function options(): array;

		// =====================================================================
		// Hook Registration Methods
		// =====================================================================

		/**
		 * Register WordPress action hooks.
		 *
		 * Sets up the necessary hooks for loading the feedback dialog on the plugins
		 * page and handling AJAX submissions. Uses 'current_screen' instead of
		 * 'admin_init' because `get_current_screen()` is not available early enough.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::loaded() Handles plugin page detection and script loading.
		 * @see self::ajax_setup() Registers the AJAX action handler.
		 */
		public function hooks(): void {
			// Hook into 'current_screen' to detect the plugins page.
			// Note: We use 'current_screen' instead of 'admin_init' because
			// get_current_screen() returns null when called too early in admin_init.
			add_action( 'current_screen', array( $this, 'loaded' ), 9 );

			// Register AJAX handler during admin initialization.
			add_action( 'admin_init', array( $this, 'ajax_setup' ) );
		}

		/**
		 * Register the AJAX action for submitting feedback.
		 *
		 * Creates a dynamic AJAX action hook based on the plugin slug to handle
		 * feedback submissions from the deactivation dialog.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::ajax_action() For the generated action name.
		 * @see self::send_feedback() For the AJAX handler implementation.
		 */
		public function ajax_setup(): void {
			// Register plugin-specific AJAX action for handling deactivation feedback.
			add_action( sprintf( 'wp_ajax_%s', $this->ajax_action() ), array( $this, 'send_feedback' ) );
		}

		/**
		 * Initialize deactivation feedback on the plugins page.
		 *
		 * Called on 'current_screen' hook to check if we're on the plugins page,
		 * boot services, and enqueue necessary scripts and styles.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::is_plugins_page() For page detection logic.
		 * @see self::enqueue_scripts() For script/style enqueuing.
		 */
		public function loaded(): void {

			if ( ! $this->is_plugins_page() ) {
				return;
			}

			$this->boot_services();

			// Enqueue scripts with priority 20 to ensure dependencies are loaded.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		}

		// =====================================================================
		// Public Getter Methods
		// =====================================================================

		/**
		 * Get the deactivation dialog title.
		 *
		 * Public accessor for the abstract `title()` method. Used by the service
		 * provider and templates to retrieve the dialog title.
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
		 * Get the API URL endpoint for feedback submission.
		 *
		 * Public accessor for the abstract `api_url()` method. Used by the
		 * `send_feedback()` method to determine where to POST the feedback data.
		 *
		 * @return string The full URL to the feedback API endpoint.
		 *
		 * @since 1.0.0
		 *
		 * @see self::api_url() The abstract method this wraps.
		 * @see self::send_feedback() Where this URL is used.
		 */
		public function get_api_url(): string {
			return $this->api_url();
		}

		// =====================================================================
		// Dialog Configuration Methods
		// =====================================================================

		/**
		 * Get the dialog action buttons configuration.
		 *
		 * Returns an array of button configurations for the feedback dialog footer.
		 * Override this method in subclasses to customize the buttons displayed.
		 *
		 * Each button configuration supports:
		 * - `type`: 'button' for `<button>` element or 'link' for `<a>` element.
		 * - `label`: The visible button text.
		 * - `attributes`: HTML attributes array (class, href, data-*, disabled, etc.).
		 * - `spinner`: (optional) Boolean to show a loading spinner inside the button.
		 *
		 * @return array<int, array{type: string, label: string, attributes: array<string, mixed>, spinner?: bool}> Button configurations.
		 *
		 * @throws WP_Exception Logs a notice that subclass should implement this method.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Default implementation with submit and skip buttons:
		 * public function get_buttons(): array {
		 *     return array(
		 *         array(
		 *             'type'       => 'button',
		 *             'label'      => __( 'Send feedback & Deactivate' ),
		 *             'attributes' => array(
		 *                 'disabled'        => true,
		 *                 'type'            => 'submit',
		 *                 'data-action'     => 'submit',
		 *                 'data-label'      => __( 'Send feedback & Deactivate' ),
		 *                 'data-processing' => __( 'Deactivate...' ),
		 *                 'class'           => array( 'button', 'button-primary' ),
		 *             ),
		 *             'spinner'    => true,
		 *         ),
		 *         array(
		 *             'type'       => 'link',
		 *             'label'      => __( 'Skip & Deactivate' ),
		 *             'attributes' => array(
		 *                 'href'  => '#',
		 *                 'class' => array( 'skip-deactivate' ),
		 *             ),
		 *         ),
		 *     );
		 * }
		 *
		 * // Custom buttons with different actions:
		 * public function get_buttons(): array {
		 *     return array(
		 *         array(
		 *             'type'       => 'button',
		 *             'label'      => __( 'Submit Feedback', 'my-plugin' ),
		 *             'attributes' => array(
		 *                 'type'        => 'submit',
		 *                 'data-action' => 'submit',
		 *                 'class'       => array( 'button', 'button-primary' ),
		 *             ),
		 *             'spinner'    => true,
		 *         ),
		 *         array(
		 *             'type'       => 'link',
		 *             'label'      => __( 'Cancel', 'my-plugin' ),
		 *             'attributes' => array(
		 *                 'href'        => '#',
		 *                 'data-action' => 'close',
		 *                 'class'       => array( 'button' ),
		 *             ),
		 *         ),
		 *     );
		 * }
		 */
		public function get_buttons(): array {

			$this->subclass_should_implement( __FUNCTION__ );

			return array(
				array(
					'type'       => 'button',
					'label'      => __( 'Send feedback & Deactivate' ),
					'attributes' => array(
						'disabled'        => true,
						'type'            => 'submit',
						'data-action'     => 'submit',
						'data-label'      => __( 'Send feedback & Deactivate' ),
						'data-processing' => __( 'Deactivate...' ),
						'class'           => array( 'button', 'button-primary' ),
					),
					'spinner'    => true,
				),
				array(
					'type'       => 'link',
					'label'      => __( 'Skip & Deactivate' ),
					'attributes' => array(
						'href'  => '#',
						'class' => array( 'skip-deactivate' ),
					),
				),
			);
		}

		/**
		 * Get the dialog subtitle text.
		 *
		 * Override this method to add a subtitle below the main dialog title.
		 * The subtitle typically provides additional context or instructions.
		 *
		 * @return string The subtitle text, or empty string for no subtitle.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Add a subtitle:
		 * public function sub_title(): string {
		 *     return 'Please help us improve by sharing your feedback.';
		 * }
		 *
		 * // No subtitle (default):
		 * public function sub_title(): string {
		 *     return '';
		 * }
		 */
		public function sub_title(): string {
			return '';
		}

		/**
		 * Get the deactivation reasons configuration.
		 *
		 * Returns an associative array of reasons users can select when deactivating.
		 * Each reason can optionally include a message, input field, or both.
		 * Override this method in subclasses to customize the available reasons.
		 *
		 * Reason configuration options:
		 * - `title`: (required) The reason text displayed as a radio button label.
		 * - `message`: (optional) Additional HTML content shown when this reason is selected.
		 * - `input`: (optional) Text input configuration with 'placeholder' and optional 'value'.
		 *
		 * @return array<string, array{title: string, message?: string, input?: array{placeholder: string, value?: string}}> Reasons configuration.
		 *
		 * @throws WP_Exception Logs a notice that subclass should implement this method.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Custom reasons with various configurations:
		 * public function get_reasons(): array {
		 *     $current_user = wp_get_current_user();
		 *
		 *     return array(
		 *         // Simple reason with no additional fields:
		 *         'temporary_deactivation' => array(
		 *             'title' => "It's a temporary deactivation.",
		 *         ),
		 *
		 *         // Reason with informational message:
		 *         'dont_understand' => array(
		 *             'title'   => 'I could not understand how to make it work.',
		 *             'message' => '<a href="https://docs.example.com">Check documentation</a>',
		 *         ),
		 *
		 *         // Reason with email input field:
		 *         'need_help' => array(
		 *             'title'   => 'I need someone to help me set this up.',
		 *             'input'   => array(
		 *                 'placeholder' => 'Your email address',
		 *                 'value'       => sanitize_email( $current_user->user_email ),
		 *             ),
		 *             'message' => 'We will contact you to help with setup.',
		 *         ),
		 *
		 *         // Open-ended "other" reason:
		 *         'other' => array(
		 *             'title' => 'Other',
		 *             'input' => array(
		 *                 'placeholder' => 'Please share the reason',
		 *             ),
		 *         ),
		 *     );
		 * }
		 */
		public function get_reasons(): array {

			$this->subclass_should_implement( __FUNCTION__ );

			$current_user = wp_get_current_user();

			return array(
				'temporary_deactivation' => array(
					'title' => "It's a temporary deactivation.",
				),

				'dont_understand'        => array(
					'title'   => 'I could not understand how to make it work.',
					'message' => 'Explain what it does.<br /><a target="_blank" href="#">Please check live demo</a>.',
				),

				'broke_site_layout'      => array(
					'title'   => 'The plugin <strong>broke my layout</strong> or some functionality.',
					'message' => '<a target="_blank" href="#">Please open a support ticket</a>, we will fix it immediately.',
				),

				'plugin_setup_help'      => array(
					'title'   => 'I need someone to <strong>setup this plugin.</strong>',
					'input'   => array(
						'placeholder' => 'Your email address.',
						'value'       => sanitize_email( $current_user->user_email ),
					),
					'message' => 'Please provide your email address to contact with you <br />and help you to set up and configure this plugin.',
				),

				'other'                  => array(
					'title' => 'Other',
					'input' => array(
						'placeholder' => 'Please share the reason',
					),
				),
			);
		}

		/**
		 * Get the plugin settings for the feedback payload.
		 *
		 * Public accessor for the abstract `options()` method. Used by `send_feedback()`
		 * to include plugin settings in the API request body.
		 *
		 * @return array<string, mixed> The plugin settings array.
		 *
		 * @since 1.0.0
		 *
		 * @see self::options() The abstract method this wraps.
		 * @see self::send_feedback() Where this data is used.
		 */
		public function get_options(): array {
			return $this->options();
		}

		// =====================================================================
		// AJAX Handler Methods
		// =====================================================================

		/**
		 * Handle AJAX feedback submission.
		 *
		 * Processes the feedback form submission, collects system information,
		 * and sends the data to the configured API endpoint. Includes comprehensive
		 * WordPress environment data to help diagnose potential issues.
		 *
		 * The payload includes:
		 * - Feedback: reason ID, title, user input text, plugin info, and settings.
		 * - WordPress: version, language, multisite status, environment type.
		 * - Theme: active theme details including parent theme if applicable.
		 * - Plugins: list of all active plugins with version info.
		 * - Database: extension type and server version.
		 * - Server: OS, web server, PHP version and limits.
		 *
		 * @return void Outputs JSON response and terminates.
		 *
		 * @throws WP_Exception If `get_reasons()` is not implemented in subclass.
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_api_url() For the target endpoint.
		 * @see self::get_options() For plugin settings included in payload.
		 * @see self::get_reasons() For available reason configurations.
		 */
		public function send_feedback(): void {

			check_ajax_referer( $this->get_plugin_slug() );

			$reasons = $this->get_reasons();

			$feedback_data = map_deep( $_POST['data'], 'sanitize_text_field' );

			$reason_id = sanitize_title( $feedback_data['reason_type'] );

			// Skip API call for temporary deactivation - no feedback needed.
			if ( 'temporary_deactivation' === $reason_id ) {
				wp_send_json_success();
			}

			$reason_title   = wp_kses_post( $reasons[ $reason_id ]['title'] );
			$reason_text    = ( isset( $feedback_data['reason_value'] ) ? sanitize_text_field( $feedback_data['reason_value'] ) : '' );
			$plugin_name    = $this->get_plugin_basename();
			$plugin_version = sanitize_text_field( $this->get_plugin_version() );

			// Load WP_Debug_Data for system information collection.
			if ( ! class_exists( 'WP_Debug_Data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
			}

			$wp = \WP_Debug_Data::debug_data();

			// Collect active plugins information.
			$active_plugins = array();

			$get_active_plugins         = (array) get_option( 'active_plugins', array() );
			$get_network_active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

			$get_available_active_plugins = array_merge( $get_network_active_plugins, $get_active_plugins );

			foreach ( $get_available_active_plugins as $plugin ) {
				$plugin_data               = get_plugin_data( $this->get_plugin_file( $plugin ) );
				$active_plugins[ $plugin ] = array(
					'Name'      => esc_html( $plugin_data['Name'] ),
					'PluginURI' => esc_url( $plugin_data['PluginURI'] ),
					'Version'   => esc_html( $plugin_data['Version'] ),
					'Author'    => strip_tags( $plugin_data['Author'] ), // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
					'Slug'      => $plugin,
				);
			}

			// Build the complete feedback request body.
			$request_body = array(
				'feedback'  => array(
					'reason_id'       => $reason_id,
					'reason_title'    => $reason_title,
					'reason_text'     => $reason_text,

					'plugin_slug'     => $plugin_name,
					'plugin_version'  => $plugin_version,
					'plugin_settings' => $this->get_options(),
				),
				'wordpress' => array(
					'version'          => $wp['wp-core']['fields']['version']['value'],
					'site_language'    => $wp['wp-core']['fields']['site_language']['value'],
					'user_language'    => $wp['wp-core']['fields']['user_language']['value'],
					'site_url'         => $wp['wp-core']['fields']['site_url']['value'],
					'is_multisite'     => $wp['wp-core']['fields']['multisite']['value'],
					'environment_type' => $wp['wp-core']['fields']['environment_type']['value'],
				),
				'theme'     => array(
					'name'           => $wp['wp-active-theme']['fields']['name']['value'],
					'version'        => $wp['wp-active-theme']['fields']['version']['value'],
					'author'         => $wp['wp-active-theme']['fields']['author']['value'],
					'author_website' => $wp['wp-active-theme']['fields']['author_website']['value'],
					'parent_theme'   => $wp['wp-active-theme']['fields']['parent_theme']['value'],
					'theme_slug'     => basename( $wp['wp-active-theme']['fields']['theme_path']['value'] ),
				),
				'plugins'   => $active_plugins,
				'database'  => array(
					'extension' => $wp['wp-database']['fields']['extension']['value'],
					'version'   => $wp['wp-database']['fields']['server_version']['value'],
				),
				'server'    => array(
					'os'               => $wp['wp-server']['fields']['server_architecture']['value'],
					'server'           => $wp['wp-server']['fields']['httpd_software']['value'],
					'php'              => $wp['wp-server']['fields']['php_version']['value'],
					'php_time_limit'   => $wp['wp-server']['fields']['time_limit']['value'],
					'php_memory_limit' => $wp['wp-server']['fields']['memory_limit']['value'],
				),
			);

			// Send feedback to the configured API endpoint.
			$response = wp_remote_post(
				esc_url_raw( $this->get_api_url() ),
				array(
					'sslverify' => false,
					'body'      => $request_body,
				)
			);

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		/**
		 * Generate the AJAX action name for this plugin.
		 *
		 * Creates a unique AJAX action identifier based on the plugin slug.
		 * The action name follows the pattern: `storepress_plugin_deactivate_{plugin_slug}`.
		 *
		 * @return string The AJAX action name with hyphens converted to underscores.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // For a plugin with slug 'my-awesome-plugin':
		 * $action = $this->ajax_action();
		 * // Returns: 'storepress_plugin_deactivate_my_awesome_plugin'
		 *
		 * @see self::ajax_setup() Where this action is registered.
		 * @see self::enqueue_scripts() Where this action is passed to JavaScript.
		 */
		public function ajax_action(): string {
			return sprintf( 'storepress_plugin_deactivate_%s', str_replace( '-', '_', $this->get_plugin_slug() ) );
		}

		// =====================================================================
		// Script and Style Methods
		// =====================================================================

		/**
		 * Enqueue scripts and styles for the deactivation feedback dialog.
		 *
		 * Registers and enqueues the deactivation package scripts, WordPress utilities,
		 * dashicons, and initializes the JavaScript handler with necessary configuration.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_dialog_id() For the dialog element ID.
		 * @see self::ajax_action() For the AJAX action name.
		 */
		public function enqueue_scripts(): void {

			$this->register_package_scripts( 'deactivation' );

			wp_enqueue_script( 'wp-util' );
			wp_enqueue_style( 'dashicons' );
			$handle = $this->enqueue_package_scripts(
				'deactivation'
			);

			// Configuration passed to the JavaScript handler.
			$options = array(
				'slug'        => $this->get_plugin_slug(),
				'name'        => $this->get_plugin_basename(),
				'dialog'      => sprintf( '#%s', $this->get_dialog_id() ),
				'_ajax_nonce' => wp_create_nonce( $this->get_plugin_slug() ),
				'action'      => $this->ajax_action(),
			);

			// Initialize the deactivation feedback JavaScript handler.
			wp_add_inline_script( $handle, sprintf( 'try{ StorePress.AdminPluginDeactivationFeedback(%s) }catch(e){}', wp_json_encode( $options ) ) );
		}

		// =====================================================================
		// Dialog Helper Methods
		// =====================================================================

		/**
		 * Get the unique dialog element ID.
		 *
		 * Generates a unique HTML ID for the dialog element based on the plugin slug.
		 * This ID is used to target the dialog in CSS and JavaScript.
		 *
		 * @return string The dialog element ID in format: `deactivation_{plugin_slug}_dialog`.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // For a plugin with slug 'my-plugin':
		 * $dialog_id = $this->get_dialog_id();
		 * // Returns: 'deactivation_my-plugin_dialog'
		 *
		 * @see self::enqueue_scripts() Where this ID is used.
		 */
		public function get_dialog_id(): string {
			return sprintf( 'deactivation_%s_dialog', $this->get_plugin_slug() );
		}

		/**
		 * Check if the current screen is the plugins list page.
		 *
		 * Determines whether the user is on either the single-site plugins page
		 * or the network plugins page, and has the capability to update plugins.
		 *
		 * @return bool True if on plugins page with update capability, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @see self::loaded() Where this check is performed.
		 */
		public function is_plugins_page(): bool {
			$screen    = get_current_screen();
			$screen_id = $screen->id ?? '';

			return in_array( $screen_id, array( 'plugins', 'plugins-network' ), true ) && current_user_can( 'update_plugins' );
		}

		/**
		 * Get the dialog width CSS value.
		 *
		 * Override this method to specify a custom width for the dialog.
		 * Return an empty string to use the default width.
		 *
		 * @return string CSS width value (e.g., '500px', '50%') or empty string for default.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Set a fixed width:
		 * public function get_dialog_width(): string {
		 *     return '600px';
		 * }
		 *
		 * // Use percentage width:
		 * public function get_dialog_width(): string {
		 *     return '50%';
		 * }
		 *
		 * // Use default width:
		 * public function get_dialog_width(): string {
		 *     return '';
		 * }
		 */
		public function get_dialog_width(): string {
			return '';
		}
	}
}
