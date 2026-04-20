<?php
	/**
	 * Abstract Admin Menu Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\Internal\InternalPackageTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;
	use StorePress\AdminUtils\Traits\PluginCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractAdminMenu' ) ) {

	/**
	 * Abstract base class for creating WordPress admin menu pages.
	 *
	 * @name AbstractAdminMenu
	 *
	 * @phpstan-use HelperMethodsTrait<AbstractAdminMenu>
	 * @phpstan-use InternalPackageTrait<AbstractAdminMenu>
	 * @phpstan-use MethodShouldImplementTrait<AbstractAdminMenu>
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractAdminMenu {

		use HelperMethodsTrait;
		use InternalPackageTrait;
		use MethodShouldImplementTrait;

		/**
		 * Position counter for unique menu ordering.
		 *
		 * @var int
		 *
		 * @since 1.0.0
		 */
		private static int $position = 2;

		/**
		 * Tracks usage count of page slugs for unique suffix generation.
		 *
		 * @var array<string, int>
		 *
		 * @since 1.0.0
		 */
		private static array $slug_usages = array();

		/**
		 * Current page slug, may include a numeric suffix for uniqueness.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		private string $current_page_slug = '';

		/**
		 * Whether separator CSS has been added.
		 *
		 * @var bool
		 *
		 * @since 1.0.0
		 */
		private static bool $is_separator_css_added = false;

		// =========================================================================
		// Constructor and Initialization Methods
		// =========================================================================

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->hooks();
			$this->init();
		}

		/**
		 * Called after constructor sets up hooks. Override for additional initialization.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function init(): void {}

		// =========================================================================
		// Hook Registration Methods
		// =========================================================================

		/**
		 * Register WordPress hooks for admin menu functionality.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::add_menu()
		 * @see self::add_menu_page()
		 * @see self::remove_menu()
		 * @see self::page_init()
		 */
		final public function hooks(): void {

			// Register top-level menu at priority 9.
			add_action( 'admin_menu', array( $this, 'add_menu' ), 9 );

			// Register submenu page at priority 12.
			add_action( 'admin_menu', array( $this, 'add_menu_page' ), 12 );

			// Remove duplicate menu entries at priority 60.
			add_action( 'admin_menu', array( $this, 'remove_menu' ), 60 );

			// Add inline separator CSS fix.
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

			// Initialize admin page for users with proper capability.
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
		 * Add top-level admin menu with optional separator. Skips if submenu or already exists.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::is_submenu()
		 * @see self::menu_separator()
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
		 * Add submenu page with unique slug generation under the parent menu.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_current_page_slug()
		 * @see self::render()
		 * @see self::page_loaded()
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

			// Trigger page_loaded() callback when this admin page is loaded.
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
		 * Remove duplicate submenu entry created by WordPress for top-level menus.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::is_submenu()
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
		 * Get the unique page slug used in the WordPress admin URL.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_page_slug()
		 */
		public function get_current_page_slug(): string {
			return $this->current_page_slug;
		}

		// =========================================================================
		// Menu Separator Methods
		// =========================================================================

		/**
		 * Whether to add a menu separator before the top-level menu.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 *
		 * @see self::menu_separator()
		 */
		public function add_menu_separator(): bool {
			return true;
		}

		/**
		 * Add a visual separator in the admin menu at the given position.
		 *
		 * @param float  $position                  Menu position for the separator.
		 * @param string $separator_additional_class Additional CSS class for unique slug.
		 * @param string $capability                Required capability. Default 'manage_options'.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::add_menu_separator()
		 * @see self::add_menu()
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
		 * Check if menu slug contains '.php', indicating a submenu of a core admin page.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_menu_slug()
		 */
		public function is_submenu(): bool {
			return false !== strpos( $this->get_menu_slug(), '.php' );
		}

		/**
		 * Get the required capability to access this menu. Default 'manage_options'.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see self::has_capability()
		 */
		public function get_capability(): string {
			return 'manage_options';
		}

		/**
		 * Check if current user has the required capability.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_capability()
		 */
		public function has_capability(): bool {
			return current_user_can( $this->get_capability() );
		}

		// =========================================================================
		// Menu Configuration Methods
		// =========================================================================

		/**
		 * Get the menu position in the admin sidebar. Default 45.
		 *
		 * @return int
		 *
		 * @since 1.0.0
		 *
		 * @see self::add_menu()
		 */
		public function get_menu_position(): int {
			return 45;
		}

		/**
		 * Get the menu title displayed in the admin sidebar.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_menu_title(): string {
			$this->subclass_should_implement( __FUNCTION__ );

			return 'StorePress';
		}

		/**
		 * Get the menu slug. If it contains '.php', treated as a submenu.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see self::is_submenu()
		 */
		public function get_menu_slug(): string {
			return 'storepress';
		}

		/**
		 * Get the menu icon (dashicon class, base64 SVG, or 'none').
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_menu_icon(): string {
			return 'dashicons-admin-generic';
		}

		// =========================================================================
		// Page Configuration Methods
		// =========================================================================

		/**
		 * Get the base page slug for the submenu page.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see self::get_current_page_slug()
		 */
		public function get_page_slug(): string {
			return $this->get_plugin_slug();
		}

		/**
		 * Get the page title displayed in the browser title bar.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_page_title(): string {

			$this->subclass_should_implement( __FUNCTION__ );

			return $this->get_plugin_name();
		}

		/**
		 * Get the submenu item title displayed in the admin menu.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_page_menu_title(): string {
			return $this->get_plugin_name();
		}

		// =========================================================================
		// Page Lifecycle Methods
		// =========================================================================

		/**
		 * Called on admin_init for users with capability. Override to register settings.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::page_loaded()
		 */
		public function page_init(): void {
			$this->subclass_should_implement( __FUNCTION__ );
			// Override in child class to register settings.
		}

		/**
		 * Called when this specific admin page is loaded. Override to enqueue assets.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::render()
		 */
		public function page_loaded(): void {
			$this->subclass_should_implement( __FUNCTION__ );
			// Override in child class to enqueue assets.
		}

		/**
		 * Render the admin page content. Override to output page HTML.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see self::page_loaded()
		 */
		public function render(): void {
			$this->subclass_should_implement( __FUNCTION__ );
			// Override in child class to render page content.
		}
	}
}
