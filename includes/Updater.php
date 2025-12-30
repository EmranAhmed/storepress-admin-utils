<?php
	/**
	 * Plugin Updater API Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Updater' ) ) {

	/**
	 * Plugin Updater API Class.
	 *
	 * @name Updater
	 */
	abstract class Updater {

		use Common;
		use Plugin;

		/**
		 * Plugin Data.
		 *
		 * @var array<string, mixed>
		 */
		private array $plugin_data = array();

		/**
		 * Updater Plugin Admin Init.
		 */
		public function __construct() {
			add_action( 'wp_loaded', array( $this, 'init' ) );
			add_action( 'wp_loaded', array( $this, 'rollback_init' ) );
		}

		/**
		 * Init Hook.
		 *
		 * @return void
		 */
		public function init(): void {

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			if ( ! function_exists( 'get_plugin_data' ) ) {
				return;
			}

			// Add extra plugin header to display WP Tested Upto Info.
			add_filter( 'extra_plugin_headers', array( $this, 'add_tested_upto_info' ) );

			$plugin_data = $this->get_plugin_data();

			if ( ! isset( $plugin_data['UpdateURI'] ) ) {
				$message = 'Plugin "Update URI" is not available. Please add "Update URI" field on plugin file header.';
				wp_trigger_error( __METHOD__, $message );

				return;
			}

			if ( ! isset( $plugin_data['Tested up to'] ) ) {
				$message = 'Plugin "Tested up to" is not available. Please add "Tested up to" field on plugin file header.';
				wp_trigger_error( __METHOD__, $message );

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
		 * Plugin Rollback.
		 *
		 * @return void
		 */
		public function rollback_init(): void {
			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			if ( ! function_exists( 'get_plugin_data' ) ) {
				return;
			}

			// Rollback.
			new Rollback( $this->get_plugin_file(), $this->localize_strings() );
		}

		/**
		 * Absolute Plugin File.
		 *
		 * @return string
		 */
		abstract public function plugin_file(): string;

		/**
		 * License Key.
		 *
		 * @return string
		 */
		abstract public function license_key(): string;

		/**
		 * Translatable Strings.
		 *
		 * @abstract
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
		 */
		public function localize_strings(): array {

			/* translators: %s: Method name. */
			$message = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __METHOD__ );
			wp_trigger_error( __METHOD__, $message );

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
		 * Product ID for update server.
		 *
		 * @return int
		 */
		abstract public function product_id(): int;

		/**
		 * Get Provided Plugin Data.
		 *
		 * @return array<string, mixed>
		 */
		public function get_plugin_data(): array {

			if ( array_key_exists( 'Name', $this->plugin_data ) ) {
				return $this->plugin_data;
			}

			$this->plugin_data = get_plugin_data( $this->get_plugin_file() );

			return $this->plugin_data;
		}

		/**
		 * Get license key.
		 *
		 * @return string
		 */
		public function get_license_key(): string {
			return $this->license_key();
		}

		/**
		 * Get Product ID.
		 *
		 * @return int
		 */
		public function get_product_id(): int {
			return $this->product_id();
		}

		/**
		 * Add additional request for Updater Rest API.
		 *
		 * @return array<string, string>
		 */
		public function additional_request_args(): array {
			return array();
		}

		/**
		 * Get Client Host name.
		 *
		 * @return string
		 */
		public function get_client_hostname(): string {
			return wp_parse_url( sanitize_url( site_url() ), PHP_URL_HOST );
		}

		/**
		 * Get plugin update server hostname.
		 *
		 * @return string
		 */
		final public function get_update_server_hostname(): string {
			$data                   = $this->get_plugin_data();
			$update_server_hostname = untrailingslashit( $data['UpdateURI'] );

			return (string) wp_parse_url( sanitize_url( $update_server_hostname ), PHP_URL_HOST );
		}

		/**
		 * Get Update Server API URL.
		 *
		 * @return string
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
		 * Update Server API link without host name.
		 *
		 * @return string
		 * @example /wp-json/__NAMESPACE__/v1/check-update
		 */
		abstract public function update_server_path(): string;

		/**
		 * Removes leading forward slashes and backslashes if they exist.
		 *
		 * @param string $value Value from which trailing slashes will be removed.
		 *
		 * @return string String without the heading slashes.
		 */
		public function unleadingslashit( string $value ): string {
			return ltrim( $value, '/\\' );
		}

		/**
		 * Appends a leading slash on a string.
		 *
		 * @param string $value Value to which trailing slash will be added.
		 *
		 * @return string String with trailing slash added.
		 */
		public function leadingslashit( string $value ): string {
			return '/' . $this->unleadingslashit( $value );
		}

		/**
		 * Get Updater Server API link.
		 *
		 * @return string
		 */
		public function get_update_server_path(): string {
			return $this->leadingslashit( $this->update_server_path() );
		}

		/**
		 * Check plugin update forcefully.
		 *
		 * @return void
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
		 * Get check update action id.
		 *
		 * @return string
		 */
		private function get_action_id(): string {
			return sprintf( '%s_check_update', $this->get_plugin_slug() );
		}

		/**
		 * Check for update link.
		 *
		 * @param string[] $plugin_meta  An array of the plugin's metadata, including
		 *                               the version, author, author URI, and plugin URI.
		 * @param string   $plugin_file  Path to the plugin file relative to the plugins directory.
		 *
		 * @return array<string, string>
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
		 * Add tested upto support on plugin header.
		 *
		 * @param string[] $headers Available plugin header info.
		 *
		 * @return string[]
		 */
		public function add_tested_upto_info( array $headers ): array {
			return array_merge( $headers, array( 'Tested up to' ) );
		}

		/**
		 * Add Plugin banners.
		 *
		 * @return array<string, string>
		 * @example [ 'high' => '', 'low' => '' ]
		 */
		public function plugin_banners(): array {

			$plugin_dir_url = untrailingslashit( plugin_dir_url( $this->get_plugin_file() ) );
			$low            = $plugin_dir_url . '/vendor/storepress/admin-utils/images/banner.svg';

			return array(
				'low' => $low,
			);
		}

		/**
		 * Get Plugin Banners.
		 *
		 * @return array<string, string>
		 * @example [ 'high' => '', 'low' => '' ]
		 */
		public function get_plugin_banners(): array {
			return $this->plugin_banners();
		}

		/**
		 * Add Plugin Icons.
		 *
		 * @return array<string, string>
		 * @example [ '2x'  => '', '1x'  => '', 'svg' => '' ]
		 */
		public function plugin_icons(): array {

			$plugin_dir_url = untrailingslashit( plugin_dir_url( $this->get_plugin_file() ) );
			$image          = $plugin_dir_url . '/vendor/storepress/admin-utils/images/icon.svg';

			return array(
				'svg' => $image,
			);
		}

		/**
		 * Get Plugin Icons.
		 *
		 * @return array<string, string>
		 * @example [ '2x'  => '', '1x'  => '', 'svg' => '' ]
		 */
		public function get_plugin_icons(): array {
			return $this->plugin_icons();
		}

		/**
		 * Add plugin description section.
		 *
		 * @return string
		 */
		public function get_plugin_description_section(): string {
			return '';
		}

		/**
		 * Get request argument for request.
		 *
		 * @return array<string, mixed>
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
		 * Remote plugin data.
		 *
		 * @return array<string, string>
		 */
		public function get_remote_plugin_data(): array {
			$params = $this->get_request_args();

			// DO NOT USE SAME SERVER AS UPDATE RESPONSE SERVER AND UPDATE REQUEST CLIENT.
			$raw_response = wp_remote_get( $this->get_update_server_uri(), $params ); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get

			if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
				return array();
			}

			return json_decode( wp_remote_retrieve_body( $raw_response ), true );
		}

		/**
		 * Update check.
		 *
		 * @param bool|array<string, mixed> $update The plugin update data with the latest details.
		 * @param array<string, mixed>      $_plugin_data Plugin headers.
		 * @param string                    $plugin_file Plugin filename.
		 *
		 * @return bool|array<string, mixed>
		 * @see:     WP_Site_Health::detect_plugin_theme_auto_update_issues()
		 * @see:     function: wp_update_plugins() file: wp-includes/update.php
		 * @example https://example.com/updater-api/wp-json/plugin-updater/v1/check-update
		 * @see: POST http://api.wordpress.org/plugins/update-check/1.1/
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
		 * Plugin Info screenshot html.
		 *
		 * @param array<string, array<string, string>> $screenshots Screenshot array.
		 *
		 * @return string
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
		 * Prepare Remote data to use.
		 *
		 * @param bool|array<string, mixed> $remote_data Remote data.
		 *
		 * @return array<string, mixed>
		 * @see: GET https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=woocommerce
		 * @see: GET https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=woocommerce
		 * @see: POST http://api.wordpress.org/plugins/update-check/1.1/
		 * @example
		 * array [
		 *
		 *     'description'=>'',
		 *
		 *     'installation'=>'',
		 *
		 *     'changelog'=>'',
		 *
		 *     'faq'=>'',
		 *
		 *     'new_version'=>'x.x.x', // * REQUIRED
		 *
		 *     'package'=>'http://updater.com/plugin-latest.zip', // * REQUIRED ABSOLUTE URL OR EMPTY
		 *
		 *     'last_updated'=>'2024-11-11 3:24pm GMT+6',
		 *
		 *     'active_installs'=>'1000',
		 *
		 *     'upgrade_notice'=>'',
		 *
		 *     'screenshots'=>[['src'=>'', 'caption'=>'' ], ['src'=>'', 'caption'=>''], ['src'=>'', 'caption'=>'']],
		 *
		 *     'tested'=>'x.x.x', // WP testes Version
		 *
		 *     'requires'=>'x.x.x', // Minimum Required WP
		 *
		 *     'requires_php'=>'x.x.x', // Minimum Required PHP
		 *
		 *     'requires_plugins'=> ['woocommerce'], // Requires Plugins
		 *
		 *     'versions'=> [ 'trunk' => 'http://updater.com/plugin-latest.zip', '1.1.0'=> 'http://updater.com/plugin-1.1.0.zip' ], // Available versions
		 *
		 *     'preview_link'=>'', // Preview link
		 *
		 *     'banners'=>['low'=>'https://ps.w.org/marquee-block/assets/banner-772x250.png', 'high'=>'https://ps.w.org/marquee-block/assets/banner-1544x500.png'],
		 *
		 *     'banners_rtl'=>['low'=>'https://ps.w.org/marquee-block/assets/banner-772x250.png', 'high'=>'https://ps.w.org/marquee-block/assets/banner-1544x500.png'],
		 *
		 *     Using SVG Icon Recommended.
		 *
		 *     'icons'=>[ 'svg' => 'https://ps.w.org/woocommerce/assets/icon.svg', '2x'  => 'https://ps.w.org/woocommerce/assets/icon-256x256.png', '1x'  => 'https://ps.w.org/woocommerce/assets/icon-128x128.png' ], // icons.
		 *
		 *     'allow_rollback'=>'yes' , // yes | no. // * REQUIRED for ROLLBACK
		 *
		 * ]
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
		 * Plugin Information.
		 *
		 * @param false|object|array<string, mixed> $result The result object or array. Default false.
		 * @param string                            $action The type of information being requested from the Plugin Installation API.
		 * @param object                            $args   Plugin API arguments.
		 *
		 * @return false|array<string, mixed>|object
		 * @see:     function: plugins_api() file: wp-admin/includes/plugin-install.php
		 * @example GET https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=hello-dolly
		 * @example GET https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=hello-dolly
		 * @example /wp-includes/update.php#460
		 * @example /wp-admin/includes/class-wp-plugins-list-table.php#200
		 *
		 * @example https://developer.wordpress.org/reference/functions/plugins_api/
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
		 * Plugin Update Message.
		 *
		 * @param array<string, string> $plugin_data An array of plugin metadata.
		 *
		 * @return void
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
