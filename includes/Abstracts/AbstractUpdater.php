<?php
	/**
	 * Abstract Plugin Updater Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\ServiceProviders\Internal\UpdaterServiceProvider;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;
	use StorePress\AdminUtils\Traits\ManageServiceProviderTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;
	use StorePress\AdminUtils\Traits\PluginCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractUpdater' ) ) {

	/**
	 * Abstract Plugin Updater Class.
	 *
	 * Provides a framework for handling plugin updates from custom update servers.
	 * Supports license key validation, plugin information popup, rollback functionality,
	 * and force update checking.
	 *
	 * @name AbstractUpdater
	 *
	 * @phpstan-use ManageServiceProviderTrait<UpdaterServiceProvider, AbstractUpdater>
	 * @phpstan-use CallerTrait<object>
	 *
	 * @example Basic implementation:
	 *          ```php
	 *          class My_Plugin_Updater extends AbstractUpdater {
	 *              use Singleton;
	 *
	 *              public function plugin_file(): string {
	 *                  return MY_PLUGIN_FILE;
	 *              }
	 *
	 *              public function license_key(): string {
	 *                  return get_option( 'my_plugin_license_key', '' );
	 *              }
	 *
	 *              public function product_id(): int {
	 *                  return 123;
	 *              }
	 *
	 *              public function update_server_path(): string {
	 *                  return '/wp-json/plugin-updater/v1/check-update';
	 *              }
	 *          }
	 *          ```
	 *
	 * @example Initialize in plugin:
	 *          ```php
	 *          // Plugin header must include:
	 *          // Update URI: https://your-server.com
	 *          // Tested up to: 6.5
	 *
	 *          My_Plugin_Updater::instance( $this );
	 *          ```
	 *
	 * @see UpdaterServiceProvider For service provider integration.
	 * @see Rollback For plugin rollback functionality.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractUpdater {

		use HelperMethodsTrait;
		use PluginCommonTrait;
		use InternalPackageTrait;
		use ManageServiceProviderTrait;
		use CallerTrait;
		use MethodShouldImplementTrait;

		/**
		 * Plugin Data.
		 *
		 * @var array<string, mixed>
		 */
		protected array $plugin_data = array();

		/**
		 * Constructor.
		 *
		 * Initializes the plugin updater by setting up the caller, registering
		 * service providers, and initializing hooks.
		 *
		 * @param object $caller The caller class instance (typically the main plugin class).
		 *
		 * @throws \WP_Exception When plugin file header is missing "Update URI" or "Tested up to" fields.
		 *
		 * @see set_caller()
		 * @see register_service_provider()
		 * @see register_services()
		 * @see hooks()
		 * @see init()
		 *
		 * @since 1.0.0
		 */
		public function __construct( object $caller ) {
			$this->set_caller( $caller );
			$this->register_service_provider( $this );
			$this->register_services();
			$this->hooks();
			$this->init();
		}

		/**
		 * Initialize additional functionality.
		 *
		 * Override this method in subclass to add custom initialization logic.
		 * Called after service providers are registered and hooks are set up.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function init(): void {}

		/**
		 * Create service provider instance.
		 *
		 * Returns a new UpdaterServiceProvider instance for managing updater services.
		 *
		 * @param object $caller The caller class instance.
		 *
		 * @return UpdaterServiceProvider The service provider instance.
		 *
		 * @see UpdaterServiceProvider
		 *
		 * @since 1.0.0
		 */
		public function service_provider( object $caller ): UpdaterServiceProvider {
			return new UpdaterServiceProvider( $caller );
		}

		/**
		 * Register WordPress action hooks.
		 *
		 * Sets up hooks for loading services and initializing the updater
		 * after WordPress is fully loaded.
		 *
		 * @return void
		 *
		 * @see loaded()
		 * @see load_services()
		 *
		 * @since 1.0.0
		 */
		public function hooks(): void {
			// Initialize updater on wp_loaded hook.
			add_action( 'wp_loaded', array( $this, 'loaded' ) );
			// Load additional services on wp_loaded hook (priority 12).
			add_action( 'wp_loaded', array( $this, 'load_services' ), 12 );
		}

		/**
		 * Check if current user has capability to update plugins.
		 *
		 * Verifies the user has 'update_plugins' capability and that
		 * the get_plugin_data() function is available.
		 *
		 * @return bool True if user can update plugins, false otherwise.
		 *
		 * @since 1.0.0
		 */
		public function has_capability(): bool {
			return current_user_can( 'update_plugins' ) && function_exists( 'get_plugin_data' );
		}

		/**
		 * Initialize updater hooks.
		 *
		 * Callback for the 'wp_loaded' action hook. Registers all necessary filters
		 * and actions for plugin update checking, information display, and force update.
		 *
		 * @return void
		 *
		 * @throws \WP_Exception When "Update URI" or "Tested up to" headers are missing.
		 *
		 * @see has_capability()
		 * @see add_tested_upto_info()
		 * @see plugin_information()
		 * @see update_check()
		 * @see update_message()
		 * @see check_for_update_link()
		 * @see force_update_check()
		 *
		 * @since 1.0.0
		 */
		public function loaded(): void {

			if ( ! $this->has_capability() ) {
				return;
			}

			// Add extra plugin header to display WP Tested Upto Info.
			add_filter( 'extra_plugin_headers', array( $this, 'add_tested_upto_info' ) );

			$plugin_data = $this->get_plugin_data();

			if ( ! isset( $plugin_data['UpdateURI'] ) ) {

				$message = 'Plugin "Update URI" is not available. Please add "Update URI" field on plugin file header.';
				wp_trigger_error( '', $message );

				return;
			}

			if ( ! isset( $plugin_data['Tested up to'] ) ) {
				$message = 'Plugin "Tested up to" is not available. Please add "Tested up to" field on plugin file header.';
				wp_trigger_error( '', $message );

				return;
			}

			$plugin_id       = $this->get_plugin_basename();
			$plugin_hostname = $this->get_update_server_hostname();
			$action_id       = $this->get_action_id();

			// Plugin Popup Information When People Click On: View Details or View version x.x.x details link.
			add_filter( 'plugins_api', array( $this, 'plugin_information' ), 11, 3 );
			// Check plugin update information from server.
			add_filter( "update_plugins_{$plugin_hostname}", array( $this, 'update_check' ), 11, 3 );
			// Add some info at the end of plugin update notice like: notice to update license data.
			add_action( "in_plugin_update_message-{$plugin_id}", array( $this, 'update_message' ) );
			// Add force update check link.
			add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );
			// Run force update check action.
			add_action( "admin_action_{$action_id}", array( $this, 'force_update_check' ) );
		}

		/**
		 * Load and boot registered services.
		 *
		 * Callback for the 'wp_loaded' action hook (priority 12). Boots all
		 * registered services if the user has capability to update plugins.
		 *
		 * @return void
		 *
		 * @see has_capability()
		 * @see boot_services()
		 *
		 * @since 1.0.0
		 */
		public function load_services(): void {
			if ( ! $this->has_capability() ) {
				return;
			}

			$this->boot_services();
		}

		/**
		 * Get the license key for update authentication.
		 *
		 * Implement this method to return the license key used for authenticating
		 * with the update server.
		 *
		 * @return string The license key.
		 *
		 * @see get_license_key()
		 *
		 * @since 1.0.0
		 */
		abstract public function license_key(): string;

		/**
		 * Translatable Strings.
		 *
		 * @abstract Method should be overridden in subclass.
		 *
		 * @return array{
		 *      license_key_empty_message: string,
		 *      check_update_link_text: string,
		 *      rollback_changelog_title: string,
		 *      rollback_action_running: string,
		 *      rollback_action_button: string,
		 *      rollback_cancel_button: string,
		 *      rollback_current_version: string,
		 *      rollback_last_updated: string,
		 *      rollback_view_changelog: string,
		 *      rollback_page_title: string,
		 *      rollback_link_text: string,
		 *      rollback_failed: string,
		 *      rollback_success: string,
		 *      rollback_plugin_not_available: string,
		 *      rollback_no_access: string,
		 *      rollback_not_available: string,
		 *      rollback_no_target_version: string
		 * } Associative array of translatable strings with their default English values.
		 * @throws \WP_Exception Method should be overridden in subclass.
		 */
		public function localize_strings(): array {

			$this->subclass_should_implement( __FUNCTION__ );

			return array(
				'license_key_empty_message'     => 'License key is not available.',
				'check_update_link_text'        => 'Check Update',
				'rollback_changelog_title'      => 'Changelog',
				'rollback_action_running'       => 'Rolling back',
				'rollback_action_button'        => 'Rollback',
				'rollback_cancel_button'        => 'Cancel',
				'rollback_current_version'      => 'Current version',
				'rollback_last_updated'         => 'Last updated %s ago.',
				'rollback_view_changelog'       => 'View Changelog',
				'rollback_page_title'           => 'Rollback Plugin',
				'rollback_link_text'            => 'Rollback',
				'rollback_failed'               => 'Rollback failed.',
				'rollback_success'              => 'Rollback success: %s rolled back to version %s.',
				'rollback_plugin_not_available' => 'Plugin is not available.',
				'rollback_no_access'            => 'Sorry, you are not allowed to rollback plugins for this site.',
				'rollback_not_available'        => 'Rollback is not available for plugin: %s',
				'rollback_no_target_version'    => 'Plugin version not selected.',
			);
		}

		/**
		 * Get the product ID for update server authentication.
		 *
		 * Implement this method to return the product ID used for identifying
		 * the plugin on the update server.
		 *
		 * @return int The product ID.
		 *
		 * @see get_product_id()
		 *
		 * @since 1.0.0
		 */
		abstract public function product_id(): int;

		/**
		 * Get plugin data from file headers.
		 *
		 * Retrieves and caches the plugin header data using WordPress get_plugin_data().
		 *
		 * @return array<string, mixed> The plugin header data.
		 *
		 * @see get_plugin_file()
		 *
		 * @since 1.0.0
		 */
		public function get_plugin_data(): array {

			if ( array_key_exists( 'Name', $this->plugin_data ) ) {
				return $this->plugin_data;
			}

			$this->plugin_data = get_plugin_data( $this->get_plugin_file() );

			return $this->plugin_data;
		}

		/**
		 * Get the license key wrapper.
		 *
		 * Returns the license key from the abstract method.
		 *
		 * @return string The license key.
		 *
		 * @see license_key()
		 *
		 * @since 1.0.0
		 */
		public function get_license_key(): string {
			return $this->license_key();
		}

		/**
		 * Get the product ID wrapper.
		 *
		 * Returns the product ID from the abstract method.
		 *
		 * @return int The product ID.
		 *
		 * @see product_id()
		 *
		 * @since 1.0.0
		 */
		public function get_product_id(): int {
			return $this->product_id();
		}

		/**
		 * Add additional request arguments for update API.
		 *
		 * Override this method to add custom arguments to the update server request.
		 *
		 * @return array<string, string> Additional request arguments.
		 *
		 * @example Override to add custom data:
		 *          ```php
		 *          public function additional_request_args(): array {
		 *              return array(
		 *                  'site_url' => site_url(),
		 *                  'php_version' => PHP_VERSION,
		 *              );
		 *          }
		 *          ```
		 *
		 * @see get_request_args()
		 *
		 * @since 1.0.0
		 */
		public function additional_request_args(): array {
			return array();
		}

		/**
		 * Get the client hostname.
		 *
		 * Returns the hostname of the current WordPress site.
		 *
		 * @return string The client hostname.
		 *
		 * @since 1.0.0
		 */
		public function get_client_hostname(): string {
			return wp_parse_url( sanitize_url( site_url() ), PHP_URL_HOST );
		}

		/**
		 * Get the update server hostname.
		 *
		 * Extracts the hostname from the plugin's Update URI header.
		 *
		 * @return string The update server hostname.
		 *
		 * @see get_plugin_data()
		 *
		 * @since 1.0.0
		 */
		final public function get_update_server_hostname(): string {
			$data                   = $this->get_plugin_data();
			$update_server_hostname = untrailingslashit( $data['UpdateURI'] );

			return (string) wp_parse_url( sanitize_url( $update_server_hostname ), PHP_URL_HOST );
		}

		/**
		 * Get the full update server API URL.
		 *
		 * Combines the Update URI scheme and host with the update server path.
		 *
		 * @return string The complete update server URL.
		 *
		 * @see get_update_server_hostname()
		 * @see get_update_server_path()
		 *
		 * @since 1.0.0
		 */
		final public function get_update_server_uri(): string {

			$data                   = $this->get_plugin_data();
			$update_server_hostname = untrailingslashit( $data['UpdateURI'] );

			$scheme = wp_parse_url( sanitize_url( $update_server_hostname ), PHP_URL_SCHEME );

			$host = $this->get_update_server_hostname();
			$path = $this->get_update_server_path();

			return sprintf( '%s://%s%s', $scheme, $host, $path );
		}

		/**
		 * Get the update server API path without hostname.
		 *
		 * Implement this method to return the API endpoint path on the update server.
		 *
		 * @return string The API path.
		 *
		 * @example Return REST API endpoint:
		 *          ```php
		 *          public function update_server_path(): string {
		 *              return '/wp-json/plugin-updater/v1/check-update';
		 *          }
		 *          ```
		 *
		 * @see get_update_server_path()
		 *
		 * @since 1.0.0
		 */
		abstract public function update_server_path(): string;

		/**
		 * Get the update server path with leading slash.
		 *
		 * Wrapper for update_server_path() that ensures the path has a leading slash.
		 *
		 * @return string The API path with leading slash.
		 *
		 * @see update_server_path()
		 *
		 * @since 1.0.0
		 */
		public function get_update_server_path(): string {
			return $this->leadingslashit( $this->update_server_path() );
		}

		/**
		 * Force check for plugin updates.
		 *
		 * Clears the plugins cache and redirects to the plugins page to trigger
		 * a fresh update check from the server.
		 *
		 * @return void
		 *
		 * @see check_for_update_link()
		 *
		 * @since 1.0.0
		 */
		final public function force_update_check(): void {

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			if ( ! function_exists( 'wp_clean_plugins_cache' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			check_admin_referer( $this->get_plugin_basename() );

			wp_clean_plugins_cache();

			wp_safe_redirect( admin_url( 'plugins.php' ) );
			exit;
		}

		/**
		 * Get the admin action ID for force update check.
		 *
		 * @return string The action ID.
		 *
		 * @see force_update_check()
		 *
		 * @since 1.0.0
		 */
		private function get_action_id(): string {
			return sprintf( '%s_check_update', $this->get_plugin_slug() );
		}

		/**
		 * Add "Check Update" link to plugin row meta.
		 *
		 * Filter callback for 'plugin_row_meta'. Adds a force update check link
		 * to the plugin's row on the plugins page.
		 *
		 * @param string[] $plugin_meta An array of the plugin's metadata, including
		 *                              the version, author, author URI, and plugin URI.
		 * @param string   $plugin_file Path to the plugin file relative to the plugins' directory.
		 *
		 * @return array<string, string> Modified plugin meta array.
		 *
		 * @throws \WP_Exception If "localize_strings" method not overridden in subclass.
		 *
		 * @see force_update_check()
		 * @see localize_strings()
		 *
		 * @since 1.0.0
		 */
		public function check_for_update_link( array $plugin_meta, string $plugin_file ): array {

			if ( $plugin_file === $this->get_plugin_basename() && current_user_can( 'update_plugins' ) ) {
				$strings  = $this->localize_strings();
				$id       = $this->get_action_id();
				$url      = wp_nonce_url( add_query_arg( array( 'action' => $id ), admin_url( 'plugins.php' ) ), $this->get_plugin_basename() );
				$text     = $strings['check_update_link_text'];
				$row_meta = sprintf( '<a href="%1$s" aria-label="%2$s">%2$s</a>', esc_url( $url ), esc_html( $text ) );

				$plugin_meta[] = $row_meta;
			}

			return $plugin_meta;
		}

		/**
		 * Add "Tested up to" header support.
		 *
		 * Filter callback for 'extra_plugin_headers'. Adds the "Tested up to"
		 * header to the list of recognized plugin headers.
		 *
		 * @param string[] $headers Available plugin header names.
		 *
		 * @return string[] Modified headers array.
		 *
		 * @since 1.0.0
		 */
		public function add_tested_upto_info( array $headers ): array {
			return array_merge( $headers, array( 'Tested up to' ) );
		}

		/**
		 * Define plugin banner images.
		 *
		 * Override this method to customize the plugin banners shown in the
		 * plugin information popup.
		 *
		 * @return array<string, string> Banner images with 'high' and/or 'low' resolution keys.
		 *
		 * @example Override banners:
		 *          ```php
		 *          public function plugin_banners(): array {
		 *              return array(
		 *                  'high' => 'https://example.com/banner-1544x500.png',
		 *                  'low'  => 'https://example.com/banner-772x250.png',
		 *              );
		 *          }
		 *          ```
		 *
		 * @see get_plugin_banners()
		 *
		 * @since 1.0.0
		 */
		public function plugin_banners(): array {

			return array(
				'low' => $this->get_package_image_url() . '/banner.svg',
			);
		}

		/**
		 * Get plugin banner images wrapper.
		 *
		 * Returns the plugin banners from the plugin_banners() method.
		 *
		 * @return array<string, string> Banner images array.
		 *
		 * @see plugin_banners()
		 *
		 * @since 1.0.0
		 */
		public function get_plugin_banners(): array {
			return $this->plugin_banners();
		}

		/**
		 * Define plugin icon images.
		 *
		 * Override this method to customize the plugin icons shown in the
		 * plugin information popup and updates screen.
		 *
		 * @return array<string, string> Icon images with '2x', '1x', and/or 'svg' keys.
		 *
		 * @example Override icons:
		 *          ```php
		 *          public function plugin_icons(): array {
		 *              return array(
		 *                  'svg' => 'https://example.com/icon.svg',
		 *                  '2x'  => 'https://example.com/icon-256x256.png',
		 *                  '1x'  => 'https://example.com/icon-128x128.png',
		 *              );
		 *          }
		 *          ```
		 *
		 * @see get_plugin_icons()
		 *
		 * @since 1.0.0
		 */
		public function plugin_icons(): array {
			return array(
				'svg' => $this->get_package_image_url() . '/icon.svg',
			);
		}

		/**
		 * Get plugin icon images wrapper.
		 *
		 * Returns the plugin icons from the plugin_icons() method.
		 *
		 * @return array<string, string> Icon images array.
		 *
		 * @see plugin_icons()
		 *
		 * @since 1.0.0
		 */
		public function get_plugin_icons(): array {
			return $this->plugin_icons();
		}

		/**
		 * Get custom plugin description section.
		 *
		 * Override this method to provide a custom description for the plugin
		 * information popup instead of the plugin file header description.
		 *
		 * @return string The custom description HTML or empty string to use default.
		 *
		 * @see plugin_information()
		 *
		 * @since 1.0.0
		 */
		public function get_plugin_description_section(): string {
			return '';
		}

		/**
		 * Get HTTP request arguments for update server.
		 *
		 * Builds the arguments array for wp_remote_get() when contacting
		 * the update server.
		 *
		 * @return array<string, mixed> Request arguments array.
		 *
		 * @see get_remote_plugin_data()
		 * @see additional_request_args()
		 *
		 * @since 1.0.0
		 */
		protected function get_request_args(): array {
			return array(
				'body'       => array(
					'type'        => 'plugins',
					'name'        => $this->get_plugin_basename(),
					'license_key' => sanitize_text_field( $this->get_license_key() ),
					'product_id'  => absint( $this->get_product_id() ),
					'args'        => map_deep( $this->additional_request_args(), 'sanitize_text_field' ),
				),
				'headers'    => array(
					'Accept' => 'application/json',
				),
				'user-agent' => 'WordPress/' . wp_get_wp_version() . '; ' . home_url( '/' ),
			);
		}

		/**
		 * Fetch plugin data from remote update server.
		 *
		 * Makes an HTTP request to the update server and returns the response data.
		 *
		 * @return array<string, string> Remote plugin data or empty array on failure.
		 *
		 * @see get_request_args()
		 * @see get_update_server_uri()
		 *
		 * @since 1.0.0
		 */
		public function get_remote_plugin_data(): array {
			$params = $this->get_request_args();

			// DO NOT USE SAME SERVER AS UPDATE RESPONSE SERVER AND UPDATE REQUEST CLIENT.
			$raw_response = wp_remote_get( $this->get_update_server_uri(), $params ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get

			if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
				return array();
			}

			return json_decode( wp_remote_retrieve_body( $raw_response ), true );
		}

		/**
		 * Check for plugin updates from custom server.
		 *
		 * Filter callback for 'update_plugins_{hostname}'. Checks the custom update
		 * server for new plugin versions.
		 *
		 * @param bool|array<string, mixed> $update       The plugin update data with the latest details.
		 * @param array<string, mixed>      $_plugin_data Plugin headers.
		 * @param string                    $plugin_file  Plugin filename.
		 *
		 * @return bool|array<string, mixed> Update data array or false/original value.
		 *
		 * @see WP_Site_Health::detect_plugin_theme_auto_update_issues()
		 * @see wp_update_plugins() In wp-includes/update.php.
		 * @see get_remote_plugin_data()
		 * @see prepare_remote_data()
		 *
		 * @example Server API endpoint:
		 *          https://example.com/updater-api/wp-json/plugin-updater/v1/check-update
		 *
		 * @since 1.0.0
		 */
		final public function update_check( $update, array $_plugin_data, string $plugin_file ) {

			if ( $plugin_file !== $this->get_plugin_basename() ) {
				return $update;
			}

			if ( is_array( $update ) ) {
				return $update;
			}

			$remote_data = $this->get_remote_plugin_data();

			$plugin_data = $this->get_plugin_data();

			if ( $this->is_empty_array( $remote_data ) ) {
				return $update;
			}

			$plugin_version = $plugin_data['Version'];
			$plugin_uri     = $plugin_data['PluginURI'];
			$plugin_tested  = $plugin_data['Tested up to'] ?? '';
			$requires_php   = $plugin_data['RequiresPHP'] ?? '7.4';

			$plugin_id = url_shorten( (string) $plugin_uri, 150 );

			$default_item = array(
				'id'               => $plugin_id, // @example: w.org/plugins/xyz-plugin
				'slug'             => $this->get_plugin_slug(), // @example: xyz-plugin
				'plugin'           => $this->get_plugin_basename(), // @example: xyz-plugin/xyz-plugin.php
				'version'          => $plugin_version,
				'url'              => $plugin_uri,
				'icons'            => $this->get_plugin_icons(),
				'banners'          => $this->get_plugin_banners(),
				'banners_rtl'      => array(),
				'requires'         => '6.4',
				'tested'           => $plugin_tested,
				'requires_php'     => $requires_php,
				'requires_plugins' => array(),
				'package'          => '',
				'allow_rollback'   => false,
			);

			$remote_item = $this->prepare_remote_data( $remote_data );

			return $this->array_merge_deep( $remote_item, $default_item );
		}

		/**
		 * Generate screenshots HTML for plugin information popup.
		 *
		 * Builds an ordered list of screenshot images with captions.
		 *
		 * @param array<string, array<string, string>> $screenshots Screenshot array with 'src' and 'caption' keys.
		 *
		 * @return string The screenshots HTML.
		 *
		 * @see prepare_remote_data()
		 *
		 * @since 1.0.0
		 */
		public function screenshots_html( array $screenshots = array() ): string {
			ob_start();
			echo '<ol>';
			foreach ( $screenshots as $screenshot ) {
				printf( '<li><a target="_blank" href="%1$s"><img src="%1$s" alt="%2$s"></a></li>', esc_url( $screenshot['src'] ), esc_attr( $screenshot['caption'] ) ); // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
			}
			echo '</ol>';

			return ob_get_clean();
		}

		/**
		 * Prepare remote data for WordPress plugin API format.
		 *
		 * Transforms the remote server response into the format expected by
		 * WordPress plugin update and information APIs.
		 *
		 * @param bool|array<string, mixed> $remote_data Remote data from update server.
		 *
		 * @return array<string, mixed> Prepared data in WordPress plugin API format.
		 *
		 * @see update_check()
		 * @see plugin_information()
		 * @see https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=woocommerce
		 *
		 * @example Expected remote data format:
		 *          ```php
		 *          array(
		 *              'new_version'      => 'x.x.x',                    // Required.
		 *              'package'          => 'https://url/plugin.zip',  // Required (or empty).
		 *              'description'      => 'Plugin description',
		 *              'installation'     => 'Installation steps',
		 *              'changelog'        => 'Version changelog',
		 *              'faq'              => 'FAQ content',
		 *              'last_updated'     => '2024-11-11 3:24pm GMT',
		 *              'active_installs'  => 1000,
		 *              'upgrade_notice'   => 'Important notice',
		 *              'screenshots'      => array(
		 *                  array( 'src' => 'url', 'caption' => 'text' ),
		 *              ),
		 *              'tested'           => '6.5',                      // WP tested version.
		 *              'requires'         => '6.0',                      // Minimum WP version.
		 *              'requires_php'     => '7.4',                      // Minimum PHP version.
		 *              'requires_plugins' => array( 'woocommerce' ),
		 *              'versions'         => array(
		 *                  'trunk' => 'https://url/plugin-latest.zip',
		 *                  '1.1.0' => 'https://url/plugin-1.1.0.zip',
		 *              ),
		 *              'banners'          => array(
		 *                  'low'  => 'https://url/banner-772x250.png',
		 *                  'high' => 'https://url/banner-1544x500.png',
		 *              ),
		 *              'icons'            => array(
		 *                  'svg' => 'https://url/icon.svg',
		 *                  '2x'  => 'https://url/icon-256x256.png',
		 *                  '1x'  => 'https://url/icon-128x128.png',
		 *              ),
		 *              'allow_rollback'   => 'yes',                      // Required for rollback.
		 *          )
		 *          ```
		 *
		 * @since 1.0.0
		 */
		final public function prepare_remote_data( $remote_data ): array {
			$item = array();

			if ( $this->is_empty_array( $remote_data ) ) {
				return $item;
			}

			if ( isset( $remote_data['description'] ) ) {
				$item['sections']['description'] = wp_kses_post( $remote_data['description'] );
			}

			if ( isset( $remote_data['allow_rollback'] ) ) {
				$item['allow_rollback'] = $this->string_to_boolean( $remote_data['allow_rollback'] );
			}

			if ( isset( $remote_data['installation'] ) ) {
				$item['sections']['installation'] = wp_kses_post( $remote_data['installation'] );
			}

			if ( isset( $remote_data['faq'] ) ) {
				$item['sections']['faq'] = wp_kses_post( $remote_data['faq'] );
			}

			if ( isset( $remote_data['changelog'] ) ) {
				$item['sections']['changelog'] = wp_kses_post( $remote_data['changelog'] );
			}

			if ( isset( $remote_data['screenshots'] ) && ! $this->is_empty_array( $remote_data['screenshots'] ) ) {
				foreach ( $remote_data['screenshots'] as $index => $screenshot ) {
					$item['screenshots'][ ( $index + 1 ) ] = $screenshot;
				}

				$item['sections']['screenshots'] = wp_kses_post( $this->screenshots_html( $remote_data['screenshots'] ) );
			}

			if ( isset( $remote_data['version'] ) ) {
				$item['new_version'] = $remote_data['version'];
			}

			if ( isset( $remote_data['new_version'] ) ) {
				$item['new_version'] = $remote_data['new_version'];
			}

			// Unset version we already set it as new version.
			unset( $remote_data['version'] );

			if ( isset( $remote_data['last_updated'] ) ) {
				$item['last_updated'] = $remote_data['last_updated']; // Example: "2025-03-14 7:15pm GMT".
			}

			if ( isset( $remote_data['upgrade_notice'] ) ) {
				$item['upgrade_notice'] = $remote_data['upgrade_notice'];
			}

			if ( isset( $remote_data['versions'] ) ) {
				$item['versions'] = $remote_data['versions'];
			}

			$package_set = false;

			if ( isset( $remote_data['download_link'] ) ) {
				$item['package']           = $remote_data['download_link'];
				$item['download_link']     = $remote_data['download_link'];
				$item['versions']['trunk'] = $remote_data['download_link'];
				$package_set               = true;
			}

			if ( isset( $remote_data['package'] ) && ! $package_set ) {
				$item['package']           = $remote_data['package'];
				$item['download_link']     = $remote_data['package'];
				$item['versions']['trunk'] = $remote_data['package'];
				$package_set               = true;
			}

			// Disable all versions if no package file available.
			if ( ! $package_set ) {
				$item['versions'] = array();
			}

			if ( isset( $remote_data['tested'] ) ) {
				$item['tested'] = $remote_data['tested'];
			}

			if ( isset( $remote_data['requires'] ) ) {
				$item['requires'] = $remote_data['requires'];
			}

			if ( isset( $remote_data['requires_php'] ) ) {
				$item['requires_php'] = $remote_data['requires_php'];
			}

			if ( isset( $remote_data['preview_link'] ) ) {
				$item['preview_link'] = $remote_data['preview_link'];
			}

			if ( isset( $remote_data['requires_plugins'] ) ) {
				$item['requires_plugins'] = $remote_data['requires_plugins'];
			}

			if ( isset( $remote_data['active_installs'] ) ) {
				$item['active_installs'] = absint( $remote_data['active_installs'] );
			}

			if ( isset( $remote_data['rating'] ) ) {
				$item['rating'] = absint( $remote_data['rating'] );
			}

			if ( isset( $remote_data['ratings'] ) ) {
				$item['ratings'] = $remote_data['ratings'];
			}

			if ( isset( $remote_data['support_threads'] ) ) {
				$item['support_threads'] = absint( $remote_data['support_threads'] );
			}

			if ( isset( $remote_data['support_threads_resolved'] ) ) {
				$item['support_threads_resolved'] = absint( $remote_data['support_threads_resolved'] );
			}

			if ( isset( $remote_data['added'] ) ) {
				$item['added'] = $remote_data['added']; // Example: "2018-05-04".
			}

			if ( isset( $remote_data['homepage'] ) ) {
				$item['homepage'] = $remote_data['homepage'];
			}

			if ( isset( $remote_data['num_ratings'] ) ) {
				$item['num_ratings'] = $remote_data['num_ratings'];
			}

			if ( isset( $remote_data['business_model'] ) ) {
				$business_model         = $this->string_to_boolean( $remote_data['business_model'] ) ? 'commercial' : '';
				$item['business_model'] = $business_model;
			}

			if ( isset( $remote_data['commercial_support_url'] ) ) {
				$item['commercial_support_url'] = $remote_data['commercial_support_url'];
			}

			if ( isset( $remote_data['support_url'] ) ) {
				$item['support_url'] = $remote_data['support_url'];
			}

			if ( isset( $remote_data['banners'] ) ) {
				$item['banners'] = $remote_data['banners'];
			}

			if ( isset( $remote_data['banners_rtl'] ) ) {
				$item['banners_rtl'] = $remote_data['banners_rtl'];
			}

			if ( isset( $remote_data['icons'] ) ) {
				$item['icons'] = $remote_data['icons'];
			}

			if ( isset( $remote_data['preview_link'] ) ) {
				$item['preview_link'] = $remote_data['preview_link'];
			}

			if ( isset( $remote_data['author_profile'] ) ) {
				$item['author_profile'] = $remote_data['author_profile'];
			}

			if ( isset( $remote_data['author'] ) ) {
				$item['author'] = $remote_data['author'];

				if ( isset( $remote_data['author_profile'] ) ) {
					$item['author'] = sprintf( '<a target="_blank" href="%s">%s</a>', esc_url( $remote_data['author_profile'] ), esc_html( $remote_data['author'] ) );
				}
			}

			if ( isset( $remote_data['tags'] ) ) {
				/**
				 * Example.
				 *
				 * @example ["payment-gateway": "payment gateway", "ecommerce": "ecommerce"].
				 */
				$item['tags'] = $remote_data['tags'];
			}

			return $item;
		}

		/**
		 * Provide plugin information for the details popup.
		 *
		 * Filter callback for 'plugins_api'. Returns plugin information for the
		 * "View Details" popup in the WordPress admin.
		 *
		 * @param false|object|array<string, mixed> $result The result object or array. Default false.
		 * @param string                            $action The type of information being requested.
		 * @param object                            $args   Plugin API arguments including 'slug'.
		 *
		 * @return false|array<string, mixed>|object Plugin information object or pass-through result.
		 *
		 * @see plugins_api() In wp-admin/includes/plugin-install.php.
		 * @see get_plugin_data()
		 * @see get_remote_plugin_data()
		 * @see prepare_remote_data()
		 * @see https://developer.wordpress.org/reference/functions/plugins_api/
		 *
		 * @since 1.0.0
		 */
		final public function plugin_information( $result, string $action, object $args ) {

			if ( ! ( 'plugin_information' === $action ) ) {
				return $result;
			}

			if ( isset( $args->slug ) && $args->slug === $this->get_plugin_slug() ) {

				$plugin_data        = $this->get_plugin_data();
				$plugin_name        = $plugin_data['Name'];
				$plugin_description = $plugin_data['Description'];
				$plugin_uri         = $plugin_data['PluginURI'];
				$author             = $plugin_data['Author'];
				$author_uri         = $plugin_data['AuthorURI'];
				$version            = $plugin_data['Version'];
				$plugin_tested      = $plugin_data['Tested up to'] ?? '';
				$requires_php       = $plugin_data['RequiresPHP'] ?? '7.4';

				$get_description = trim( $this->get_plugin_description_section() );
				$description     = '' === $get_description ? $plugin_description : $get_description;

				$default_item = array(
					'name'             => $plugin_name,
					'version'          => $version,
					'slug'             => $this->get_plugin_slug(),
					'banners'          => $this->get_plugin_banners(),
					'banners_rtl'      => array(),
					'icons'            => $this->get_plugin_icons(),
					'author'           => $author,
					'homepage'         => $plugin_uri,
					'requires_php'     => $requires_php,
					'sections'         => array(
						'description' => $description,
					),
					'requires_plugins' => array(),
					'versions'         => array(),
					'allow_rollback'   => false,
				);

				if ( ! $this->is_empty_string( $plugin_tested ) ) {
					$default_item['tested'] = $plugin_tested;
				}

				$remote_item = $this->prepare_remote_data( $this->get_remote_plugin_data() );

				$data = $this->array_merge_deep( $remote_item, $default_item );

				if ( false === $data['allow_rollback'] ) {
					unset( $data['versions'] );
				}

				return (object) $data;
			}

			return $result;
		}

		/**
		 * Display additional update message in plugin row.
		 *
		 * Action callback for 'in_plugin_update_message-{plugin_id}'. Displays
		 * additional information at the end of the plugin update notice, such as
		 * license key warnings or upgrade notices.
		 *
		 * @param array<string, string> $plugin_data An array of plugin metadata including 'new_version' and 'upgrade_notice'.
		 *
		 * @return void
		 *
		 * @see localize_strings()
		 *
		 * @since 1.0.0
		 */
		public function update_message( array $plugin_data ): void {

			$license_key    = $this->get_license_key();
			$upgrade_notice = $plugin_data['upgrade_notice'] ?? '';

			$strings = $this->localize_strings();

			if ( $this->is_empty_string( $license_key ) ) {
				printf( ' <strong>%s</strong>', esc_html( $strings['license_key_empty_message'] ) );
			}

			// Get Notice from notice array.
			if ( is_array( $upgrade_notice ) && ! $this->is_empty_array( $upgrade_notice ) ) {
				$new_version = sanitize_text_field( $plugin_data['new_version'] );

				$notice = $this->get_var( $upgrade_notice[ $new_version ], false );

				if ( $notice && ! $this->is_empty_string( $notice ) ) {
					printf( ' <br /><br /><strong><em>%s</em></strong>', esc_html( $notice ) );
				}
			}

			// Get Notice from notice string.
			if ( is_string( $upgrade_notice ) && ! $this->is_empty_string( $upgrade_notice ) ) {
				printf( ' <br /><br /><strong><em>%s</em></strong>', esc_html( $upgrade_notice ) );
			}
		}
	}
}
