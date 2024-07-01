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
			add_action( 'admin_init', array( $this, 'init' ) );
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

			// Add extra plugin header to display WP Tested Upto Info.
			add_filter( 'extra_plugin_headers', array( $this, 'add_tested_upto_info' ) );

			$plugin_data = $this->get_plugin_data();

			if ( ! isset( $plugin_data['UpdateURI'] ) ) {
				$msg = 'Plugin "Update URI" is not available. Please add "Update URI" field on plugin file header.';
				wp_trigger_error( __METHOD__, $msg );

				return;
			}

			if ( ! isset( $plugin_data['Tested up to'] ) ) {
				$msg = 'Plugin "Tested up to" is not available. Please add "Tested up to" field on plugin file header.';
				wp_trigger_error( __METHOD__, $msg );

				return;
			}

			$plugin_id       = $this->get_plugin_slug();
			$plugin_hostname = $this->get_update_server_hostname();
			$action_id       = $this->get_action_id();

			// Plugin Popup Information When People Click On: View Details or View version x.x.x details link.
			add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );

			// Check plugin update information from server.
			add_filter( "update_plugins_{$plugin_hostname}", array( $this, 'update_check' ), 10, 3 );

			// Add some info at the end of plugin update notice like: notice to update license data.
			add_action( "in_plugin_update_message-{$plugin_id}", array( $this, 'update_message' ) );

			// Add force update check link.
			add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );

			// Run force update check action.
			add_action( "admin_action_{$action_id}", array( $this, 'force_update_check' ) );
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
		 * License key empty message text.
		 *
		 * @return string
		 */
		abstract public function license_key_empty_message(): string;

		/**
		 * Check update link text.
		 *
		 * @return string
		 */
		abstract public function check_update_link_text(): string;

		/**
		 * Product ID for update server.
		 *
		 * @return string
		 */
		abstract public function product_id(): string;

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
		 * Get Plugin absolute file
		 *
		 * @return string
		 */
		public function get_plugin_file(): string {
			return $this->plugin_file();
		}

		/**
		 * Plugin Directory Name Only
		 *
		 * @return string
		 * @example xyz-plugin
		 */
		public function get_plugin_dirname(): string {
			return wp_basename( dirname( $this->get_plugin_file() ) );
		}

		/**
		 * Plugin Slug Like "plugin-directory/plugin-file.php"
		 *
		 * @return string
		 * @example xyz-plugin/xyz-plugin.php
		 */
		public function get_plugin_slug(): string {
			return plugin_basename( $this->get_plugin_file() );
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
		 * @return string
		 */
		public function get_product_id(): string {
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
		 * Get Update server uri.
		 *
		 * @return string
		 */
		final public function get_update_server_uri(): string {

			$data                   = $this->get_plugin_data();
			$update_server_hostname = untrailingslashit( $data['UpdateURI'] );

			$scheme = wp_parse_url( sanitize_url( $update_server_hostname ), PHP_URL_SCHEME );
			$host   = $this->get_update_server_hostname();
			$path   = $this->get_update_server_path();

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
		final public function force_update_check() {
			if ( current_user_can( 'update_plugins' ) ) {
				if ( ! function_exists( 'wp_clean_plugins_cache' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				check_admin_referer( $this->get_plugin_slug() );

				wp_clean_plugins_cache();

				wp_safe_redirect( admin_url( 'plugins.php' ) );
				exit;
			}
		}

		/**
		 * Get check update action id.
		 *
		 * @return string
		 */
		private function get_action_id(): string {
			return sprintf( '%s_check_update', $this->get_plugin_dirname() );
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

			if ( $plugin_file === $this->get_plugin_slug() && current_user_can( 'update_plugins' ) ) {

				$id       = $this->get_action_id();
				$url      = wp_nonce_url( add_query_arg( array( 'action' => $id ), admin_url( 'plugins.php' ) ), $this->get_plugin_slug() );
				$text     = $this->check_update_link_text();
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
		 * Get Plugin Banners.
		 *
		 * @return array<string, string>
		 * @example [ 'high' => '', 'low' => '' ]
		 */
		public function get_plugin_info_banners(): array {

			$banners = $this->get_plugin_banners();

			return array(
				'high' => esc_url( $banners['2x'] ),
				'low'  => esc_url( $banners['1x'] ),
			);
		}

		/**
		 * Add Plugin banners.
		 *
		 * @return array<string, string>
		 * @example [ '2x' => '', '1x' => '' ]
		 */
		abstract public function plugin_banners(): array;

		/**
		 * Get Plugin Banners.
		 *
		 * @return array<string, string>
		 * @example [ '2x' => '', '1x' => '' ]
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
		abstract public function plugin_icons(): array;

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
		 * Add plugin changelog section.
		 *
		 * @return string
		 */
		public function get_plugin_changelog_section(): string {
			return '';
		}

		/**
		 * Get request argument for request.
		 *
		 * @return array<string, mixed>
		 */
		protected function get_request_args(): array {
			return array(
				'body'    => array(
					'type'        => 'plugins',
					'name'        => $this->get_plugin_slug(),
					'license_key' => $this->get_license_key(),
					'product_id'  => $this->get_product_id(),
					'args'        => $this->additional_request_args(),
				),
				'headers' => array(
					'Accept' => 'application/json',
				),
			);
		}

		/**
		 * Remote plugin data.
		 *
		 * @return bool|array<string, string>
		 */
		public function get_remote_plugin_data() {
			$params = $this->get_request_args();

			$raw_response = wp_safe_remote_get( $this->get_update_server_uri(), $params );

			if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
				return false;
			}

			return json_decode( wp_remote_retrieve_body( $raw_response ), true );
		}

		/**
		 * Update check.
		 *
		 * @param bool|array<string, mixed> $update The plugin update data with the latest details.
		 * @param array<string, mixed>      $plugin_data Plugin headers.
		 * @param string                    $plugin_file Plugin filename.
		 *
		 * @return bool|array<string, mixed>
		 * @see     WP_Site_Health::detect_plugin_theme_auto_update_issues()
		 * @see     wp_update_plugins()
		 */
		final public function update_check( $update, array $plugin_data, string $plugin_file ) {

			if ( $plugin_file !== $this->get_plugin_slug() ) {
				return $update;
			}

			if ( is_array( $update ) ) {
				return $update;
			}

			$remote_data = $this->get_remote_plugin_data();
			$plugin_data = $this->get_plugin_data();

			if ( false === $remote_data ) {
				return $update;
			}

			$plugin_version = $plugin_data['Version'];
			$plugin_uri     = $plugin_data['PluginURI'];
			$plugin_tested  = $plugin_data['Tested up to'] ?? '';
			$requires_php   = $plugin_data['RequiresPHP'];

			$plugin_id = url_shorten( (string) $plugin_uri, 150 );

			$item = array(
				'id'            => $plugin_id, // @example: w.org/plugins/xyz-plugin
				'slug'          => $this->get_plugin_dirname(), // @example: xyz-plugin
				'plugin'        => $this->get_plugin_slug(), // @example: xyz-plugin/xyz-plugin.php
				'version'       => $plugin_version,
				'url'           => $plugin_uri,
				'icons'         => $this->get_plugin_icons(),
				'banners'       => $this->get_plugin_banners(),
				'banners_rtl'   => array(),
				'compatibility' => new \stdClass(),
				'tested'        => $plugin_tested,
				'requires_php'  => $requires_php,
			);

			$remote_item = $this->prepare_remote_data( $remote_data );

			return wp_parse_args( $remote_item, $item );
		}

		/**
		 * Prepare Remote data to use.
		 *
		 * @param bool|array<string, mixed> $remote_data Remote data.
		 *
		 * @return array<string, mixed>
		 * @example
		 * array [
		 *
		 *     'description'=>'',
		 *
		 *     'changelog'=>'',
		 *
		 *     'version'=>'x.x.x',
		 *
		 *      OR
		 *
		 *     'new_version'=>'x.x.x',
		 *
		 *     'last_updated'=>'2023-11-11 3:24pm GMT+6',
		 *
		 *     'upgrade_notice'=>'',
		 *
		 *     'download_link'=>'plugin.zip',
		 *
		 *      OR
		 *
		 *     'package'=>'plugin.zip',
		 *
		 *     'tested'=>'x.x.x', // WP testes Version
		 *
		 *     'requires'=>'x.x.x', // Minimum Required WP
		 *
		 *     'requires_php'=>'x.x.x', // Minimum Required PHP
		 *
		 * ]
		 */
		public function prepare_remote_data( $remote_data ): array {
			$item = array();

			if ( ( is_bool( $remote_data ) && false === $remote_data ) || ( is_array( $remote_data ) && $this->is_empty_array( $remote_data ) ) ) {
				return $item;
			}

			if ( isset( $remote_data['description'] ) ) {
				$item['sections']['description'] = $remote_data['description'];
			}

			if ( isset( $remote_data['changelog'] ) ) {
				$item['sections']['changelog'] = $remote_data['changelog'];
			}

			if ( isset( $remote_data['version'] ) ) {
				$item['version'] = $remote_data['version'];
			}

			if ( isset( $remote_data['new_version'] ) ) {
				$item['version'] = $remote_data['new_version'];
			}

			if ( isset( $remote_data['last_updated'] ) ) {
				$item['last_updated'] = $remote_data['last_updated'];
			}

			if ( isset( $remote_data['upgrade_notice'] ) ) {
				$item['upgrade_notice'] = $remote_data['upgrade_notice'];
			}

			if ( isset( $remote_data['download_link'] ) ) {
				$item['download_link'] = $remote_data['download_link'];
			}

			if ( isset( $remote_data['package'] ) ) {
				$item['download_link'] = $remote_data['package'];
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
		 * @see     plugins_api()
		 * @example https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=hello-dolly
		 */
		final public function plugin_information( $result, string $action, object $args ) {

			if ( ! ( 'plugin_information' === $action ) ) {
				return $result;
			}

			if ( isset( $args->slug ) && $args->slug === $this->get_plugin_dirname() ) {

				$plugin_data        = $this->get_plugin_data();
				$plugin_name        = $plugin_data['Name'];
				$plugin_description = $plugin_data['Description'];
				$plugin_homepage    = $plugin_data['PluginURI'];
				$author             = $plugin_data['Author'];
				$version            = $plugin_data['Version'];

				$get_description = trim( $this->get_plugin_description_section() );
				$get_changelog   = trim( $this->get_plugin_changelog_section() );
				$description     = '' === $get_description ? $plugin_description : $get_description;

				$item = array(
					'name'     => $plugin_name,
					'version'  => $version,
					'slug'     => $this->get_plugin_dirname(),
					'banners'  => $this->get_plugin_info_banners(),
					'author'   => $author,
					'homepage' => $plugin_homepage,
					'sections' => array(
						'description' => $description,
					),
				);

				if ( strlen( $get_changelog ) > 0 ) {
					$item['sections']['changelog'] = $get_changelog;
				}

				$remote_data = $this->get_remote_plugin_data();

				$remote_item = $this->prepare_remote_data( $remote_data );

				$data = wp_parse_args( $remote_item, $item );

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
		public function update_message( array $plugin_data ) {

			$license_key    = $this->get_license_key();
			$upgrade_notice = $plugin_data['upgrade_notice'] ?? '';

			if ( $this->is_empty_string( $license_key ) ) {
				printf( ' <strong>%s</strong>', esc_html( $this->license_key_empty_message() ) );
			}

			if ( ! $this->is_empty_string( $upgrade_notice ) ) {
				printf( ' <br /><br /><strong><em>%s</em></strong>', esc_html( $upgrade_notice ) );
			}
		}
	}
}
