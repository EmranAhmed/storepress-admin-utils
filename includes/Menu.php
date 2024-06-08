<?php
	/**
	 * Admin Settings Menu Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Menu' ) ) {
	/**
	 * Admin Settings Menu Class.
	 *
	 * @name Menu
	 */
	abstract class Menu {

		use Common;

		/**
		 * Menu Position
		 *
		 * @var int $position Menu Position.
		 */
		private static int $position = 2;

		/**
		 * Used menu slugs.
		 *
		 * @var array $slug_usages Slug used in menu.
		 */

		private static array $slug_usages = array();

		/**
		 * Current Page Slug.
		 *
		 * @var string $current_page_slug Store Current visiting page slug.
		 */
		private string $current_page_slug = '';

		/**
		 * Menu constructor.
		 */
		public function __construct() {

			add_action(
				'admin_menu',
				function () {
					global $submenu, $menu;

					// Bail if submenu.
					if ( $this->is_submenu() ) {
						return;
					}

					// Create unique Menu.
					foreach ( $menu as $m ) {
						if ( $m[2] === $this->get_parent_slug() ) {
							return;
						}
					}

					$capability = $this->get_capability();

					$separator_menu_position = sprintf( '%s.%s', $this->get_menu_position(), self::$position );
					$this->admin_menu_separator( $separator_menu_position, $this->get_parent_slug(), $capability );
					self::$position++;

					$menu_position = sprintf( '%s.%s', $this->get_menu_position(), self::$position );
					add_menu_page( $this->get_parent_menu_title(), $this->get_parent_menu_title(), $capability, $this->get_parent_slug(), '', $this->get_menu_icon(), $menu_position );
					self::$position++;
				},
				9
			);

			add_action(
				'admin_menu',
				function () {

					$parent_slug = $this->get_parent_slug();

					$menu_slug = $this->get_page_slug();

					if ( ! isset( self::$slug_usages[ $menu_slug ] ) ) {
						self::$slug_usages[ $menu_slug ] = 0;
					} else {
						self::$slug_usages[ $menu_slug ] += 1;
					}

					if ( 0 === self::$slug_usages[ $menu_slug ] ) {
						$this->current_page_slug = sprintf( '%s', $menu_slug );
					} else {
						$this->current_page_slug = sprintf( '%s-%s', $menu_slug, self::$slug_usages[ $menu_slug ] );
					}

					$capability = $this->get_capability();

					$settings_page = add_submenu_page(
						$parent_slug,
						$this->page_title(),
						$this->menu_title(),
						$capability,
						$this->get_current_page_slug(),
						function () {

							if ( ! current_user_can( $this->get_capability() ) ) {
								return;
							}

							$this->display_settings_page();
						}
					);

					add_action(
						'load-' . $settings_page,
						function () {

							if ( ! current_user_can( $this->get_capability() ) ) {
								return;
							}

							$this->settings_page_init();
						}
					);
				},
				12
			);

			add_action(
				'admin_menu',
				function () {

					global $submenu, $menu;

					if ( $this->is_submenu() ) {
						return;
					}

					$slug = $this->get_parent_slug();

					if ( ! isset( $submenu[ $slug ] ) ) {
						return;
					}

					unset( $submenu[ $slug ][0] );
				},
				60
			);

			add_action(
				'admin_init',
				function () {

					// Process Settings Actions.
					$this->settings_actions();

					// Load Settings.
					$this->settings_init();
				}
			);

			add_action(
				'rest_api_init',
				function () {
					// Settings REST Init.
					$this->rest_api_init();
				}
			);
		}

		/**
		 * REST API Init.
		 *
		 * @return void
		 */
		abstract public function rest_api_init();

		/**
		 * Settings Init.
		 *
		 * @return void
		 */
		abstract public function settings_init();

		/**
		 * Load Settings Page.
		 *
		 * @return void
		 */
		abstract public function settings_page_init();

		/**
		 * Process Settings Actions.
		 *
		 * @return void
		 */
		abstract public function settings_actions();

		/**
		 * Process Single Settings action.
		 *
		 * @param string $current_action action key.
		 *
		 * @return void
		 */
		abstract public function process_actions( string $current_action );

		/**
		 * Set Parent Menu Slug
		 *
		 * @return string
		 */
		abstract public function parent_menu(): string;

		/**
		 * Get Parent Menu or Main Menu name
		 *
		 * @return string
		 */
		public function get_parent_menu(): string {
			return $this->parent_menu();
		}

		/**
		 * Set Parent Menu Title
		 *
		 * @return string
		 */
		abstract public function parent_menu_title(): string;

		/**
		 * Get Parent or Main Menu Title
		 *
		 * @return string menu title;
		 */
		public function get_parent_menu_title(): string {
			return $this->parent_menu_title();
		}

		/**
		 * Assign Parent Menu Icon
		 *
		 * @return string
		 */
		abstract public function menu_icon(): string;

		/**
		 * Get Main Menu Icon
		 *
		 * @return string menu icon;
		 */
		public function get_menu_icon(): string {
			return $this->menu_icon();
		}

		/**
		 * Assign Parent Menu Position
		 *
		 * @return string
		 */
		abstract public function menu_position(): string;

		/**
		 * Get Main Menu Position
		 *
		 * @return string menu position;
		 */
		public function get_menu_position(): string {
			return $this->menu_position();
		}

		/**
		 * Assign Settings Page Title.
		 *
		 * @return string Settings Page Title.
		 */
		abstract public function page_title(): string;

		/**
		 * Get Settings Page Title.
		 *
		 * @return string Get Settings Page Title.
		 */
		public function get_page_title(): string {
			return $this->page_title();
		}

		/**
		 * Assign Settings Page Menu Title.
		 *
		 * @return string Settings Page Menu Title.
		 */
		abstract public function menu_title(): string;

		/**
		 * Get Settings Page Menu Title.
		 *
		 * @return string
		 */
		public function get_menu_title(): string {
			return $this->menu_title();
		}

		/**
		 * Assign Settings Page ID.
		 *
		 * @return string Settings Page ID.
		 */
		abstract public function page_id(): string;

		/**
		 * Get Settings Page ID.
		 *
		 * @return string
		 */
		public function get_page_id(): string {
			return $this->page_id();
		}

		/**
		 * Assign Settings Page Showing Capability.
		 *
		 * @return string
		 */
		abstract public function capability(): string;

		/**
		 * Get Settings Page Showing Capability.
		 *
		 * @return string
		 */
		public function get_capability(): string {
			return $this->capability();
		}

		/**
		 * Add Menu Separator.
		 *
		 * @return bool
		 */
		public function add_menu_separator(): bool {
			return true;
		}

		/**
		 * Adding Main Admin Menu Separator
		 *
		 * @param numeric-string $position Separator Position.
		 * @param string         $separator_additional_class Separator Additional Class. Default: empty.
		 * @param string         $capability                 Menu Separator Capability. Default: manage_options.
		 *
		 * @return void
		 */
		private function admin_menu_separator( string $position, string $separator_additional_class = '', string $capability = 'manage_options' ): void {
			global $menu;

			if ( ! current_user_can( $capability ) ) {
				return;
			}

			if ( ! $this->add_menu_separator() ) {
				return;
			}

			/**
			 * We have to Override Global Variable name $menu to add menu separator.
			 */
			$menu[ $position ] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				'',
				'read',
				sprintf( 'separator-%s', strtolower( $separator_additional_class ) ),
				'',
				sprintf( 'wp-menu-separator %s', strtolower( $separator_additional_class ) ),
			);
			ksort( $menu );
		}

		/**
		 * Check if menu is main menu or submenu
		 *
		 * @return boolean
		 */
		public function is_submenu(): bool {
			return str_contains( $this->get_parent_slug(), '.php' );
		}

		/**
		 * Get Page ID.
		 *
		 * @return string
		 */
		private function get_page_slug(): string {
			return $this->get_page_id();
		}

		/**
		 * Get Current Page Slug.
		 *
		 * @return string
		 */
		public function get_current_page_slug(): string {
			return $this->current_page_slug;
		}

		/**
		 * Get parent page slug.
		 *
		 * @return string
		 */
		public function get_parent_slug(): string {
			return $this->get_parent_menu();
		}
	}
}
