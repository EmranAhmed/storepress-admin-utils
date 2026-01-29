<?php
	/**
	 * Abstract Plugin Upgrade Notice File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;
	use StorePress\AdminUtils\Traits\PluginCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractProPluginInCompatibility' ) ) {

	/**
	 * Abstract Plugin Upgrade Notice Class.
	 *
	 * @name AbstractProPluginInCompatibility
	 */
	abstract class AbstractProPluginInCompatibility {

		use HelperMethodsTrait;
		use PluginCommonTrait;
		use CallerTrait;
		use MethodShouldImplementTrait;

		/**
		 * Plugin Data.
		 *
		 * @var array<string, mixed>
		 */
		protected array $plugin_data = array();

		/**
		 * Updater Plugin Admin Init.
		 *
		 * @param object $caller Caller Object.
		 */
		public function __construct( object $caller ) {
			$this->set_caller( $caller );
			$this->hooks();
			$this->init();
		}

		/**
		 * Init method
		 *
		 * @return void
		 */
		public function init(): void {}

		/**
		 * Register WordPress hooks.
		 *
		 * @return void
		 */
		public function hooks(): void {
			add_action( 'admin_init', array( $this, 'loaded' ), 9 );
			add_action( 'admin_init', array( $this, 'deactivate' ), 12 );
		}

		/**
		 * Get required version of Plugin.
		 *
		 * @return string
		 */
		abstract public function compatible_version(): string;

		/**
		 * Check if current user has capability to update plugins.
		 *
		 * @return bool
		 */
		public function has_capability(): bool {
			return current_user_can( 'update_plugins' );
		}

		/**
		 * Init Hook.
		 *
		 * @return void
		 */
		public function loaded(): void {

			if ( ! $this->has_capability() ) {
				return;
			}

			$plugin_file = $this->get_plugin_file();

			if ( ! file_exists( $plugin_file ) ) {
				return;
			}

			if ( $this->is_compatible() ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'admin_notice' ), 12 );

			add_action( 'after_plugin_row_' . $this->get_plugin_basename(), array( $this, 'row_notice' ) );
		}

		/**
		 * Deactivate plugin.
		 *
		 * @return void
		 */
		public function deactivate(): void {

			if ( ! $this->has_capability() ) {
				return;
			}

			$plugin_file = $this->get_plugin_file();

			if ( ! file_exists( $plugin_file ) ) {
				return;
			}

			if ( $this->is_compatible() ) {
				return;
			}

			if ( ! $this->deactivate_incompatible() ) {
				return;
			}

			if ( is_plugin_inactive( $this->get_plugin_basename() ) ) {
				return;
			}

			// Deactivate the plugin silently, Prevent deactivation hooks from running.
			deactivate_plugins( $this->get_plugin_basename(), true );
		}

		/**
		 * Show notice while try to activate incompatible version of extended plugin.
		 *
		 * @return void
		 * @throws \WP_Exception Exception If "localize_notice_format" is not overridden in subclass.
		 */
		public function admin_notice(): void {

			if ( ! $this->show_admin_notice() ) {
				return;
			}

			// Inactive plugin should not display any admin notice.
			if ( is_plugin_inactive( $this->get_plugin_basename() ) ) {
				return;
			}

			$message = $this->get_notice_content();
			printf( '<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error', wp_kses_post( $message ) );
		}

		/**
		 * Show notice on plugin row.
		 *
		 * @return void
		 * @throws \WP_Exception Exception If "localize_notice_format" is not overridden in subclass.
		 */
		public function row_notice(): void {
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
		 * Check is using compatible version.
		 *
		 * @return bool
		 */
		private function is_compatible(): bool {
			$current_version  = $this->get_plugin_version();
			$required_version = $this->compatible_version();

			return version_compare( $current_version, $required_version, '>=' );
		}

		/**
		 * Notice string.
		 *
		 * @return string
		 * @throws \WP_Exception Throw Exception If "localize_notice_format" is not overridden in subclass.
		 */
		public function get_notice_content(): string {

			$name               = $this->get_plugin_name();
			$version            = $this->get_plugin_version();
			$compatible_version = $this->compatible_version();

			return sprintf( $this->localize_notice_format(), $name, $version, $compatible_version );
		}

		/**
		 * Notice string format.
		 *
		 * @abstract Must be overridden in subclass.
		 * @return string
		 * @throws \WP_Exception This method should be overridden in subclass.
		 */
		public function localize_notice_format(): string {

			$this->subclass_should_implement( __FUNCTION__ );

			// translators: 1: Extended Plugin Name. 2: Extended Plugin Version. 3: Extended Plugin Compatible Version.
			return 'You are using an incompatible version of <strong>%1$s - (%2$s)</strong>. Please upgrade to version <strong>%3$s</strong> or upper.';
		}

		/**
		 * Pro or dependent plugin file.
		 *
		 * @return string
		 */
		abstract public function pro_plugin_file(): string;

		/**
		 * Pro plugin file.
		 *
		 * @abstract Must be overridden in subclass.
		 * @return string
		 * @throws \WP_Exception This method should be overridden in subclass.
		 */
		public function plugin_file(): string {
			return $this->pro_plugin_file();
		}
	}
}
