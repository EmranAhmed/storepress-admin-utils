<?php
	/**
	 * Abstract Pro Plugin Incompatibility Class File.
	 *
	 * Provides a base implementation for handling version incompatibilities between
	 * a free/base plugin and its pro/extended version. This class displays admin notices,
	 * plugin row warnings, and can optionally deactivate incompatible plugin versions.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    3.1.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;
	use StorePress\AdminUtils\Traits\PluginCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractProPluginInCompatibility' ) ) {

	/**
	 * Abstract Pro Plugin Incompatibility Class.
	 *
	 * This abstract class provides functionality for detecting and handling version
	 * incompatibilities between a base plugin and its pro/extended version. It displays
	 * admin notices, plugin row warnings, and can optionally deactivate the incompatible
	 * plugin version to prevent conflicts.
	 *
	 * Use this class in the base/free plugin to ensure the pro/extended plugin meets
	 * the minimum required version before activation.
	 *
	 * Subclasses must implement:
	 * - `compatible_version()`: The minimum required version of the pro plugin.
	 * - `pro_plugin_file()`: The path to the pro plugin's main file.
	 *
	 * Optionally override:
	 * - `localize_notice_format()`: Customize the incompatibility notice message.
	 * - `show_admin_notice()`: Control whether admin notices are displayed.
	 * - `show_plugin_row_notice()`: Control whether plugin row notices are displayed.
	 * - `deactivate_incompatible()`: Enable automatic deactivation of incompatible versions.
	 * - `init()`: Add custom initialization logic.
	 *
	 * @name AbstractProPluginInCompatibility
	 *
	 * @phpstan-use HelperMethodsTrait<AbstractProPluginInCompatibility>
	 * @phpstan-use PluginCommonTrait<AbstractProPluginInCompatibility>
	 * @phpstan-use MethodShouldImplementTrait<AbstractProPluginInCompatibility>
	 *
	 * @see   HelperMethodsTrait For helper utility methods.
	 * @see   PluginCommonTrait For plugin-related methods.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractProPluginInCompatibility {

		use HelperMethodsTrait;
		use PluginCommonTrait;
		use MethodShouldImplementTrait;

		// =====================================================================
		// Properties
		// =====================================================================

		/**
		 * Cached plugin data from file headers.
		 *
		 * Stores the plugin header data retrieved via get_plugin_data() to avoid
		 * repeated file reads.
		 *
		 * @var array<string, mixed> Plugin header data array.
		 *
		 * @since 1.0.0
		 */
		protected array $plugin_data = array();

		// =====================================================================
		// Constructor and Initialization Methods
		// =====================================================================

		/**
		 * Initialize the incompatibility checker.
		 *
		 * Sets up the caller reference and registers hooks for checking plugin
		 * compatibility on admin initialization.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // In your base plugin's main class:
		 * class MyProPluginCheck extends AbstractProPluginInCompatibility {
		 *     public function compatible_version(): string {
		 *         return '2.0.0'; // Minimum required pro version
		 *     }
		 *     public function pro_plugin_file(): string {
		 *         return WP_PLUGIN_DIR . '/my-plugin-pro/my-plugin-pro.php';
		 *     }
		 * }
		 *
		 * // Initialize in base plugin:
		 * new MyProPluginCheck( $this );
		 */
		public function __construct() {
			$this->hooks();
			$this->init();
		}

		/**
		 * Custom initialization hook for subclasses.
		 *
		 * Override this method in subclasses to add custom initialization logic
		 * that runs after the constructor completes and hooks are registered.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // In your subclass:
		 * public function init(): void {
		 *     // Add custom initialization logic
		 *     $this->setup_additional_checks();
		 * }
		 */
		public function init(): void {
		}

		// =====================================================================
		// Hook Registration Methods
		// =====================================================================

		/**
		 * Register WordPress action hooks.
		 *
		 * Sets up hooks for checking plugin compatibility and optionally
		 * deactivating incompatible versions during admin initialization.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see   self::loaded() Checks compatibility and shows notices.
		 * @see   self::deactivate() Optionally deactivates incompatible plugin.
		 */
		final public function hooks(): void {
			// Check compatibility and display notices at priority 9.
			add_action( 'admin_init', array( $this, 'loaded' ), 9 );
			// Deactivate incompatible plugin at priority 12 (after loaded).
			add_action( 'admin_init', array( $this, 'deactivate' ), 12 );
		}

		// =====================================================================
		// Abstract Methods (Must Be Implemented by Subclasses)
		// =====================================================================

		/**
		 * Get the minimum compatible version of the pro plugin.
		 *
		 * This method must be implemented by subclasses to specify the minimum
		 * required version of the pro/extended plugin that is compatible with
		 * the base plugin.
		 *
		 * @return string The minimum compatible version string (e.g., '2.0.0').
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Require minimum version 2.0.0:
		 * public function compatible_version(): string {
		 *     return '2.0.0';
		 * }
		 *
		 * // Dynamic version based on base plugin:
		 * public function compatible_version(): string {
		 *     return MY_PLUGIN_MIN_PRO_VERSION;
		 * }
		 */
		abstract public function compatible_version(): string;

		// =====================================================================
		// Capability Methods
		// =====================================================================

		/**
		 * Check if current user has capability to manage plugins.
		 *
		 * Verifies the user has 'update_plugins' capability, which is typically
		 * required for administrators to see plugin-related notices.
		 *
		 * @return bool True if user can update plugins, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @see   self::loaded() Where this check is performed.
		 * @see   self::deactivate() Where this check is performed.
		 */
		public function has_capability(): bool {
			return current_user_can( 'update_plugins' );
		}

		// =====================================================================
		// Initialization and Loading Methods
		// =====================================================================

		/**
		 * Initialize compatibility checking and register notices.
		 *
		 * Callback for the 'admin_init' action hook (priority 9). Checks if the
		 * pro plugin exists and is incompatible, then registers admin notices
		 * and plugin row notices.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see   self::has_capability() For capability check.
		 * @see   self::is_compatible() For version comparison.
		 * @see   self::admin_notice() For admin notice display.
		 * @see   self::row_notice() For plugin row notice display.
		 */
		final public function loaded(): void {

			if ( ! $this->has_capability() ) {
				return;
			}

			if ( ! $this->is_valid_plugin() ) {
				return;
			}

			if ( $this->is_compatible() ) {
				return;
			}

			// Display admin notice for incompatible plugin.
			add_action( 'admin_notices', array( $this, 'admin_notice' ), 12 );

			// Display notice in plugin row.
			add_action( 'after_plugin_row_' . $this->get_plugin_basename(), array( $this, 'row_notice' ) );
		}

		// =====================================================================
		// Deactivation Methods
		// =====================================================================

		/**
		 * Deactivate incompatible plugin if configured.
		 *
		 * Callback for the 'admin_init' action hook (priority 12). Silently
		 * deactivates the incompatible plugin version if `deactivate_incompatible()`
		 * returns true.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see   self::has_capability() For capability check.
		 * @see   self::is_compatible() For version comparison.
		 * @see   self::deactivate_incompatible() For deactivation toggle.
		 */
		final public function deactivate(): void {

			if ( ! $this->has_capability() ) {
				return;
			}

			if ( ! $this->is_valid_plugin() ) {
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

			// Deactivate the plugin silently, preventing deactivation hooks from running.
			deactivate_plugins( $this->get_plugin_basename(), true );
		}

		// =====================================================================
		// Notice Display Methods
		// =====================================================================

		/**
		 * Display admin notice for incompatible plugin.
		 *
		 * Callback for the 'admin_notices' action hook. Displays an error notice
		 * at the top of admin pages when an incompatible plugin version is detected.
		 *
		 * @return void
		 *
		 * @throws \WP_Exception If "localize_notice_format" is not overridden in subclass.
		 *
		 * @since 1.0.0
		 *
		 * @see   self::show_admin_notice() For notice toggle.
		 * @see   self::get_notice_content() For notice message.
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
		 * Display notice in the plugin row on the plugins page.
		 *
		 * Callback for the 'after_plugin_row_{plugin_basename}' action hook.
		 * Displays a warning notice directly below the plugin row.
		 *
		 * @return void
		 *
		 * @throws \WP_Exception If "localize_notice_format" is not overridden in subclass.
		 *
		 * @since 1.0.0
		 *
		 * @see   self::show_plugin_row_notice() For notice toggle.
		 * @see   self::get_notice_content() For notice message.
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

		// =====================================================================
		// Configuration Methods
		// =====================================================================

		/**
		 * Determine whether to show the plugin row notice.
		 *
		 * Override this method to disable the plugin row notice.
		 *
		 * @return bool True to show the notice, false to hide it.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Disable plugin row notice:
		 * public function show_plugin_row_notice(): bool {
		 *     return false;
		 * }
		 *
		 * @see   self::row_notice() Where this is checked.
		 */
		public function show_plugin_row_notice(): bool {
			return true;
		}

		/**
		 * Determine whether to show the admin notice.
		 *
		 * Override this method to disable the admin notice.
		 *
		 * @return bool True to show the notice, false to hide it.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Disable admin notice:
		 * public function show_admin_notice(): bool {
		 *     return false;
		 * }
		 *
		 * // Only show on specific pages:
		 * public function show_admin_notice(): bool {
		 *     $screen = get_current_screen();
		 *     return $screen && 'plugins' === $screen->id;
		 * }
		 *
		 * @see   self::admin_notice() Where this is checked.
		 */
		public function show_admin_notice(): bool {
			return true;
		}

		/**
		 * Determine whether to auto-deactivate incompatible plugins.
		 *
		 * Override this method to enable automatic deactivation of incompatible
		 * plugin versions. Use with caution as this silently deactivates plugins.
		 *
		 * @return bool True to auto-deactivate, false to only show warnings.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Enable auto-deactivation:
		 * public function deactivate_incompatible(): bool {
		 *     return true;
		 * }
		 *
		 * @see   self::deactivate() Where this is checked.
		 */
		public function deactivate_incompatible(): bool {
			return false;
		}

		// =====================================================================
		// Version Comparison Methods
		// =====================================================================

		/**
		 * Check if the installed pro plugin version is compatible.
		 *
		 * Compares the currently installed pro plugin version against the
		 * minimum required version using PHP's version_compare().
		 *
		 * @return bool True if compatible, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @see   self::compatible_version() For required version.
		 * @see   PluginCommonTrait::get_plugin_version() For current version.
		 */
		private function is_compatible(): bool {
			$current_version  = $this->get_plugin_version();
			$required_version = $this->compatible_version();

			return version_compare( $current_version, $required_version, '>=' );
		}

		// =====================================================================
		// Notice Content Methods
		// =====================================================================

		/**
		 * Get the formatted notice content.
		 *
		 * Generates the notice message by combining the localized format string
		 * with the plugin name, current version, and required version.
		 *
		 * @return string The formatted notice message.
		 *
		 * @throws \WP_Exception If "localize_notice_format" is not overridden in subclass.
		 *
		 * @since 1.0.0
		 *
		 * @see   self::localize_notice_format() For message format.
		 * @see   PluginCommonTrait::get_plugin_name() For plugin name.
		 * @see   PluginCommonTrait::get_plugin_version() For current version.
		 * @see   self::compatible_version() For required version.
		 */
		public function get_notice_content(): string {

			$name               = $this->get_plugin_name();
			$version            = $this->get_plugin_version();
			$compatible_version = $this->compatible_version();

			return sprintf( $this->localize_notice_format(), $name, $version, $compatible_version );
		}

		/**
		 * Get the localized notice format string.
		 *
		 * Override this method to provide a custom message format for the
		 * incompatibility notice. The format string should include three
		 * placeholders: %1$s (name), %2$s (current version), %3$s (required version).
		 *
		 * @return string The notice format string with placeholders.
		 *
		 * @throws \WP_Exception Logs a notice that subclass should implement this method.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Custom localized notice:
		 * public function localize_notice_format(): string {
		 *     // translators: 1: Plugin Name, 2: Current Version, 3: Required Version.
		 *     return esc_html__(
		 *         'Incompatible version of %1$s (%2$s) detected. Version %3$s or higher required.',
		 *         'my-plugin'
		 *     );
		 * }
		 *
		 * @see   self::get_notice_content() Where this format is used.
		 */
		public function localize_notice_format(): string {

			$this->subclass_should_implement( __FUNCTION__ );

			// translators: 1: Extended Plugin Name. 2: Extended Plugin Version. 3: Extended Plugin Compatible Version.
			return 'You are using an incompatible version of <strong>%1$s - (%2$s)</strong>. Please upgrade to version <strong>%3$s</strong> or upper.';
		}

		// =====================================================================
		// Plugin File Methods
		// =====================================================================

		/**
		 * Get the path to the pro/extended plugin's main file.
		 *
		 * This method must be implemented by subclasses to return the absolute
		 * path to the pro plugin's main PHP file.
		 *
		 * @return string The absolute path to the pro plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Using constant:
		 * public function pro_plugin_file(): string {
		 *     return WP_PLUGIN_DIR . '/my-plugin-pro/my-plugin-pro.php';
		 * }
		 *
		 * // Using defined constant:
		 * public function pro_plugin_file(): string {
		 *     if ( defined( 'MY_PLUGIN_PRO_FILE' ) ) {
		 *         return MY_PLUGIN_PRO_FILE;
		 *     }
		 *     return WP_PLUGIN_DIR . '/my-plugin-pro/my-plugin-pro.php';
		 * }
		 */
		abstract public function pro_plugin_file(): string;

		/**
		 * Get the plugin file path.
		 *
		 * Returns the pro plugin file path. This method wraps `pro_plugin_file()`
		 * and is used by the PluginCommonTrait for version checking.
		 *
		 * @return string The absolute path to the plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @see   self::pro_plugin_file() The method this wraps.
		 * @see   PluginCommonTrait::get_plugin_file() Where this is used.
		 */
		final public function plugin_file(): string {
			return $this->pro_plugin_file();
		}
	}
}
