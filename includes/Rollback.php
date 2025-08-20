<?php
	/**
	 * Plugin Rollback API Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use WP_Ajax_Upgrader_Skin;

if ( ! class_exists( '\StorePress\AdminUtils\Rollback' ) ) {

	/**
	 * Plugin Updater API Class.
	 *
	 * @name Rollback
	 */
	class Rollback {

		use Common;
		use Package;

		/**
		 * Plugin Absolute File Path.
		 *
		 * @var string
		 */
		private string $plugin_file;
		/**
		 * Rollback Page title.
		 *
		 * @var string
		 */
		private string $page_title;
		/**
		 * Rollback link text.
		 *
		 * @var string
		 */
		private string $link_text;

		/**
		 * Rollback Plugin Info.
		 *
		 * @var array<string, mixed>
		 */
		private array $plugin_info;

		/**
		 * Rollback Plugin Data.
		 *
		 * @var array<string, mixed>
		 */
		private array $plugin_data;

		/**
		 * Localize strings.
		 *
		 * @var array<string, string>
		 */
		private array $localize_strings;

		/**
		 * Plugin Rollback.
		 *
		 * @param string                $plugin_file      Plugin file.
		 * @param array<string, string> $localize_strings Admin page title.
		 */
		public function __construct( string $plugin_file, array $localize_strings ) {
			$this->plugin_file      = $plugin_file;
			$this->page_title       = $localize_strings['rollback_page_title'];
			$this->link_text        = $localize_strings['rollback_link_text'];
			$this->localize_strings = $localize_strings;

			$this->init();
		}

		/**
		 * Get plugin file absolute or relative file.
		 *
		 * @return string
		 */
		public function plugin_file(): string {
			return $this->plugin_file;
		}

		/**
		 * Get Plugin Info after page load.
		 *
		 * @return array<string, mixed>
		 */
		public function get_plugin_info(): array {
			return $this->plugin_info;
		}

		/**
		 * Get plugin file data.
		 *
		 * @return array<string, mixed>
		 */
		public function get_plugin_data(): array {
			return $this->plugin_data;
		}

		/**
		 * Get localize strings.
		 *
		 * @return string[]
		 */
		public function get_localize_strings(): array {
			return $this->localize_strings;
		}

		/**
		 * Rollback Main Menu Slug.
		 *
		 * @return string
		 */
		public function main_menu_slug(): string {
			return 'storepress-plugin-rollback-main';
		}

		/**
		 * Rollback submenu slug.
		 *
		 * @return string
		 */
		public function menu_slug(): string {
			return sprintf( 'storepress-%s-rollback', $this->get_plugin_slug() );
		}

		/**
		 * Get nonce action.
		 *
		 * @return string
		 */
		public function get_nonce_action(): string {
			return $this->menu_slug();
		}

		/**
		 * Get nonce key.
		 *
		 * @return string
		 * @see check_admin_referer()
		 */
		public function get_wp_nonce_query_arg(): string {
			return '_wpnonce';
		}
		/**
		 * Get nonce key.
		 *
		 * @return string
		 * @see check_ajax_referer()
		 */
		public function get_ajax_nonce_query_arg(): string {
			return '_ajax_nonce';
		}

		/**
		 * Init Hook.
		 *
		 * @return void
		 */
		public function init() {

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			if ( ! function_exists( 'get_plugin_data' ) ) {
				return;
			}

			// Set admin menu first.
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

			// Set plugin action link.
			add_filter( 'plugin_action_links_' . $this->get_plugin_basename(), array( $this, 'action_links' ), 12 );

			add_action( 'wp_ajax_' . $this->get_ajax_action(), array( $this, 'handle_rollback' ) );
		}

		/**
		 * Get Ajax action.
		 *
		 * @return string
		 */
		public function get_ajax_action(): string {
			return sprintf( 'storepress_%s_rollback_action', $this->get_plugin_slug() );
		}

		/**
		 * Handle ajax.
		 *
		 * @return void
		 */
		public function handle_rollback() {

			$status = array(
				'currentVersion' => '',
				'targetVersion'  => '',
				'pluginName'     => '',
				'plugin'         => '',
				'error'          => true,
				'errorCode'      => '',
			);

			check_ajax_referer( 'updates' );

			$version = isset( $_POST['targetVersion'] ) ? sanitize_text_field( $_POST['targetVersion'] ) : '';

			$strings = $this->get_localize_strings();

			// Check capability.
			if ( ! current_user_can( 'update_plugins' ) ) {
				$status['message'] = esc_html( $strings['rollback_no_access'] );
				wp_send_json_error( $status );
			}

			$validate_plugin = validate_plugin( $this->get_plugin_basename() );

			// Check validate.
			if ( is_wp_error( $validate_plugin ) ) {
				$status['message'] = esc_html( $validate_plugin->get_error_message() );
				wp_send_json_error( $status );
			}

			// Check target selected.
			if ( $this->is_empty_string( $version ) ) {
				$status['message'] = esc_html( $strings['rollback_no_target_version'] );
				wp_send_json_error( $status );
			}

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $this->get_plugin_slug() ) );
			$plugin_data = get_plugin_data( $this->get_plugin_file() );

			$status['pluginName']     = $plugin_data['Name'];
			$status['plugin']         = $this->get_plugin_basename();
			$status['slug']           = $this->get_plugin_slug();
			$status['currentVersion'] = $plugin_data['Version'];
			$status['targetVersion']  = $version;
			$status['versionID']      = str_replace( '.', '-', $version );

			if ( is_wp_error( $plugin_info ) ) {
				$status['message'] = $plugin_info->get_error_message();
				wp_send_json_error( $status );
			}

			$plugin_info = (array) $plugin_info;

			$package = trim( $plugin_info['versions'][ $version ] );

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Rollback( $skin );

			$result = $upgrader->rollback( $this->get_plugin_basename(), $package );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$status['debug'] = $skin->get_upgrade_messages();
			}

			if ( true === $result ) {
				$status['error']   = false;
				$status['message'] = esc_html( sprintf( $strings['rollback_success'], $plugin_data['Name'], $version ) );
				wp_send_json_success( $status );
			}

			if ( is_wp_error( $result ) ) {
				$status['message'] = esc_html( $result->get_error_message() );
				wp_send_json_error( $status );
			}

			if ( is_wp_error( $skin->result ) ) {
				$status['message'] = esc_html( $skin->result->get_error_message() );
				wp_send_json_error( $status );
			} elseif ( $skin->get_errors()->has_errors() ) {
				$status['message'] = esc_html( $skin->get_error_messages() );
				wp_send_json_error( $status );
			} elseif ( false === $result ) {
				global $wp_filesystem;

				$status['errorCode'] = 'unable_to_connect_to_filesystem';
				$status['message']   = __( 'Unable to connect to the filesystem. Please confirm your credentials.' );

				// Pass through the error from WP_Filesystem if one was raised.
				if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
					$status['errorCode'] = 'unable_to_connect_to_filesystem';
					$status['message']   = esc_html( $wp_filesystem->errors->get_error_message() );
				}

				wp_send_json_error( $status );
			}

			// An unhandled error occurred.
			$status['message'] = esc_html( $strings['rollback_failed'] );
			wp_send_json_error( $status );
		}

		/**
		 * Register Scripts.
		 *
		 * @return void
		 */
		public function register_scripts() {

			$this->register_package_admin_utils_script();

			$l10 = wp_parse_args(
				array(
					'plugin' => $this->get_plugin_basename(),
					'slug'   => $this->get_plugin_slug(),
					'action' => $this->get_ajax_action(),
				),
				$this->get_localize_strings()
			);

			$this->register_package_scripts( 'rollback', $l10 );
		}

		/**
		 * Enqueue scripts.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'updates' );
			$this->enqueue_package_scripts( 'rollback' );
		}


		/**
		 * Get page title.
		 *
		 * @return string
		 */
		public function get_page_title(): string {
			return $this->page_title;
		}

		/**
		 * Get link text.
		 *
		 * @return string
		 */
		public function get_link_text(): string {
			return $this->link_text;
		}

		/**
		 * Image for banner or icons.
		 *
		 * @param string[] $images Images.
		 *
		 * @return string
		 */
		public function get_image_url( array $images ): string {
			foreach ( $images as $image ) {
				if ( ! $this->is_empty_string( $image ) ) {
					return $image;
				}
			}

			return '';
		}

		/**
		 * Action links.
		 *
		 * @param string[] $links action links.
		 *
		 * @return string[]
		 */
		public function action_links( array $links ): array {

			// unset wp.org rollback plugin action.
			unset( $links['rollback'] );

			$page_url = menu_page_url( $this->menu_slug(), false );

			$action_links = sprintf( '<a href="%1$s" aria-label="%2$s">%2$s</a>', esc_url( $page_url ), esc_html( $this->get_link_text() ) );

			$data = $this->get_plugin_transient_data( $this->get_plugin_basename() );

			$allow_rollback = $this->string_to_boolean( $this->get_var( $data['allow_rollback'], 'no' ) );

			if ( $allow_rollback ) {
				$links[ $this->menu_slug() ] = $action_links;
			}

			return $links;
		}

		/**
		 * Plugin transient data.
		 *
		 * @param string $plugin Plugin basename.
		 *
		 * @return array<string, mixed>
		 */
		public function get_plugin_transient_data( string $plugin ): array {
			$current = get_site_transient( 'update_plugins' );

			if ( isset( $current->response[ $plugin ] ) ) {
				return (array) $current->response[ $plugin ];
			}

			if ( isset( $current->no_update[ $plugin ] ) ) {
				return (array) $current->no_update[ $plugin ];
			}

			return array();
		}

		/**
		 * Setup admin menu.
		 *
		 * @return void
		 */
		public function admin_menu() {

			add_menu_page( '', '', 'update_plugins', $this->main_menu_slug(), '__return_false' );

			$page_hook = add_submenu_page( $this->main_menu_slug(), $this->get_page_title(), '', 'update_plugins', $this->menu_slug(), array( $this, 'settings_template' ) );

			add_action( 'load-' . $page_hook, array( $this, 'settings_init' ) );

			remove_menu_page( $this->main_menu_slug() );
		}

		/**
		 * Show admin page.
		 *
		 * @return void|\WP_Error
		 */
		public function settings_template() {

			include_once __DIR__ . '/templates/rollback-template.php';

			wp_print_request_filesystem_credentials_modal();
			wp_print_admin_notice_templates();
		}

		/**
		 * Process page before admin page show.
		 *
		 * @return void
		 */
		public function settings_init() {

			$plugin_file = $this->get_plugin_file();

			$validate_plugin = validate_plugin( $this->get_plugin_basename() );

			$strings = $this->get_localize_strings();

			if ( is_wp_error( $validate_plugin ) ) {
				wp_die(
					esc_html( $validate_plugin->get_error_message() ),
					esc_html( $strings['rollback_page_title'] ),
					array(
						'back_link' => true,
					)
				);
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$slug = $this->get_plugin_slug();

			$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

			if ( is_wp_error( $plugin_info ) ) {
				wp_die(
					esc_html( $plugin_info->get_error_message() ),
					esc_html( $strings['rollback_page_title'] ),
					array(
						'back_link' => true,
					)
				);
			}

			$this->plugin_info = (array) $plugin_info;

			$this->plugin_data = get_plugin_data( $plugin_file );

			$is_rollback_available = isset( $this->plugin_info['allow_rollback'] );
			$is_rollback_allowed   = $is_rollback_available && $this->plugin_info['allow_rollback'];

			if ( ! $is_rollback_available ) {
				wp_die(
					sprintf( esc_html( $strings['rollback_not_available'] ), esc_html( $this->plugin_info['name'] ) ),
					esc_html( $strings['rollback_page_title'] ),
					array(
						'back_link' => true,
					)
				);
			}

			if ( ! $is_rollback_allowed ) {
				wp_die(
					sprintf( esc_html( $strings['rollback_not_available'] ), esc_html( $this->plugin_info['name'] ) ),
					esc_html( $strings['rollback_page_title'] ),
					array(
						'back_link' => true,
					)
				);
			}

			$this->get_dialog();

			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 15 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		}

		/**
		 * Get Dialog Instance;
		 *
		 * @return Changelog_Dialog
		 */
		public function get_dialog(): Changelog_Dialog {
			// Load Changelog_Dialog In Singleton It Does load In Page Once.
			return Changelog_Dialog::instance( $this );
		}
	}
}
