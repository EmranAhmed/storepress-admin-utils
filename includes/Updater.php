<?php
	/**
	 * Plugin Updater API
	 *
	 * @package    StorePress/AdminUtils
	 * @version    1.0
	 */

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Updater' ) ) {

	/**
	 * Plugin Updater API
	 *
	 * @name Updater
	 */
	abstract class Updater {

		/**
		 * Plugin Data.
		 *
		 * @var array
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

			if ( empty( $plugin_data['UpdateURI'] ) ) {
				$msg = 'Plugin "Update URI" is not available. Please add "Update URI" field on plugin file header.';
				$this->trigger_error( __METHOD__, $msg );

				return;
			}

			if ( empty( $plugin_data['Tested up to'] ) ) {
				$msg = 'Plugin "Tested up to" is not available. Please add "Tested up to" field on plugin file header.';
				$this->trigger_error( __METHOD__, $msg );

				return;
			}

			$plugin_id       = $this->get_plugin_slug();
			$plugin_hostname = $this->get_update_server_hostname();
			$action_id       = $this->get_action_id();

			// Plugin Popup Information When People Click On: View Details or View version x.x.x details link.
			add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );

			// Check plugin update information from server.
			add_filter( "update_plugins_{$plugin_hostname}", array( $this, 'update_check' ), 10, 4 );

			// Add some info at the end of plugin update notice like: notice to update license data.
			add_action( "in_plugin_update_message-{$plugin_id}", array( $this, 'update_message' ), 10, 2 );

			// Add force update check link.
			add_filter( 'plugin_row_meta', array( $this, 'check_for_update_link' ), 10, 2 );

			// Run force update check action.
			add_action( "admin_action_{$action_id}", array( $this, 'force_update_check' ) );
		}

		abstract public function plugin_file(): string;

		abstract public function license_key(): string;

		abstract public function license_key_empty_message(): string;

		abstract public function check_update_link_text(): string;

		abstract public function product_id(): string;

		final private function get_plugin_data(): array {

			if ( ! empty( $this->plugin_data ) ) {
				return $this->plugin_data;
			}

			$this->plugin_data = get_plugin_data( $this->get_plugin_file() );

			return $this->plugin_data;
		}

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

		public function get_license_key(): string {
			return $this->license_key();
		}

		public function get_product_id(): string {
			return $this->product_id();
		}

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

			return wp_parse_url( sanitize_url( $update_server_hostname ), PHP_URL_HOST );
		}

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

		final public function force_update_check() {
			if ( current_user_can( 'update_plugins' ) ) {
				if ( ! function_exists( 'wp_clean_plugins_cache' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				check_admin_referer( $this->get_plugin_slug() );

				wp_clean_plugins_cache();

				wp_safe_redirect( admin_url( 'plugins.php' ) );
			}
		}

		private function get_action_id(): string {
			return sprintf( '%s_check_update', $this->get_plugin_dirname() );
		}

		public function check_for_update_link( $links, $file ): array {

			if ( $file === $this->get_plugin_slug() && current_user_can( 'update_plugins' ) ) {

				$id   = $this->get_action_id();
				$url  = wp_nonce_url( add_query_arg( array( 'action' => $id ), admin_url( 'plugins.php' ) ), $this->get_plugin_slug() );
				$text = $this->check_update_link_text();

				$row_meta[ $id ] = sprintf( '<a href="%1$s" aria-label="%2$s">%2$s</a>', esc_url( $url ), esc_html( $text ) );

				return array_merge( $links, $row_meta );
			}

			return $links;
		}

		public function add_tested_upto_info( $headers ): array {
			return array_merge( $headers, array( 'Tested up to' ) );
		}

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
		 * @return array [
		 * '2x' => '',
		 * '1x' => ''
		 * ]
		 */
		abstract public function plugin_banners(): array;

		/**
		 * Get Plugin Banners.
		 *
		 * @return array [
		 *  '2x' => '',
		 *  '1x' => ''
		 *  ]
		 */
		public function get_plugin_banners(): array {
			return $this->plugin_banners();
		}

		/**
		 * Add Plugin Icons.
		 *
		 * @return array [
		 * '2x'  => '',
		 * '1x'  => '',
		 * 'svg' => ''
		 * ] Plugin Icons array.
		 */
		abstract public function plugin_icons(): array;

		/**
		 * Get Plugin Icons.
		 *
		 * @return array [
		 * '2x'  => '',
		 * '1x'  => '',
		 * 'svg' => '',
		 * ]
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
		 * @return array
		 */
		protected function get_request_args(): array {
			return array(
				'timeout' => 10,
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
		 * @return false|string[]
		 */
		public function get_remote_plugin_data() {
			$params = $this->get_request_args();

			$raw_response = wp_remote_get( $this->get_update_server_uri(), $params );

			if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
				return false;
			}

			return json_decode( wp_remote_retrieve_body( $raw_response ), true );
		}

		/**
		 * @param          $update
		 * @param array    $plugin_data
		 * @param string   $plugin_file
		 * @param string[] $locales
		 *
		 * @return array|mixed
		 * @see     WP_Site_Health::detect_plugin_theme_auto_update_issues()
		 * @see     wp_update_plugins()
		 */
		final public function update_check( $update, array $plugin_data, string $plugin_file, $locales ) {

			if ( $plugin_file !== $this->get_plugin_slug() ) {
				return $update;
			}

			if ( ! empty( $update ) ) {
				return $update;
			}

			$remote_data = $this->get_remote_plugin_data();
			$plugin_data = $this->get_plugin_data();

			if ( empty( $remote_data ) ) {
				return $update;
			}

			$plugin_version = $plugin_data['Version'];
			$plugin_uri     = $plugin_data['PluginURI'];
			$plugin_tested  = $plugin_data['Tested up to'] ?? '';
			$requires_php   = $plugin_data['RequiresPHP'];

			/**
			 * @var string $plugin_id plugin unique id. Example: w.org/plugins/xyz-plugin.
			 */
			$plugin_id = url_shorten( $plugin_uri, 150 );

			$item = array(
				'id'            => $plugin_id, // w.org/plugins/xyz-plugin
				'slug'          => $this->get_plugin_dirname(), // xyz-plugin
				'plugin'        => $this->get_plugin_slug(), // xyz-plugin/xyz-plugin.php
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
		 * @param array|false $remote_data
		 *
		 * @return array [
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

			if ( empty( $remote_data ) ) {
				return $item;
			}

			if ( isset( $remote_data['description'] ) ) {
				$item['sections']['description'] = wp_kses_post( $remote_data['description'] );
			}

			if ( isset( $remote_data['changelog'] ) ) {
				$item['sections']['changelog'] = wp_kses_post( $remote_data['changelog'] );
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
		 * @param $default
		 * @param $action
		 * @param $args
		 *
		 * @return array|mixed
		 * @see     plugins_api()
		 * @example https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=hello-dolly
		 */
		final public function plugin_information( $default, $action, $args ) {

			if ( ! ( 'plugin_information' === $action ) ) {
				return $default;
			}

			if ( isset( $args->slug ) && $args->slug === $this->get_plugin_dirname() ) {

				$plugin_data        = $this->get_plugin_data();
				$plugin_name        = $plugin_data['Name'];
				$plugin_description = $plugin_data['Description'];
				$plugin_homepage    = $plugin_data['PluginURI'];
				$author             = $plugin_data['Author'];
				$version            = $plugin_data['Version'];

				$get_description = $this->get_plugin_description_section();
				$get_changelog   = $this->get_plugin_changelog_section();
				$description     = empty( $get_description ) ? wpautop( $plugin_description ) : $get_description;

				$item = array(
					'name'     => $plugin_name,
					'version'  => $version,
					'slug'     => $this->get_plugin_dirname(),
					'banners'  => $this->get_plugin_info_banners(),
					'author'   => $author,
					'homepage' => esc_url( $plugin_homepage ),
					'sections' => array(
						'description' => wp_kses_post( $description ),
					),
				);

				if ( ! empty( $get_changelog ) ) {
					$item['sections']['changelog'] = $get_changelog;
				}

				$remote_data = $this->get_remote_plugin_data();

				$remote_item = $this->prepare_remote_data( $remote_data );

				$data = wp_parse_args( $remote_item, $item );

				return (object) $data;
			}

			return $default;
		}

		public function update_message( $plugin_data, $response ) {

			$license_key    = trim( $this->get_license_key() );
			$upgrade_notice = $plugin_data['upgrade_notice'] ?? false;

			if ( empty( $license_key ) ) {
				printf( ' <strong>%s</strong>', esc_html( $this->license_key_empty_message() ) );
			}

			if ( $upgrade_notice ) {
				printf( ' <br /><br /><strong><em>%s</em></strong>', esc_html( $upgrade_notice ) );
			}
		}

		/**
		 * @param string $function_name
		 * @param string $message
		 *
		 * @return void
		 */
		final public function trigger_error( string $function_name, string $message ) {

			// Bail out if WP_DEBUG is not turned on.
			if ( ! WP_DEBUG ) {
				return;
			}

			if ( function_exists( 'wp_trigger_error' ) ) {
				wp_trigger_error( $function_name, $message );
			} else {

				if ( ! empty( $function_name ) ) {
					$message = sprintf( '%s(): %s', $function_name, $message );
				}

				$message = wp_kses(
					$message,
					array(
						'a' => array( 'href' ),
						'br',
						'code',
						'em',
						'strong',
					),
					array( 'http', 'https' )
				);

				trigger_error( $message );
			}
		}
	}
}
