<?php
	/**
	 * Upgrade Notice
	 *
	 * @package    StorePress/AdminUtils
	 * @version    1.0
	 */

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Upgrade_Notice' ) ) {
	/**
	 * Upgrade Notice
	 */
	abstract class Upgrade_Notice {

		/**
		 * Plugin Data.
		 *
		 * @var array
		 */
		private array $plugin_data = array();

		/**
		 * Upgrade notice.
		 */
		protected function __construct() {

			add_action( 'admin_init', array( $this, 'init' ), 9 );
		}

		/**
		 * Init Plugin Information.
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

			$this->plugin_data = get_plugin_data( $this->plugin_file() );

			$plugin_basename = plugin_basename( $this->plugin_file() );

			if ( $this->is_compatible() ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'admin_notice' ), 11 );

			add_action( 'after_plugin_row_' . $plugin_basename, array( $this, 'row_notice' ) );
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
		 * @return true
		 */
		public function show_plugin_row_notice(): bool {
			return true;
		}

		/**
		 * Show admin notice.
		 *
		 * @return true
		 */
		public function show_admin_notice(): bool {
			return true;
		}

		/**
		 * Get Pro plugin file.
		 *
		 * @return string
		 */
		abstract public function plugin_file(): string;

		/**
		 * Get required version of Extended Plugin.
		 *
		 * @return string
		 */
		abstract public function compatible_version(): string;

		/**
		 * Check is using compatible version.
		 *
		 * @return bool
		 */
		public function is_compatible(): bool {
			$current_version  = sanitize_text_field( $this->plugin_data['Version'] );
			$required_version = $this->compatible_version();

			return version_compare( $current_version, $required_version ) >= 0;
		}

		/**
		 * Notice string.
		 *
		 * @return string
		 */
		public function get_notice_content(): string {

			$plugin_name        = sanitize_text_field( $this->plugin_data['Name'] );
			$plugin_version     = sanitize_text_field( $this->plugin_data['Version'] );
			$compatible_version = $this->compatible_version();

			return sprintf( $this->localize_notice_format(), $plugin_name, $plugin_version, $compatible_version );
		}

		/**
		 * Notice string format.
		 *
		 * @return string
		 */
		public function localize_notice_format(): string {

			$message = esc_html__( 'not implemented. Must be overridden in subclass.' );
			$this->trigger_error( __METHOD__, $message );

			// translators: 1: Extended Plugin Name. 2: Extended Plugin Version. 3: Extended Plugin Compatible Version.
			return 'You are using an incompatible version of <strong>%1$s - (%2$s)</strong>. Please upgrade to version <strong>%3$s</strong> or upper.';
		}

		/**
		 * Trigger user error.
		 *
		 * @param string $function_name Function name.
		 * @param string $message       Message.
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

				trigger_error( $message ); // phpcs:ignore.
			}
		}
	}
}