<?php
	/**
	 * Abstract Admin Menu Class File.
	 *
	 * Provides an abstract base class for creating WordPress admin menu pages
	 * with support for top-level menus, submenus, and menu separators.
	 * This class handles menu registration, capability checks, and page rendering.
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
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;
	use StorePress\AdminUtils\Traits\PluginCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractAdminMenu' ) ) {
	/**
	 * Abstract Admin Menu Class.
	 *
	 * Provides base functionality for creating WordPress admin menu pages
	 * with support for top-level menus, submenus, and menu separators.
	 * This class automates menu registration, duplicate menu removal,
	 * and page initialization with proper capability checks.
	 *
	 * Features:
	 * - Top-level menu creation with optional separators.
	 * - Submenu page registration under parent menus.
	 * - Automatic unique slug generation for duplicate page slugs.
	 * - Capability-based access control.
	 * - Page load and render lifecycle hooks.
	 *
	 * @name AbstractAdminMenu
	 *
	 * @see HelperMethodsTrait For helper utility methods.
	 * @see PluginCommonTrait For plugin-related methods.
	 * @see CallerTrait For caller object management.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractAdminMenu {

		use HelperMethodsTrait;
		use PluginCommonTrait;
		use InternalPackageTrait;
		use CallerTrait;
		use MethodShouldImplementTrait;

		/**
		 * Static position counter for menu ordering.
		 *
		 * Used to ensure unique positions for menus and separators.
		 * This counter increments with each menu/separator added to prevent
		 * position conflicts in the WordPress admin menu.
		 *
		 * @var int Starting position offset for menu items.
		 *
		 * @since 1.0.0
		 */
		private static int $position = 2;

		/**
		 * Tracks usage count of page slugs.
		 *
		 * Used to generate unique slugs when the same base slug is used multiple times.
		 * Maps page slug strings to their usage count for suffix generation.
		 *
		 * @var array<string, int> Associative array of slug => usage count.
		 *
		 * @since 1.0.0
		 */
		private static array $slug_usages = array();

		/**
		 * The current page slug for this menu instance.
		 *
		 * May include a numeric suffix if the base slug was already in use.
		 * This is the actual slug used when registering the submenu page.
		 *
		 * @var string The unique page slug for this instance.
		 *
		 * @since 1.0.0
		 */
		private string $current_page_slug = '';

		/**
		 * Check is separator CSS added or not.
		 *
		 * @var bool
		 */
		private static bool $is_separator_css_added = false;

		// =========================================================================
		// Constructor and Initialization Methods
		// =========================================================================

		/**
		 * Admin Menu Constructor.
		 *
		 * Initializes the admin menu by setting the caller and registering hooks.
		 * This constructor is called when a child class instance is created.
		 *
		 * @param object $caller The caller object that instantiated this menu.
		 *                       Typically, the main plugin class instance.
		 *
		 * @since 1.0.0
		 *
		 * @example Basic usage in a child class:
		 * ```php
		 * class MyPluginMenu extends AbstractAdminMenu {
		 *     // Override methods as needed
		 * }
		 * $menu = new MyPluginMenu( $this );
		 * ```
		 *
		 * @example With a plugin instance:
		 * ```php
		 * add_action( 'plugins_loaded', function() {
		 *     $plugin = MyPlugin::instance();
		 *     new MySettingsPage( $plugin );
		 * } );
		 * ```
		 *
		 * @see CallerTrait::set_caller() For setting the caller object.
		 * @see AbstractAdminMenu::hooks() For hook registration.
		 * @see AbstractAdminMenu::init() For additional initialization.
		 */
		public function __construct( object $caller ) {
			$this->set_caller( $caller );
			$this->hooks();
			$this->init();
		}

		/**
		 * Initialize the menu instance.
		 *
		 * Called after the constructor sets up hooks. Override this method
		 * in child classes to perform additional initialization tasks.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example Override to add custom initialization:
		 * ```php
		 * public function init(): void {
		 *     $this->load_dependencies();
		 *     $this->setup_custom_hooks();
		 * }
		 * ```
		 */
		public function init(): void {}

		// =========================================================================
		// Hook Registration Methods
		// =========================================================================

		/**
		 * Register WordPress hooks for admin menu functionality.
		 *
		 * Registers actions for menu creation, removal, and initialization.
		 * This method is marked as final to prevent child classes from
		 * overriding the core hook registration logic.
		 *
		 * Registered hooks:
		 * - `admin_menu` (priority 9): Creates top-level menu via add_menu().
		 * - `admin_menu` (priority 12): Creates submenu page via add_menu_page().
		 * - `admin_menu` (priority 60): Removes duplicate entries via remove_menu().
		 * - `admin_enqueue_scripts`: Adds inline CSS for menu separator styling.
		 * - `admin_init`: Triggers page_init() for users with capability.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see AbstractAdminMenu::add_menu() For top-level menu creation.
		 * @see AbstractAdminMenu::add_menu_page() For submenu page creation.
		 * @see AbstractAdminMenu::remove_menu() For duplicate menu removal.
		 * @see AbstractAdminMenu::page_init() For page initialization.
		 */
		final public function hooks(): void {

			/**
			 * Register top-level menu at priority 9.
			 *
			 * @see AbstractAdminMenu::add_menu()
			 */
			add_action( 'admin_menu', array( $this, 'add_menu' ), 9 );

			/**
			 * Register submenu page at priority 12.
			 *
			 * @see AbstractAdminMenu::add_menu_page()
			 */
			add_action( 'admin_menu', array( $this, 'add_menu_page' ), 12 );

			/**
			 * Remove duplicate menu entries at priority 60.
			 *
			 * @see AbstractAdminMenu::remove_menu()
			 */
			add_action( 'admin_menu', array( $this, 'remove_menu' ), 60 );

			/**
			 * Add inline styles for menu separator.
			 *
			 * Fixes min-height for WordPress admin menu separators.
			 */
			add_action(
				'admin_enqueue_scripts',
				function () {

					if ( self::$is_separator_css_added ) {
						return;
					}

					if ( ! $this->add_menu_separator() ) {
						return;
					}

					wp_add_inline_style( 'admin-menu', '#adminmenu li.menu-top.wp-menu-separator { min-height: auto; }' );
					self::$is_separator_css_added = true;
				}
			);

			/**
			 * Initialize admin page for users with proper capability.
			 *
			 * @see AbstractAdminMenu::page_init()
			 * @see AbstractAdminMenu::has_capability()
			 */
			add_action(
				'admin_init',
				function () {

					if ( $this->has_capability() ) {
						// Admin Page Init.
						$this->page_init();
					}
				}
			);
		}

		// =========================================================================
		// Menu Registration Methods
		// =========================================================================

		/**
		 * Add top-level admin menu.
		 *
		 * Creates the parent menu page with optional separator.
		 * Skips if this is a submenu or if the menu already exists.
		 * This method is called via the `admin_menu` hook at priority 9.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example Override menu properties in child class:
		 * ```php
		 * public function get_menu_title(): string {
		 *     return 'My Plugin';
		 * }
		 *
		 * public function get_menu_slug(): string {
		 *     return 'my-plugin';
		 * }
		 *
		 * public function get_menu_icon(): string {
		 *     return 'dashicons-admin-settings';
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::is_submenu() For submenu detection.
		 * @see AbstractAdminMenu::get_menu_slug() For menu slug.
		 * @see AbstractAdminMenu::get_menu_title() For menu title.
		 * @see AbstractAdminMenu::get_menu_icon() For menu icon.
		 * @see AbstractAdminMenu::menu_separator() For separator creation.
		 */
		public function add_menu(): void {
			// Bail if submenu.
			if ( $this->is_submenu() ) {
				return;
			}

			// Create Unique Admin Menu, If menu already registered,
			// it will return string, and we just type hint it to make bool.
			$parent_menu_url = (bool) trim( menu_page_url( $this->get_menu_slug(), false ) );

			if ( $parent_menu_url ) {
				return;
			}

			$capability = $this->get_capability();

			$separator_menu_position = (float) sprintf( '%d.%d', $this->get_menu_position(), self::$position );
			$this->menu_separator( $separator_menu_position, $this->get_menu_slug(), $capability );
			++self::$position;

			$menu_position = (float) sprintf( '%d.%d', $this->get_menu_position(), self::$position );
			add_menu_page(
				$this->get_menu_title(),
				$this->get_menu_title(),
				$capability,
				$this->get_menu_slug(),
				'__return_false',
				$this->get_menu_icon(),
				$menu_position
			);
			++self::$position;
		}

		/**
		 * Add submenu page under the parent menu.
		 *
		 * Handles unique slug generation for duplicate page slugs and
		 * registers the submenu page with its callback and load action.
		 * This method is called via the `admin_menu` hook at priority 12.
		 *
		 * Slug handling:
		 * - First usage: Uses the slug as-is (e.g., "my-plugin").
		 * - Subsequent usages: Appends a number suffix (e.g., "my-plugin-1").
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example Override page properties in child class:
		 * ```php
		 * public function get_page_slug(): string {
		 *     return 'my-plugin-settings';
		 * }
		 *
		 * public function get_page_title(): string {
		 *     return 'My Plugin Settings';
		 * }
		 *
		 * public function get_page_menu_title(): string {
		 *     return 'Settings';
		 * }
		 * ```
		 *
		 * @example Create submenu under existing WordPress page:
		 * ```php
		 * public function get_menu_slug(): string {
		 *     return 'options-general.php'; // Settings menu
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::get_page_slug() For base page slug.
		 * @see AbstractAdminMenu::get_current_page_slug() For actual registered slug.
		 * @see AbstractAdminMenu::get_page_title() For browser title.
		 * @see AbstractAdminMenu::get_page_menu_title() For menu item title.
		 * @see AbstractAdminMenu::render() For page content rendering.
		 * @see AbstractAdminMenu::page_loaded() For page load callback.
		 */
		public function add_menu_page(): void {

			$page_slug = $this->get_page_slug();

			if ( ! isset( self::$slug_usages[ $page_slug ] ) ) {
				self::$slug_usages[ $page_slug ] = 0;
			} else {
				++self::$slug_usages[ $page_slug ];
			}

			if ( 0 === self::$slug_usages[ $page_slug ] ) {
				$this->current_page_slug = sprintf( '%s', $page_slug );
			} else {
				$this->current_page_slug = sprintf( '%s-%s', $page_slug, self::$slug_usages[ $page_slug ] );
			}

			$capability = $this->get_capability();

			$settings_page = add_submenu_page(
				$this->get_menu_slug(),
				$this->get_page_title(),
				$this->get_page_menu_title(),
				$capability,
				$this->get_current_page_slug(),
				function () {
					if ( $this->has_capability() ) {
						// RENDER PAGE CONTENT.
						$this->render();
					}
				}
			);

			/**
			 * Trigger page loaded callback when admin page is loaded.
			 *
			 * @see AbstractAdminMenu::page_loaded()
			 */
			add_action(
				'load-' . $settings_page,
				function () {
					if ( $this->has_capability() ) {
						// PAGE LOADED.
						$this->page_loaded();
					}
				}
			);
		}

		/**
		 * Remove duplicate submenu entry created by add_menu_page.
		 *
		 * WordPress adds a duplicate submenu item when creating a top-level menu.
		 * This method removes that duplicate entry. Called via `admin_menu` hook
		 * at priority 60 to ensure all menus are registered first.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see AbstractAdminMenu::is_submenu() For submenu detection.
		 * @see AbstractAdminMenu::get_menu_slug() For menu slug.
		 */
		public function remove_menu(): void {
			if ( $this->is_submenu() ) {
				return;
			}

			// Remove duplicate menu.
			$menu_slug = $this->get_menu_slug();

			remove_submenu_page( $menu_slug, $menu_slug );
		}

		// =========================================================================
		// Slug and Identifier Methods
		// =========================================================================

		/**
		 * Get the current page slug.
		 *
		 * Returns the unique page slug, which may include a numeric suffix
		 * if the same slug is used multiple times. This is the actual slug
		 * used in the WordPress admin URL.
		 *
		 * @return string The current page slug.
		 *
		 * @since 1.0.0
		 *
		 * @example Get the current page URL:
		 * ```php
		 * $page_url = admin_url( 'admin.php?page=' . $this->get_current_page_slug() );
		 * ```
		 *
		 * @example Check if current page matches:
		 * ```php
		 * $current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		 * if ( $current_page === $this->get_current_page_slug() ) {
		 *     // We're on this page
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::get_page_slug() For the base page slug.
		 */
		public function get_current_page_slug(): string {
			return $this->current_page_slug;
		}

		// =========================================================================
		// Menu Separator Methods
		// =========================================================================

		/**
		 * Determine whether to add a menu separator before the menu.
		 *
		 * Override this method to return false to disable the separator.
		 * By default, a separator is added before top-level menus.
		 *
		 * @return bool True to add separator, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @example Disable menu separator:
		 * ```php
		 * public function add_menu_separator(): bool {
		 *     return false;
		 * }
		 * ```
		 *
		 * @example Conditionally add separator:
		 * ```php
		 * public function add_menu_separator(): bool {
		 *     return apply_filters( 'my_plugin_show_separator', true );
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::menu_separator() For separator creation.
		 */
		public function add_menu_separator(): bool {
			return true;
		}

		/**
		 * Add a menu separator at the specified position.
		 *
		 * Creates an empty menu item that acts as a visual separator
		 * in the WordPress admin menu. This is a private method called
		 * internally by add_menu().
		 *
		 * @param float  $position                   The menu position for the separator.
		 * @param string $separator_additional_class Additional CSS class for the separator.
		 *                                           Used to create a unique slug.
		 * @param string $capability                 Required capability to view the separator.
		 *                                           Default: 'manage_options'.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see AbstractAdminMenu::add_menu_separator() For separator enable/disable.
		 * @see AbstractAdminMenu::add_menu() For usage context.
		 */
		private function menu_separator( float $position, string $separator_additional_class = '', string $capability = 'manage_options' ): void {

			if ( ! current_user_can( $capability ) ) {
				return;
			}

			if ( ! $this->add_menu_separator() ) {
				return;
			}

			$menu_slug = sprintf( 'menu_separator_%s wp-menu-separator', strtolower( $separator_additional_class ) );

			add_menu_page(
				'',
				'',
				$capability,
				$menu_slug,
				'__return_false',
				'none',
				$position
			);
		}

		// =========================================================================
		// Capability and Access Control Methods
		// =========================================================================

		/**
		 * Check if this menu is a submenu of an existing WordPress admin page.
		 *
		 * Determines if the menu slug contains '.php', indicating it should
		 * be added as a submenu to a core WordPress admin page (e.g., Settings,
		 * Tools, or other existing admin pages).
		 *
		 * @return bool True if this is a submenu, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @example Top-level menu (returns false):
		 * ```php
		 * public function get_menu_slug(): string {
		 *     return 'my-plugin'; // Creates top-level menu
		 * }
		 * ```
		 *
		 * @example Submenu under Settings (returns true):
		 * ```php
		 * public function get_menu_slug(): string {
		 *     return 'options-general.php'; // Adds under Settings
		 * }
		 * ```
		 *
		 * @example Submenu under Tools (returns true):
		 * ```php
		 * public function get_menu_slug(): string {
		 *     return 'tools.php'; // Adds under Tools
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::get_menu_slug() For menu slug configuration.
		 */
		public function is_submenu(): bool {
			return false !== strpos( $this->get_menu_slug(), '.php' );
		}

		/**
		 * Get the required capability to access the menu.
		 *
		 * Override this method to change the required user capability.
		 * Default capability is 'manage_options' (Administrator level).
		 *
		 * @return string The capability required to access this menu.
		 *
		 * @since 1.0.0
		 *
		 * @example Restrict to administrators only (default):
		 * ```php
		 * public function get_capability(): string {
		 *     return 'manage_options';
		 * }
		 * ```
		 *
		 * @example Allow editors and above:
		 * ```php
		 * public function get_capability(): string {
		 *     return 'edit_others_posts';
		 * }
		 * ```
		 *
		 * @example Use custom capability:
		 * ```php
		 * public function get_capability(): string {
		 *     return 'my_plugin_manage_settings';
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::has_capability() For capability check.
		 * @link https://developer.wordpress.org/plugins/users/roles-and-capabilities/
		 */
		public function get_capability(): string {
			return 'manage_options';
		}

		/**
		 * Check if current user has the required capability.
		 *
		 * Wrapper around WordPress current_user_can() using this menu's
		 * configured capability.
		 *
		 * @return bool True if user has capability, false otherwise.
		 *
		 * @since 1.0.0
		 *
		 * @example Check access before performing action:
		 * ```php
		 * if ( $this->has_capability() ) {
		 *     // User can access, proceed with action
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::get_capability() For capability value.
		 */
		public function has_capability(): bool {
			return current_user_can( $this->get_capability() );
		}

		// =========================================================================
		// Menu Configuration Methods
		// =========================================================================

		/**
		 * Get the menu position in the admin sidebar.
		 *
		 * Override this method to change the menu position.
		 * WordPress default menu positions:
		 * - 2: Dashboard
		 * - 4: Separator
		 * - 5: Posts
		 * - 10: Media
		 * - 20: Pages
		 * - 25: Comments
		 * - 59: Separator
		 * - 60: Appearance
		 * - 65: Plugins
		 * - 70: Users
		 * - 75: Tools
		 * - 80: Settings
		 * - 99: Separator
		 *
		 * @return int The menu position (default: 45).
		 *
		 * @since 1.0.0
		 *
		 * @example Place after Comments menu:
		 * ```php
		 * public function get_menu_position(): int {
		 *     return 26;
		 * }
		 * ```
		 *
		 * @example Place after Settings menu:
		 * ```php
		 * public function get_menu_position(): int {
		 *     return 81;
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::add_menu() For position usage.
		 */
		public function get_menu_position(): int {
			return 45;
		}

		/**
		 * Get the menu title displayed in the admin sidebar.
		 *
		 * Override this method to set a custom menu title.
		 * This is the text shown in the WordPress admin sidebar.
		 *
		 * @return string The menu title.
		 *
		 * @since 1.0.0
		 *
		 * @example Set custom menu title:
		 * ```php
		 * public function get_menu_title(): string {
		 *     return esc_html__( 'My Plugin', 'my-plugin' );
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::add_menu() For title usage.
		 */
		public function get_menu_title(): string {
			$this->subclass_should_implement( __FUNCTION__ );

			return 'StorePress';
		}

		/**
		 * Get the menu slug used for URL and identification.
		 *
		 * Override this method to set a custom menu slug.
		 * If the slug contains '.php', it will be treated as a submenu
		 * of an existing WordPress admin page.
		 *
		 * @return string The menu slug.
		 *
		 * @since 1.0.0
		 *
		 * @example Top-level menu:
		 * ```php
		 * public function get_menu_slug(): string {
		 *     return 'my-plugin';
		 * }
		 * ```
		 *
		 * @example Submenu under Settings:
		 * ```php
		 * public function get_menu_slug(): string {
		 *     return 'options-general.php';
		 * }
		 * ```
		 *
		 * @example Submenu under WooCommerce:
		 * ```php
		 * public function get_menu_slug(): string {
		 *     return 'woocommerce';
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::is_submenu() For submenu detection.
		 */
		public function get_menu_slug(): string {
			return 'storepress';
		}

		/**
		 * Get the menu icon displayed in the admin sidebar.
		 *
		 * Override this method to set a custom dashicon or SVG icon.
		 * Supports dashicons, base64-encoded SVG, or 'none'.
		 *
		 * @return string The dashicon class or base64 SVG.
		 *
		 * @since 1.0.0
		 *
		 * @example Use a dashicon:
		 * ```php
		 * public function get_menu_icon(): string {
		 *     return 'dashicons-cart';
		 * }
		 * ```
		 *
		 * @example Use base64 SVG:
		 * ```php
		 * public function get_menu_icon(): string {
		 *     return 'data:image/svg+xml;base64,' . base64_encode( $svg_content );
		 * }
		 * ```
		 *
		 * @example No icon:
		 * ```php
		 * public function get_menu_icon(): string {
		 *     return 'none';
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::add_menu() For icon usage.
		 * @link https://developer.wordpress.org/resource/dashicons/
		 */
		public function get_menu_icon(): string {
			return 'dashicons-admin-generic';
		}

		// =========================================================================
		// Page Configuration Methods
		// =========================================================================

		/**
		 * Get the page slug for the submenu page.
		 *
		 * Override this method to provide a unique page slug.
		 * By default, returns the plugin slug from PluginCommonTrait.
		 *
		 * @return string The page slug.
		 *
		 * @since 1.0.0
		 *
		 * @example Custom page slug:
		 * ```php
		 * public function get_page_slug(): string {
		 *     return 'my-plugin-settings';
		 * }
		 * ```
		 *
		 * @example Using plugin slug with suffix:
		 * ```php
		 * public function get_page_slug(): string {
		 *     return $this->get_plugin_slug() . '-advanced';
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::get_current_page_slug() For actual registered slug.
		 * @see PluginCommonTrait::get_plugin_slug() For default value source.
		 */
		public function get_page_slug(): string {
			return $this->get_plugin_slug();
		}

		/**
		 * Get the page title displayed in the browser title bar.
		 *
		 * Override this method to provide the page title.
		 * This is shown in the browser tab/title bar.
		 *
		 * @return string The page title.
		 *
		 * @since 1.0.0
		 *
		 * @example Custom page title:
		 * ```php
		 * public function get_page_title(): string {
		 *     return esc_html__( 'My Plugin Settings', 'my-plugin' );
		 * }
		 * ```
		 *
		 * @example Dynamic page title:
		 * ```php
		 * public function get_page_title(): string {
		 *     return sprintf(
		 *         esc_html__( '%s - Settings', 'my-plugin' ),
		 *         $this->get_plugin_name()
		 *     );
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::add_menu_page() For title usage.
		 */
		public function get_page_title(): string {

			$this->subclass_should_implement( __FUNCTION__ );

			return $this->get_plugin_name();
		}

		/**
		 * Get the submenu page title displayed in the admin menu.
		 *
		 * Override this method to provide the menu title.
		 * By default, returns the plugin name from PluginCommonTrait.
		 * This is the text shown in the submenu item.
		 *
		 * @return string The submenu page menu title.
		 *
		 * @since 1.0.0
		 *
		 * @example Custom menu title:
		 * ```php
		 * public function get_page_menu_title(): string {
		 *     return esc_html__( 'Settings', 'my-plugin' );
		 * }
		 * ```
		 *
		 * @example Menu title with notification badge:
		 * ```php
		 * public function get_page_menu_title(): string {
		 *     $count = $this->get_pending_count();
		 *     if ( $count > 0 ) {
		 *         return sprintf( 'Settings <span class="awaiting-mod">%d</span>', $count );
		 *     }
		 *     return 'Settings';
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::add_menu_page() For title usage.
		 * @see PluginCommonTrait::get_plugin_name() For default value source.
		 */
		public function get_page_menu_title(): string {
			return $this->get_plugin_name();
		}

		// =========================================================================
		// Page Lifecycle Methods
		// =========================================================================

		/**
		 * Initialize the admin page.
		 *
		 * Called during admin_init hook for users with proper capability.
		 * Override this method to register settings, scripts, or other initialization.
		 * This is called on every admin page load, not just this specific page.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example Register settings:
		 * ```php
		 * public function page_init(): void {
		 *     register_setting(
		 *         'my_plugin_options',
		 *         'my_plugin_settings',
		 *         array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		 *     );
		 * }
		 * ```
		 *
		 * @example Add settings sections and fields:
		 * ```php
		 * public function page_init(): void {
		 *     add_settings_section(
		 *         'general_section',
		 *         'General Settings',
		 *         array( $this, 'render_section' ),
		 *         $this->get_current_page_slug()
		 *     );
		 *
		 *     add_settings_field(
		 *         'enable_feature',
		 *         'Enable Feature',
		 *         array( $this, 'render_checkbox' ),
		 *         $this->get_current_page_slug(),
		 *         'general_section'
		 *     );
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::hooks() For when this is called.
		 * @see AbstractAdminMenu::page_loaded() For page-specific initialization.
		 */
		public function page_init(): void {
			$this->subclass_should_implement( __FUNCTION__ );
			// Override in child class to register settings.
		}

		/**
		 * Handle page load event.
		 *
		 * Called when the specific admin page is loaded, before any output.
		 * Override this method to enqueue scripts/styles or prepare data.
		 * This is the ideal place for page-specific asset loading.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example Enqueue page-specific assets:
		 * ```php
		 * public function page_loaded(): void {
		 *     wp_enqueue_style(
		 *         'my-plugin-admin',
		 *         plugins_url( 'assets/css/admin.css', MY_PLUGIN_FILE ),
		 *         array(),
		 *         MY_PLUGIN_VERSION
		 *     );
		 *
		 *     wp_enqueue_script(
		 *         'my-plugin-admin',
		 *         plugins_url( 'assets/js/admin.js', MY_PLUGIN_FILE ),
		 *         array( 'jquery' ),
		 *         MY_PLUGIN_VERSION,
		 *         true
		 *     );
		 * }
		 * ```
		 *
		 * @example Add help tabs:
		 * ```php
		 * public function page_loaded(): void {
		 *     $screen = get_current_screen();
		 *     $screen->add_help_tab( array(
		 *         'id'      => 'overview',
		 *         'title'   => 'Overview',
		 *         'content' => '<p>Help content here.</p>',
		 *     ) );
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::add_menu_page() For when this is called.
		 * @see AbstractAdminMenu::render() For page output.
		 */
		public function page_loaded(): void {
			$this->subclass_should_implement( __FUNCTION__ );
			// Override in child class to enqueue assets.
		}

		/**
		 * Render the page template.
		 *
		 * Called when displaying the admin page content.
		 * Override this method to output the page HTML.
		 * This is called after page_loaded() and is responsible for
		 * generating all visible page content.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example Basic page template:
		 * ```php
		 * public function render(): void {
		 *     ?>
		 *     <div class="wrap">
		 *         <h1><?php echo esc_html( $this->get_page_title() ); ?></h1>
		 *         <form method="post" action="options.php">
		 *             <?php
		 *             settings_fields( 'my_plugin_options' );
		 *             do_settings_sections( $this->get_current_page_slug() );
		 *             submit_button();
		 *             ?>
		 *         </form>
		 *     </div>
		 *     <?php
		 * }
		 * ```
		 *
		 * @example Load template file:
		 * ```php
		 * public function render(): void {
		 *     include plugin_dir_path( MY_PLUGIN_FILE ) . 'templates/admin-page.php';
		 * }
		 * ```
		 *
		 * @see AbstractAdminMenu::add_menu_page() For render callback registration.
		 * @see AbstractAdminMenu::page_loaded() For pre-render setup.
		 */
		public function render(): void {
			$this->subclass_should_implement( __FUNCTION__ );
			// Override in child class to render page content.
		}
	}
}
