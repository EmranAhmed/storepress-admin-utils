<?php
	/**
	 * Plugin Upgrade Notice Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Upgrade_Notice' ) ) {
	/**
	 * Plugin Upgrade Notice Class.
	 */
	abstract class Upgrade_Notice {

		/**
		 * Plugin Data.
		 *
		 * @var array<string, string|bool>
		 */
		private array $data = array();

		/**
		 * Relative Plugin file.
		 *
		 * @var string
		 */
		private string $plugin;

		/**
		 * Class construct.
		 */
		protected function __construct() {

			add_action( 'admin_init', array( $this, 'init' ), 9 );
			add_action( 'admin_init', array( $this, 'deactivate' ), 12 );
		}

		/**
		 * Get absolute file path.
		 *
		 * @param string $plugin_file relative or absolute path.
		 *
		 * @return string
		 */
		public function get_absolute_plugin_file( string $plugin_file ): string {
			$file   = wp_normalize_path( $plugin_file );
			$plugin = plugin_basename( $file );

			return trailingslashit( WP_PLUGIN_DIR ) . $plugin;
		}

		/**
		 * Init Plugin Info.
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

			$plugin_file = $this->get_absolute_plugin_file( $this->plugin_file() );

			if ( ! file_exists( $plugin_file ) ) {
				return;
			}

			$this->data   = get_plugin_data( $plugin_file );
			$this->plugin = plugin_basename( $plugin_file );

			if ( $this->is_compatible() ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'admin_notice' ), 12 );

			add_action( 'after_plugin_row_' . $this->plugin, array( $this, 'row_notice' ) );
		}

		/**
		 * Deactivate incompatible version.
		 *
		 * @return void
		 */
		public function deactivate() {

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			if ( ! function_exists( 'get_plugin_data' ) ) {
				return;
			}

			$plugin_file = $this->get_absolute_plugin_file( $this->plugin_file() );

			if ( ! file_exists( $plugin_file ) ) {
				return;
			}

			if ( $this->is_compatible() ) {
				return;
			}

			if ( ! $this->deactivate_incompatible() ) {
				return;
			}

			if ( is_plugin_inactive( $this->plugin ) ) {
				return;
			}

			// Deactivate the plugin silently, Prevent deactivation hooks from running.
			deactivate_plugins( $this->plugin, true );
		}

		/**
		 * Show notice while try to activate incompatible version of extended plugin.
		 *
		 * @return void
		 */
		public function admin_notice() {

			if ( ! $this->show_admin_notice() ) {
				return;
			}

			// Inactive plugin should not display any admin notice.
			if ( is_plugin_inactive( $this->plugin ) ) {
				return;
			}

			$message = $this->get_notice_content();
			printf( '<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error', wp_kses_post( $message ) );
		}

		/**
		 * Show notice on plugin row.
		 *
		 * @return void
		 */
		public function row_notice() {
			global $wp_list_table;

			if ( ! $this->show_plugin_row_notice() ) {
				return;
			}

			$columns_count = $wp_list_table->get_column_count();
			$update_notice = $this->get_notice_content();
			?>
				<tr class="plugin-update-tr update">
					<td class="plugin-update" colspan="<?php echo absint( $columns_count ); ?>">
						<div class="notice inline notice-warning notice-alt"><p><?php echo wp_kses_post( $update_notice ); ?></p></div>
					</td>
				</tr>
				<?php
		}

		/**
		 * Show Notice on plugin row.
		 *
		 * @return bool
		 */
		public function show_plugin_row_notice(): bool {
			return true;
		}

		/**
		 * Show admin notice.
		 *
		 * @return bool
		 */
		public function show_admin_notice(): bool {
			return true;
		}

		/**
		 * Should deactivate incompatible version.
		 *
		 * @return bool
		 */
		public function deactivate_incompatible(): bool {
			return false;
		}

		/**
		 * Get plugin absolute or relative file.
		 *
		 * @return string
		 */
		abstract public function plugin_file(): string;

		/**
		 * Get required version of Plugin.
		 *
		 * @return string
		 */
		abstract public function compatible_version(): string;

		/**
		 * Check is using compatible version.
		 *
		 * @return bool
		 */
		private function is_compatible(): bool {
			$current_version  = sanitize_text_field( (string) $this->data['Version'] );
			$required_version = $this->compatible_version();

			return version_compare( $current_version, $required_version, '>=' );
		}

		/**
		 * Notice string.
		 *
		 * @return string
		 */
		public function get_notice_content(): string {

			$name               = sanitize_text_field( (string) $this->data['Name'] );
			$version            = sanitize_text_field( (string) $this->data['Version'] );
			$compatible_version = $this->compatible_version();

			return sprintf( $this->localize_notice_format(), $name, $version, $compatible_version );
		}

		/**
		 * Notice string format.
		 *
		 * @abstract
		 * @return string
		 */
		public function localize_notice_format(): string {

			/* translators: %s: Method name. */
			$message = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __METHOD__ );
			wp_trigger_error( __METHOD__, $message );

			// translators: 1: Extended Plugin Name. 2: Extended Plugin Version. 3: Extended Plugin Compatible Version.
			return 'You are using an incompatible version of <strong>%1$s - (%2$s)</strong>. Please upgrade to version <strong>%3$s</strong> or upper.';
		}
	}
}
