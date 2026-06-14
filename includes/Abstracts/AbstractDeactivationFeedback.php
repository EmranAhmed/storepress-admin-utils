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
	 *
	 * @phpstan-use HelperMethodsTrait<AbstractDeactivationFeedback>
	 * @phpstan-use PluginCommonTrait<AbstractDeactivationFeedback>
	 * @phpstan-use MethodShouldImplementTrait<AbstractDeactivationFeedback>
	 * @phpstan-use InternalPackageTrait<AbstractDeactivationFeedback>
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
		public function init(): void {}

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
		 * @return array<string, mixed>
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
		 * @see self::loaded()
		 * @see self::ajax_setup()
		 */
		final public function hooks(): void {
			// Uses 'current_screen' instead of 'admin_init' because
			// get_current_screen() returns null too early in admin_init.
			add_action( 'current_screen', array( $this, 'loaded' ), 9 );

			// Register AJAX handler for feedback submission.
			add_action( 'admin_init', array( $this, 'ajax_setup' ) );
		}

		/**
		 * Register the plugin-specific AJAX action for feedback submission.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::ajax_action()
		 * @see self::send_feedback()
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
		 * @see self::is_plugins_page()
		 * @see self::enqueue_scripts()
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
		 * @see self::title()
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
		 * @see self::api_url()
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
		 * @return array<int, array{type: string, label: string, attributes: array<string, mixed>, spinner?: bool}>
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
		 * @return array<string, array{title: string, message?: string, input?: array{placeholder: string, value?: string}}>
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
		 * @return array<string, mixed>
		 *
		 * @since 1.0.0
		 *
		 * @see self::options()
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

			$get_available_active_plugins = array_merge( $get_network_active_plugins, $get_active_plugins );

			foreach ( $get_available_active_plugins as $plugin ) {

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
					'version'          => $wp['wp-core']['fields']['version']['value'],
					'site_language'    => $wp['wp-core']['fields']['site_language']['value'],
					'user_language'    => $wp['wp-core']['fields']['user_language']['value'],
					'site_url'         => $wp['wp-core']['fields']['site_url']['value'],
					'is_multisite'     => $this->string_to_boolean( $wp['wp-core']['fields']['multisite']['value'] ),
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
					'body' => $request_body,
				)
			);

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		/**
		 * Generate the AJAX action name: `storepress_plugin_deactivate_{slug}`.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see self::ajax_setup()
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
		 * @see self::get_dialog_id()
		 * @see self::ajax_action()
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
				'slug'   => $this->get_plugin_slug(),
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
