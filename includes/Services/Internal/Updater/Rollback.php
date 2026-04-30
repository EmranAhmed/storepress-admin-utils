<?php
	/**
	 * Plugin Rollback Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\Updater;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractUpdater;
	use StorePress\AdminUtils\Factory\RollbackFactory;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Updater\Rollback' ) ) {
	/**
	 * Plugin Rollback Class.
	 *
	 * Provides rollback functionality allowing admins to downgrade a plugin to a
	 * previously released version. Handles admin menu, AJAX processing, script
	 * registration, and plugin API integration.
	 *
	 * @name Rollback
	 *
	 * @since 1.0.0
	 *
	 * @see AbstractUpdater For the parent updater class.
	 * @see Dialog          For the changelog dialog.
	 * @see RollbackFactory For the factory that creates upgrader and dialog instances.
	 */
	class Rollback {

		use HelperMethodsTrait;
		use InternalPackageTrait;

		/**
		 * Plugin information from plugins API.
		 *
		 * Populated after settings_init() runs. Contains version history,
		 * changelog, and other plugin metadata.
		 *
		 * @since 1.0.0
		 *
		 * @var array<string, mixed>
		 */
		private array $plugin_info;

		/**
		 * Plugin header data from file.
		 *
		 * Populated after settings_init() runs. Contains plugin name,
		 * version, author, and other header information.
		 *
		 * @since 1.0.0
		 *
		 * @var array<string, mixed>
		 */
		private array $plugin_data;

		/**
		 * Parent updater instance that owns this rollback service.
		 *
		 * @since 1.0.0
		 *
		 * @var AbstractUpdater
		 */
		protected AbstractUpdater $updater;

		/**
		 * Factory instance for creating upgrader skin and dialog objects.
		 *
		 * @since 1.0.0
		 *
		 * @var RollbackFactory
		 */
		protected RollbackFactory $factory;

		/**
		 * Construct Rollback instance.
		 *
		 * @param AbstractUpdater      $updater Parent updater instance.
		 * @param RollbackFactory|null $factory Optional. Factory class name. Default RollbackFactory::class.
		 *
		 * @since 1.0.0
		 */
		public function __construct( AbstractUpdater $updater, ?RollbackFactory $factory = null ) {
			$this->updater = $updater;
			$this->factory = $factory ?? RollbackFactory::instance();
			$this->hook();
		}

		/**
		 * Get the parent updater instance.
		 *
		 * @since 1.0.0
		 *
		 * @return AbstractUpdater
		 */
		public function get_updater(): AbstractUpdater {
			return $this->updater;
		}

		/**
		 * Get the plugin file path from the parent updater.
		 *
		 * @since 1.0.0
		 *
		 * @return string Plugin file path (e.g. 'my-plugin/my-plugin.php').
		 *
		 * @see AbstractUpdater::get_plugin_file()
		 */
		public function plugin_file(): string {
			return $this->get_updater()->get_plugin_file();
		}

		/**
		 * Get the rollback factory instance.
		 *
		 * @since 1.0.0
		 *
		 * @return RollbackFactory
		 */
		public function get_factory(): RollbackFactory {
			return $this->factory;
		}

		/**
		 * Get plugin information from API.
		 *
		 * Returns the plugin information fetched from the update server.
		 * Only available after settings_init() has run.
		 *
		 * @return array<string, mixed> The plugin info array.
		 *
		 * @see settings_init()
		 *
		 * @since 1.0.0
		 */
		public function get_plugin_info(): array {
			return $this->plugin_info;
		}

		/**
		 * Get plugin file header data.
		 *
		 * Returns the plugin header data from the main plugin file.
		 * Only available after settings_init() has run.
		 *
		 * @return array<string, mixed> The plugin data array.
		 *
		 * @see settings_init()
		 *
		 * @since 1.0.0
		 */
		public function get_plugin_data(): array {
			return $this->plugin_data;
		}

		/**
		 * Get localized strings from parent updater.
		 *
		 * Returns the localization strings defined in the parent updater class.
		 *
		 * @return array<string, string> The localized strings array.
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see AbstractUpdater::localize_strings()
		 *
		 * @since 1.0.0
		 */
		public function get_localize_strings(): array {
			return $this->get_updater()->localize_strings();
		}

		/**
		 * Get the hidden parent menu slug.
		 *
		 * Returns the slug for the hidden parent menu that contains the rollback submenu.
		 *
		 * @return string The main menu slug.
		 *
		 * @since 1.0.0
		 */
		public function main_menu_slug(): string {
			return 'storepress-plugin-rollback-main';
		}

		/**
		 * Get the rollback page menu slug.
		 *
		 * Returns the unique menu slug for this plugin's rollback page.
		 *
		 * @return string The menu slug.
		 *
		 * @since 1.0.0
		 */
		public function menu_slug(): string {
			return sprintf( 'storepress-%s-rollback', $this->get_plugin_slug() );
		}

		/**
		 * Get nonce action string.
		 *
		 * @return string The nonce action.
		 *
		 * @since 1.0.0
		 */
		public function get_nonce_action(): string {
			return $this->menu_slug();
		}

		/**
		 * Get WordPress nonce query argument name.
		 *
		 * @return string The nonce query arg name.
		 *
		 * @see check_admin_referer()
		 *
		 * @since 1.0.0
		 */
		public function get_wp_nonce_query_arg(): string {
			return '_wpnonce';
		}

		/**
		 * Get AJAX nonce query argument name.
		 *
		 * @return string The AJAX nonce query arg name.
		 *
		 * @see check_ajax_referer()
		 *
		 * @since 1.0.0
		 */
		public function get_ajax_nonce_query_arg(): string {
			return '_ajax_nonce';
		}

		/**
		 * Check if user has capability to perform rollback.
		 *
		 * Verifies the user has 'update_plugins' capability and that
		 * get_plugin_data() function is available.
		 *
		 * @return bool True if user can perform rollback, false otherwise.
		 *
		 * @since 1.0.0
		 */
		public function has_capability(): bool {
			return current_user_can( 'update_plugins' ) && function_exists( 'get_plugin_data' );
		}

		/**
		 * Register WordPress action hooks.
		 *
		 * Sets up hooks for admin menu, plugin action links, and AJAX handling.
		 *
		 * @return void
		 *
		 * @see admin_menu()
		 * @see action_links()
		 * @see handle_rollback()
		 *
		 * @since 1.0.0
		 */
		final public function hook(): void {

			if ( ! $this->has_capability() ) {
				return;
			}

			// Register hidden admin menu for rollback page.
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
			// Add rollback link to plugin action links on plugins page.
			add_filter( 'plugin_action_links_' . $this->get_plugin_basename(), array( $this, 'action_links' ), 12 );
			// Handle AJAX rollback request.
			add_action( 'wp_ajax_' . $this->get_ajax_action(), array( $this, 'handle_rollback' ) );
		}

		/**
		 * Get AJAX action name.
		 *
		 * Returns the unique AJAX action name for this plugin's rollback handler.
		 *
		 * @return string The AJAX action name.
		 *
		 * @since 1.0.0
		 */
		public function get_ajax_action(): string {
			return sprintf( 'storepress_%s_rollback_action', $this->get_plugin_slug() );
		}

		/**
		 * Handle AJAX rollback request.
		 *
		 * Processes the AJAX request to rollback the plugin to a specific version.
		 * Validates permissions, checks plugin existence, and performs the rollback.
		 *
		 * @return void Sends JSON response and exits.
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see Upgrader::rollback()
		 *
		 * @since 1.0.0
		 */
		public function handle_rollback(): void {

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

			// Get Skin and Run Rollback.
			$skin   = $this->get_factory()->get_upgrader_skin();
			$result = $this->get_factory()->run_rollback( $this, $package );

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
		 * Register rollback scripts and localization.
		 *
		 * Registers the rollback package scripts with localized strings
		 * and plugin data for the JavaScript interface.
		 *
		 * @return void
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see register_package_scripts()
		 *
		 * @since 1.0.0
		 */
		public function register_scripts(): void {

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
		 * Enqueue rollback scripts.
		 *
		 * Enqueues the WordPress updates script and the rollback package scripts.
		 *
		 * @return void
		 *
		 * @see enqueue_package_scripts()
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts(): void {
			wp_enqueue_script( 'updates' );
			$this->enqueue_package_scripts( 'rollback' );
		}

		/**
		 * Get the rollback page title.
		 *
		 * @return string The page title.
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see get_localize_strings()
		 *
		 * @since 1.0.0
		 */
		public function get_page_title(): string {
			$localize_strings = $this->get_localize_strings();
			return $localize_strings['rollback_page_title'];
		}

		/**
		 * Get the rollback link text.
		 *
		 * @return string The link text.
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see get_localize_strings()
		 *
		 * @since 1.0.0
		 */
		public function get_link_text(): string {
			$localize_strings = $this->get_localize_strings();
			return $localize_strings['rollback_link_text'];
		}

		/**
		 * Get first non-empty image URL from array.
		 *
		 * Iterates through an array of image URLs and returns the first
		 * non-empty one found.
		 *
		 * @param string[] $images Array of image URLs.
		 *
		 * @return string The first non-empty image URL or empty string.
		 *
		 * @since 1.0.0
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
		 * Add rollback link to plugin action links.
		 *
		 * Filter callback for 'plugin_action_links_{plugin}'. Adds a rollback
		 * link if rollback is enabled for this plugin.
		 *
		 * @param array<string, mixed> $links Existing action links.
		 *
		 * @return array<string, mixed> Modified action links.
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see get_plugin_transient_data()
		 *
		 * @since 1.0.0
		 */
		final public function action_links( array $links ): array {

			// Unset wp.org rollback plugin action.
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
		 * Get plugin update transient data.
		 *
		 * Retrieves the plugin data from the update_plugins transient,
		 * checking both response and no_update arrays.
		 *
		 * @param string $plugin Plugin basename.
		 *
		 * @return array<string, mixed> Plugin transient data or empty array.
		 *
		 * @since 1.0.0
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
		 * Register the admin menu page.
		 *
		 * Creates a hidden parent menu and adds the rollback submenu page.
		 * The parent menu is immediately removed from the sidebar.
		 *
		 * @return void
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see settings_template()
		 * @see settings_init()
		 *
		 * @since 1.0.0
		 */
		final public function admin_menu(): void {

			// Add hidden parent menu page.
			add_menu_page( '', '', 'update_plugins', $this->main_menu_slug(), '__return_false' );

			// Add rollback submenu page under hidden parent.
			$page_hook = add_submenu_page( $this->main_menu_slug(), $this->get_page_title(), '', 'update_plugins', $this->menu_slug(), array( $this, 'settings_template' ) );

			// Initialize settings on page load.
			add_action( 'load-' . $page_hook, array( $this, 'settings_init' ) );

			// Remove the hidden parent menu from admin sidebar.
			remove_menu_page( $this->main_menu_slug() );
		}

		/**
		 * Render the rollback page template.
		 *
		 * Includes the rollback template file and prints necessary
		 * WordPress admin modals.
		 *
		 * @return void
		 *
		 * @see get_package_templates_path()
		 *
		 * @since 1.0.0
		 */
		public function settings_template(): void {

			include_once $this->get_package_templates_path() . '/rollback-template.php';

			wp_print_request_filesystem_credentials_modal();
			wp_print_admin_notice_templates();
		}

		/**
		 * Initialize settings page before rendering.
		 *
		 * Validates the plugin, fetches plugin info from API, checks if
		 * rollback is allowed, and registers scripts.
		 *
		 * @return void
		 *
		 * @throws \WP_Exception If localize_strings() not overridden in parent.
		 *
		 * @see register_scripts()
		 * @see enqueue_scripts()
		 * @see boot_services()
		 *
		 * @since 1.0.0
		 */
		final public function settings_init(): void {

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

			$this->get_factory()->create_dialog( $this );

			// Register rollback scripts and styles.
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 15 );

			// Enqueue rollback scripts and styles.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		}
	}
}
