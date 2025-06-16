<?php
	/**
	 * Plugin Rollback API Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

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
		 * @var array
		 */
		private array $plugin_info;

		/**
		 * Rollback Plugin Data.
		 *
		 * @var array
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
		 * @param string                $plugin_file Plugin file.
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
		 * Get Plugin Info after page load.
		 *
		 * @return array
		 */
		public function get_plugin_info(): array {
			return $this->plugin_info;
		}

		/**
		 * Get plugin file data.
		 *
		 * @return array
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
			return 'storepress-plugin-rollback';
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

			$plugin_basename = $this->get_plugin_basename();

			// Rollback.
			add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'action_links' ), 12 );

			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 20 );

			add_action( 'wp_ajax_storepress_plugin_rollback', array( $this, 'handle_rollback' ) );
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

			$strings = $this->get_localize_strings();

			$plugin  = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';
			$version = isset( $_POST['targetVersion'] ) ? sanitize_text_field( $_POST['targetVersion'] ) : '';
			$slug    = isset( $_POST['slug'] ) ? sanitize_text_field( $_POST['slug'] ) : '';

			// Is plugin file not empty.
			if ( $this->is_empty_string( $plugin ) ) {
				$status['message'] = esc_html( $strings['rollback_plugin_not_available'] );
				wp_send_json_error( $status );
			}

			// Check capability.
			if ( ! current_user_can( 'update_plugins' ) || 0 !== validate_file( $plugin ) ) {
				$status['message'] = esc_html( $strings['rollback_no_access'] );
				wp_send_json_error( $status );
			}

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

			$status['pluginName']     = $plugin_data['Name'];
			$status['plugin']         = $plugin;
			$status['slug']           = $slug;
			$status['currentVersion'] = $plugin_data['Version'];
			$status['targetVersion']  = $version;

			if ( is_wp_error( $plugin_info ) ) {
				$status['message'] = $plugin_info->get_error_message();
				wp_send_json_error( $status );
			}

			$plugin_info = (array) $plugin_info;

			$package = trim( $plugin_info['versions'][ $version ] );

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Rollback( $skin );

			$result = $upgrader->rollback( $plugin, $package );

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

			if ( ! $this->is_rollback_page() ) {
				return;
			}

			$plugin_dir_url  = untrailingslashit( plugin_dir_url( $this->get_plugin_file() ) );
			$plugin_dir_path = untrailingslashit( plugin_dir_path( $this->get_plugin_file() ) );

			$script_url        = $plugin_dir_url . '/vendor/storepress/admin-utils/build/rollback.js';
			$style_url         = $plugin_dir_url . '/vendor/storepress/admin-utils/build/rollback.css';
			$script_asset_file = $plugin_dir_path . '/vendor/storepress/admin-utils/build/rollback.asset.php';
			$script_assets     = include $script_asset_file;


			// Admin Utils.
			$utils_script_url = $plugin_dir_url . '/vendor/storepress/admin-utils/build/storepress-utils.js';
			$utils_asset_file = $plugin_dir_path . '/vendor/storepress/admin-utils/build/storepress-utils.asset.php';
			$utils_asset      = include $utils_asset_file;
			wp_register_script( 'storepress-utils', $utils_script_url, $utils_asset['dependencies'], $utils_asset['version'], true );

			wp_register_script(
				'storepress-plugin-rollback',
				$script_url,
				$script_assets['dependencies'],
				$script_assets['version'],
				array(
					'in_footer' => true,
				)
			);
			wp_register_style( 'storepress-plugin-rollback', $style_url, array(), $script_assets['version'] );

			wp_localize_script(
				'storepress-plugin-rollback',
				'StorePressPluginRollbackStrings',
				array_merge(
					$this->get_localize_strings(),
					array(
						'plugin' => $this->get_plugin_basename(),
						'slug'   => $this->get_plugin_slug(),
					)
				)
			);
		}

		/**
		 * Check is admin page.
		 *
		 * @return bool
		 */
		public function is_rollback_page(): bool {
			// We have to check is valid current page.
			return ( is_admin() && $this->menu_slug() === $this->http_get_var( 'page' ) );
		}

		/**
		 * Get plugin file.
		 *
		 * @return string
		 */
		public function get_plugin_file(): string {
			return $this->plugin_file;
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
		 * Plugin BaseName Like "plugin-directory/plugin-file.php"
		 *
		 * @return string
		 * @example xyz-plugin/xyz-plugin.php
		 */
		public function get_plugin_basename(): string {
			return plugin_basename( $this->get_plugin_file() );
		}

		/**
		 * Plugin Slug
		 *
		 * @return string
		 * @example xyz-plugin
		 */
		public function get_plugin_slug(): string {
			return wp_basename( dirname( $this->get_plugin_file() ) );
		}

		/**
		 * Plugin Dir Path.
		 *
		 * @return string
		 */
		public function get_plugin_dir_path(): string {
			return plugin_dir_path( $this->get_plugin_file() );
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

			$nonce_action = $this->menu_slug();

			$page_url = menu_page_url( $this->menu_slug(), false );

			$action_url = add_query_arg( array( 'plugin' => $this->get_plugin_basename() ), $page_url );

			$page_link = wp_nonce_url( $action_url, $nonce_action );

			$action_links = sprintf( '<a href="%1$s" aria-label="%2$s">%2$s</a>', esc_url( $page_link ), esc_html( $this->get_link_text() ) );

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

			$page_id = add_submenu_page(
				$this->main_menu_slug(),
				$this->get_page_title(),
				'',
				'update_plugins',
				$this->menu_slug(),
				array( $this, 'show_page' )
			);

			add_action( 'load-' . $page_id, array( $this, 'process_page' ) );

			remove_menu_page( $this->main_menu_slug() );
		}

		/**
		 * Show admin page.
		 *
		 * @return void|\WP_Error
		 */
		public function show_page() {
			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			check_admin_referer( $this->menu_slug() );

			include_once __DIR__ . '/templates/rollback-template.php';

			wp_print_request_filesystem_credentials_modal();
			wp_print_admin_notice_templates();
		}

		/**
		 * Process page before admin page show.
		 *
		 * @return void
		 */
		public function process_page() {

			if ( ! $this->is_rollback_page() ) {
				return;
			}

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			check_admin_referer( $this->menu_slug() );

			$plugin = isset( $_GET['plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) : '';

			$validate_plugin = validate_plugin( $plugin );

			if ( is_wp_error( $validate_plugin ) ) {
				wp_die(
					esc_html( $validate_plugin->get_error_message() ),
					'',
					array(
						'back_link' => true,
					)
				);
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$slug = sanitize_key( dirname( $plugin ) );

			$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

			if ( is_wp_error( $plugin_info ) ) {
				wp_die(
					esc_html( $plugin_info->get_error_message() ),
					'',
					array(
						'back_link' => true,
					)
				);
			}

			$this->plugin_info = (array) $plugin_info;

			$this->plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

			$is_rollback_available = isset( $this->plugin_info['allow_rollback'] );
			$is_rollback_allowed   = $is_rollback_available && $this->plugin_info['allow_rollback'];

			if ( ! $is_rollback_available ) {
				wp_die(
					sprintf( 'Rollback is not available for plugin "%s"', esc_html( $this->plugin_info['name'] ) ),
					'',
					array(
						'back_link' => true,
					)
				);
			}

			if ( ! $is_rollback_allowed ) {
				wp_die(
					sprintf( 'Rollback is not allowed for plugin "%s"', esc_html( $this->plugin_info['name'] ) ),
					'',
					array(
						'back_link' => true,
					)
				);
			}

			wp_enqueue_script( 'updates' );
			wp_enqueue_script( 'storepress-plugin-rollback' );
			wp_enqueue_style( 'storepress-plugin-rollback' );
		}
	}
}
