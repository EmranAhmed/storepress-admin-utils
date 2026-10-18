<?php
	/**
	 * Abstract Deactivation Feedback Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    3.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Factory\DeactivationFeedbackFactory;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;
	use StorePress\AdminUtils\Traits\PluginCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractDeactivationFeedback' ) ) {

	/**
	 * Base class for collecting user feedback on plugin deactivation.
	 *
	 * @name AbstractDeactivationFeedback
	 */
	abstract class AbstractDeactivationFeedback {

		use HelperMethodsTrait;
		use PluginCommonTrait;
		use MethodShouldImplementTrait;
		use InternalPackageTrait;

		/**
		 * Factory instance for creating dialog components.
		 *
		 * @var DeactivationFeedbackFactory
		 *
		 * @since 3.1.0
		 */
		protected DeactivationFeedbackFactory $factory;

		// =====================================================================
		// Constructor and Initialization Methods
		// =====================================================================

		/**
		 * Constructor.
		 *
		 * @param DeactivationFeedbackFactory|null $factory Factory class name. Default DeactivationFeedbackFactory.
		 *
		 * @since 1.0.0
		 */
		public function __construct( ?DeactivationFeedbackFactory $factory = null ) {

			$this->factory = $factory ?? DeactivationFeedbackFactory::instance();
			$this->hooks();
			$this->init();
		}

		/**
		 * Get the factory instance.
		 *
		 * @return DeactivationFeedbackFactory
		 *
		 * @since 3.1.0
		 */
		public function get_factory(): DeactivationFeedbackFactory {
			return $this->factory;
		}

		/**
		 * Called after constructor. Override for additional initialization.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function init(): void {
		}

		/**
		 * Get the deactivation dialog title.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		abstract public function title(): string;

		/**
		 * Get the API URL endpoint to send feedback data.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		abstract public function api_url(): string;

		/**
		 * Get the plugin settings to include in the feedback payload.
		 *
		 * @return array<array-key, mixed>
		 *
		 * @since 1.0.0
		 */
		abstract public function options(): array;

		// =====================================================================
		// Hook Registration Methods
		// =====================================================================

		/**
		 * Register WordPress action hooks.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see   self::loaded()
		 * @see   self::ajax_setup()
		 */
		final public function hooks(): void {
			// Uses 'current_screen' instead of 'admin_init' because
			// get_current_screen() returns null too early in admin_init.
			add_action( 'current_screen', array( $this, 'loaded' ), 9 );

			// Register AJAX handler for feedback submission.
			add_action( 'admin_init', array( $this, 'ajax_setup' ) );
		}

		/**
		 * Plugin Slug for WordPress Plugin List Page.
		 *
		 * @return string
		 * @see WP_Plugins_List_Table::single_row()
		 */
		public function get_plugin_slug_for_plugin_list(): string {
			$plugin_data = $this->get_plugin_data();

			$slug = $plugin_data['slug'] ?? null;

			if ( is_string( $slug ) ) {
				return $slug;
			}

			$name = $plugin_data['Name'] ?? '';

			return is_string( $name ) ? sanitize_title( $name ) : '';
		}

		/**
		 * Register the plugin-specific AJAX action for feedback submission.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see   self::ajax_action()
		 * @see   self::send_feedback()
		 */
		final public function ajax_setup(): void {
			// Register plugin-specific AJAX action for handling deactivation feedback.
			add_action( sprintf( 'wp_ajax_%s', $this->ajax_action() ), array( $this, 'send_feedback' ) );
		}

		/**
		 * Called on 'current_screen' to initialize dialog and enqueue scripts.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see   self::is_plugins_page()
		 * @see   self::enqueue_scripts()
		 */
		final public function loaded(): void {

			if ( ! $this->is_plugins_page() ) {
				return;
			}

			if ( ! $this->has_permission() ) {
				return;
			}

			$this->get_factory()->create_dialog( $this );

			// Enqueue scripts with priority 20 to ensure dependencies are loaded.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		}

		/**
		 * User have permission for deactivation.
		 *
		 * @return bool
		 */
		public function has_permission(): bool {
			return current_user_can( 'update_plugins' );
		}

		// =====================================================================
		// Public Getter Methods
		// =====================================================================

		/**
		 * Public accessor for the dialog title.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see   self::title()
		 */
		public function get_title(): string {
			return $this->title();
		}

		/**
		 * Public accessor for the API URL endpoint.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see   self::api_url()
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
		 * @return array<array-key, array{type: string, label: string, attributes: array<array-key, mixed>, spinner?: bool}>
		 *
		 * @since 1.0.0
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
		 * Get the dialog subtitle text. Override to add context below the title.
		 *
		 * @return string Empty string for no subtitle.
		 *
		 * @since 1.0.0
		 */
		public function sub_title(): string {
			return '';
		}

		/**
		 * Get the deactivation reasons configuration.
		 *
		 * @return array<array-key, array{title: string, message?: string, input?: array{placeholder?: string, value?: string}}>
		 *
		 * @since 1.0.0
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
		 * Public accessor for plugin settings included in the feedback payload.
		 *
		 * @return array<array-key, mixed>
		 *
		 * @since 1.0.0
		 *
		 * @see   self::options()
		 */
		public function get_options(): array {
			return $this->options();
		}

		/**
		 * Get Plugin license key for PRO Plugin.
		 *
		 * @return string
		 */
		public function license_key(): string {
			return '';
		}

		/**
		 * Get Is Plugin is Free or Pro version.
		 *
		 * @return bool
		 */
		public function is_pro(): bool {
			return false;
		}

		// =====================================================================
		// AJAX Handler Methods
		// =====================================================================

		/**
		 * Handle AJAX feedback submission and send data to API endpoint.
		 *
		 * @return void Outputs JSON response and terminates.
		 *
		 * @throws \WP_Exception If plugin file or reason not defined.
		 * @see   self::get_api_url()
		 * @see   self::get_options()
		 * @see   self::get_reasons()
		 * @since 1.0.0
		 */
		public function send_feedback(): void {

			check_ajax_referer( $this->get_plugin_slug() );

			if ( ! $this->has_permission() ) {
				wp_send_json_error( array( 'message' => __( 'Sorry, you are not allowed to access this page.' ) ), 403 );
			}

			$reasons = $this->get_reasons();

			$feedback_data = map_deep( wp_unslash( $_POST['data'] ?? array() ), 'sanitize_text_field' );

			/**
			 * Feedback data shape.
			 *
			 * @var array{reason_type: string, reason_value: string} $feedback_data
			 */
			$reason_id = sanitize_title( $feedback_data['reason_type'] ?? '' );

			// Check available reason.
			if ( ! isset( $reasons[ $reason_id ] ) ) {
				wp_send_json_error( array( 'message' => 'Invalid reason.' ), 400 );
			}

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

			// Collect active plugins' information.
			$active_plugins = array();

			$get_active_plugins         = (array) get_option( 'active_plugins', array() );
			$get_network_active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

			// 'active_plugins' is a list of basenames, 'active_sitewide_plugins' is keyed by basename with activation timestamps as values.
			$get_available_active_plugins = array_merge( array_keys( $get_network_active_plugins ), array_values( $get_active_plugins ) );

			foreach ( array_filter( $get_available_active_plugins, 'is_string' ) as $plugin ) {

				// Skip Current Deactivating plugin info from list.
				if ( $plugin_name === $plugin ) {
					continue;
				}

				$plugin_data = get_plugin_data( $this->get_plugin_file( $plugin ) );

				$active_plugins[ $plugin ] = array(
					'name'           => esc_html( $plugin_data['Name'] ),
					'plugin_uri'     => esc_url( $plugin_data['PluginURI'] ),
					'version'        => esc_html( $plugin_data['Version'] ),
					'author'         => wp_strip_all_tags( $plugin_data['Author'] ),
					'author_website' => esc_url( $plugin_data['AuthorURI'] ),
					'slug'           => $plugin,
				);
			}

			// Build the complete feedback request body.
			$request_body = array(
				'feedback'  => array(
					'reason_slug'     => $reason_id,
					'reason_title'    => $reason_title,
					'reason_text'     => $reason_text,
					'plugin_slug'     => $plugin_name,
					'plugin_version'  => $plugin_version,
					'plugin_settings' => $this->get_options(),
					'is_pro'          => $this->is_pro(),
					'license_key'     => $this->license_key(),
				),
				'wordpress' => array(
					'version'          => $this->get_debug_data_value( $wp, 'wp-core', 'version' ),
					'site_language'    => $this->get_debug_data_value( $wp, 'wp-core', 'site_language' ),
					'user_language'    => $this->get_debug_data_value( $wp, 'wp-core', 'user_language' ),
					'site_url'         => $this->get_debug_data_value( $wp, 'wp-core', 'site_url' ),
					'is_multisite'     => $this->string_to_boolean( $this->get_debug_data_value( $wp, 'wp-core', 'multisite' ) ),
					'environment_type' => $this->get_debug_data_value( $wp, 'wp-core', 'environment_type' ),
				),
				'theme'     => array(
					'name'           => $this->get_debug_data_value( $wp, 'wp-active-theme', 'name' ),
					'version'        => $this->get_debug_data_value( $wp, 'wp-active-theme', 'version' ),
					'author'         => $this->get_debug_data_value( $wp, 'wp-active-theme', 'author' ),
					'author_website' => $this->get_debug_data_value( $wp, 'wp-active-theme', 'author_website' ),
					'parent_theme'   => $this->get_debug_data_value( $wp, 'wp-active-theme', 'parent_theme' ),
					'theme_slug'     => basename( $this->get_debug_data_value( $wp, 'wp-active-theme', 'theme_path' ) ),
				),
				'plugins'   => $active_plugins,
				'database'  => array(
					'extension' => $this->get_debug_data_value( $wp, 'wp-database', 'extension' ),
					'version'   => $this->get_debug_data_value( $wp, 'wp-database', 'server_version' ),
				),
				'server'    => array(
					'os'               => $this->get_debug_data_value( $wp, 'wp-server', 'server_architecture' ),
					'server'           => $this->get_debug_data_value( $wp, 'wp-server', 'httpd_software' ),
					'php'              => $this->get_debug_data_value( $wp, 'wp-server', 'php_version' ),
					'php_time_limit'   => $this->get_debug_data_value( $wp, 'wp-server', 'time_limit' ),
					'php_memory_limit' => $this->get_debug_data_value( $wp, 'wp-server', 'memory_limit' ),
				),
			);

			// Send feedback to the configured API endpoint.
			$response = wp_remote_post(
				esc_url_raw( $this->get_api_url() ),
				array(
					'blocking' => false, // Fire and forget; don't wait for a response.
					'body'     => $request_body,
				)
			);

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		/**
		 * Read a single field value from a site health debug data payload.
		 *
		 * Core types the payload as a plain array, so the section, its fields and the
		 * field value are all checked before use. Values are display ready strings,
		 * but a few are reported as numbers.
		 *
		 * @param array<array-key, mixed> $debug_data Payload from WP_Debug_Data::debug_data().
		 * @param string                  $section    Section key, e.g. 'wp-core'.
		 * @param string                  $field      Field key inside the section, e.g. 'version'.
		 *
		 * @return string Field value, or empty string when the field is missing or not a scalar.
		 *
		 * @since 3.9.0
		 *
		 * @see   self::send_feedback()
		 * @see   \WP_Debug_Data::debug_data()
		 */
		private function get_debug_data_value( array $debug_data, string $section, string $field ): string {

			$section_data = $debug_data[ $section ] ?? null;

			if ( ! is_array( $section_data ) ) {
				return '';
			}

			$fields = $section_data['fields'] ?? null;

			if ( ! is_array( $fields ) ) {
				return '';
			}

			$field_data = $fields[ $field ] ?? null;

			if ( ! is_array( $field_data ) ) {
				return '';
			}

			$value = $field_data['value'] ?? '';

			return is_scalar( $value ) ? (string) $value : '';
		}

		/**
		 * Generate the AJAX action name: `storepress_plugin_deactivate_{slug}`.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see   self::ajax_setup()
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
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see   self::get_dialog_id()
		 * @see   self::ajax_action()
		 */
		public function enqueue_scripts(): void {

			$this->register_package_scripts( 'deactivation' );

			wp_enqueue_script( 'wp-util' );
			wp_enqueue_style( 'dashicons' );
			$handle = $this->enqueue_package_scripts( 'deactivation' );

			// Configuration passed to the JavaScript handler.
			$options = array(
				// WP Plugin List table generate slug from plugin name.
				// So plugin slug as directory will not match and popup not open.
				'slug'   => $this->get_plugin_slug_for_plugin_list(),
				'name'   => $this->get_plugin_basename(),
				'dialog' => sprintf( '#%s', $this->get_dialog_id() ),
				'nonce'  => wp_create_nonce( $this->get_plugin_slug() ),
				'action' => $this->ajax_action(),
			);

			// Initialize the deactivation feedback JavaScript handler.
			wp_add_inline_script( $handle, sprintf( 'try{ StorePress.AdminPluginDeactivationFeedback(%s) }catch(e){}', wp_json_encode( $options ) ) );
		}

		// =====================================================================
		// Dialog Helper Methods
		// =====================================================================

		/**
		 * Get the unique dialog element ID: `deactivation_{slug}_dialog`.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_dialog_id(): string {
			return sprintf( 'deactivation_%s_dialog', $this->get_plugin_slug() );
		}

		/**
		 * Check if the current screen is the plugins list page.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function is_plugins_page(): bool {
			$screen    = get_current_screen();
			$screen_id = $screen->id ?? '';

			return in_array( $screen_id, array( 'plugins', 'plugins-network' ), true ) && current_user_can( 'update_plugins' );
		}

		/**
		 * Get the dialog width CSS value. Empty string for default.
		 *
		 * @return string CSS width value (e.g., '500px', '50%') or empty string.
		 *
		 * @since 1.0.0
		 */
		public function get_dialog_width(): string {
			return '';
		}
	}
}
