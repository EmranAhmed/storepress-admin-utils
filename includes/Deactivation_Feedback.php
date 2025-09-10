<?php
	/**
	 * Dialog UI.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Deactivation_Feedback' ) ) {
	/**
	 * Admin Settings Field Class.
	 *
	 * @name Deactivation_Feedback
	 */
	abstract class Deactivation_Feedback {

		use Common;
		use Package;

		/**
		 * Get deactivation reasons.
		 *
		 * @return array<string, mixed>
		 * @example
		 *
		 * array(
		 *     'temporary_deactivation' => array(
		 *         'title' => "It's a temporary deactivation.",
		 *     ),
		 *
		 *     'dont_understand' => array(
		 *         'title' => "I could not understand how to make it work.",
		 *         'message' => 'Explain what it does.<br /><a target="_blank" href="#">Please check live demo</a>.',
		 *     ),
		 *
		 *     'broke_site_layout' => array(
		 *         'title' => 'The plugin <strong>broke my layout</strong> or some functionality.',
		 *         'message' => '<a target="_blank" href="#">Please open a support ticket</a>, we will fix it immediately.',
		 *     ),
		 *
		 *     'plugin_setup_help' => array(
		 *         'title' => 'I need someone to <strong>setup this plugin.</strong>',
		 *         'input' => array(
		 *             'placeholder'=>'Your email address.',
		 *             'value'=>sanitize_email( $current_user->user_email )
		 *         ),
		 *         'message' => 'Please provide your email address to contact with you <br />and help you to set up and configure this plugin.',
		 *     ),
		 *
		 *     'other' => array(
		 *         'title' => 'Other',
		 *         'input' => array(
		 *             'placeholder'=> 'Please share the reason',
		 *         ),
		 *     )
		 * )
		 */
		abstract public function reasons(): array;

		/**
		 * Get deactivation title.
		 *
		 * @return string
		 */
		abstract public function title(): string;

		/**
		 * Get API URL to send feedback.
		 *
		 * @return string
		 * @example https://example.com/wp-json/__NAMESPACE__/v1/deactivate
		 */
		abstract public function api_url(): string;

		/**
		 * Get saved settings data.
		 *
		 * @return array<string, mixed>
		 */
		abstract public function options(): array;

		/**
		 * Init Hook.
		 *
		 * @return void
		 */
		protected function __construct() {
			// We use current_screen instead of admin_init.
			// because using calling get_current_screen() too early in admin_init.
			add_action( 'current_screen', array( $this, 'init' ), 9 );
			add_action( 'admin_init', array( $this, 'ajax_setup' ) );
		}

		/**
		 * Setup ajax action.
		 *
		 * @return void
		 */
		public function ajax_setup() {
			add_action( sprintf( 'wp_ajax_%s', $this->ajax_action() ), array( $this, 'send_feedback' ) );
		}

		/**
		 * Init.
		 *
		 * @return void
		 */
		public function init() {

			if ( ! $this->is_plugins_page() ) {
				return;
			}
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
			$this->get_dialog();
		}

		/**
		 * Get deactivation reasons.
		 *
		 * @return string
		 */
		public function get_title(): string {
			return $this->title();
		}
		/**
		 * Get API URL to send feedback.
		 *
		 * @return string
		 */
		public function get_api_url(): string {
			return $this->api_url();
		}

		/**
		 * Dialog buttons.
		 *
		 * @return array<int, mixed>
		 * @example
		 *
		 * array(
		 *     array(
		 *         'type'       => 'button',
		 *         'label'      => __( 'Send feedback & Deactivate' ),
		 *         'attributes' => array(
		 *             'disabled'        => true,
		 *             'type'            => 'submit',
		 *             'data-action'     => 'submit',
		 *             'data-label'      => __( 'Send feedback & Deactivate' ),
		 *             'data-processing' => __( 'Deactivate...' ),
		 *             'class'           => array( 'button', 'button-primary' ),
		 *          ),
		 *         'spinner'    => true,
		 *     ),
		 *
		 *     array(
		 *         'type'       => 'link',
		 *         'label'      => __( 'Skip & Deactivate' ),
		 *         'attributes' => array(
		 *             'href'  => '#',
		 *             'class' => array( 'skip-deactivate' ),
		 *         ),
		 *     ),
		 * )
		 */
		public function get_buttons(): array {

			/* translators: %s: Method name. */
			$message = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __METHOD__ );
			wp_trigger_error( __METHOD__, $message );

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
		 * Get deactivation reasons.
		 *
		 * @return string
		 */
		public function sub_title(): string {
			return '';
		}

		/**
		 * Get deactivation reasons.
		 *
		 * @return array<string, mixed>
		 */
		public function get_reasons(): array {
			return $this->reasons();
		}

		/**
		 * Get saved settings data.
		 *
		 * @return array<string, mixed>
		 */
		public function get_options(): array {
			return $this->options();
		}

		/**
		 * Send feedback to API Server.
		 *
		 * @return void
		 */
		public function send_feedback() {

			check_ajax_referer( $this->get_plugin_slug() );

			$reasons = $this->get_reasons();

			$feedback_data = map_deep( $_POST['data'], 'sanitize_text_field' );

			$reason_id = sanitize_title( $feedback_data['reason_type'] );

			if ( 'temporary_deactivation' === $reason_id ) {
				wp_send_json_success();
			}

			$reason_title   = wp_kses_post( $reasons[ $reason_id ]['title'] );
			$reason_text    = ( isset( $feedback_data['reason_value'] ) ? sanitize_text_field( $feedback_data['reason_value'] ) : '' );
			$plugin_name    = $this->get_plugin_basename();
			$plugin_version = sanitize_text_field( $this->get_plugin_version() );

			if ( ! class_exists( 'WP_Debug_Data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
			}

			$wp = \WP_Debug_Data::debug_data();

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
		 * Ajax Action.
		 *
		 * @return string
		 */
		public function ajax_action(): string {
			return sprintf( 'storepress_plugin_deactivate_%s', str_replace( '-', '_', $this->get_plugin_slug() ) );
		}

		/**
		 * Enqueue script.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {

			$this->register_package_admin_utils_script();
			$this->register_package_scripts( 'deactivation' );

			wp_enqueue_script( 'wp-util' );
			wp_enqueue_style( 'dashicons' );
			$handle = $this->enqueue_package_scripts(
				'deactivation'
			);

			$options = array(
				'slug'        => $this->get_plugin_slug(),
				'name'        => $this->get_plugin_basename(),
				'dialog'      => sprintf( '#%s', $this->get_dialog_id() ),
				'_ajax_nonce' => wp_create_nonce( $this->get_plugin_slug() ),
				'action'      => $this->ajax_action(),
			);

			wp_add_inline_script( $handle, sprintf( 'try{ StorePress.AdminPluginDeactivationFeedback(%s) }catch(e){}', wp_json_encode( $options ) ) );
		}

		/**
		 * Get Dialog Box ID.
		 *
		 * @return string
		 */
		public function get_dialog_id(): string {
			return sprintf( 'deactivation_%s_dialog', $this->get_plugin_slug() );
		}

		/**
		 * Is Admin Plugin list Page.
		 *
		 * @return bool
		 */
		public function is_plugins_page(): bool {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			return in_array( $screen_id, array( 'plugins', 'plugins-network' ), true ) && current_user_can( 'update_plugins' );
		}

		/**
		 * Load Dialog.
		 *
		 * @return Deactivation_Dialog
		 */
		public function get_dialog(): Deactivation_Dialog {
			// Load Deactivation_Dialog In NON Singleton It Does load In Page in many times.
			return new Deactivation_Dialog( $this );
		}

		/**
		 * Dialog width;
		 *
		 * @return string
		 */
		public function get_dialog_width(): string {
			return '';
		}
	}
}
