<?php
	/**
	 * Admin Settings Menu Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Menu' ) ) {
	/**
	 * Admin Settings Abstract Menu Class.
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
		 * @var array<string, int> $slug_usages Slug used in menu.
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
					// Bail if submenu.
					if ( $this->is_submenu() ) {
						return;
					}

					// Create Unique Parent Menu.
					$parent_menu_url = (bool) trim( menu_page_url( $this->get_parent_slug(), false ) );

					if ( $parent_menu_url ) {
						return;
					}

					$capability = $this->get_capability();

					$separator_menu_position = (float) sprintf( '%d.%d', $this->get_menu_position(), self::$position );
					$this->admin_menu_separator( $separator_menu_position, $this->get_parent_slug(), $capability );
					self::$position++;

					$menu_position = (float) sprintf( '%d.%d', $this->get_menu_position(), self::$position );
					add_menu_page(
						$this->get_parent_menu_title(),
						$this->get_parent_menu_title(),
						$capability,
						$this->get_parent_slug(),
						'__return_false',
						$this->get_menu_icon(),
						$menu_position
					);
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

					if ( $this->is_submenu() ) {
						return;
					}

					// Remove duplicate menu.
					$menu_slug = $this->get_parent_slug();

					remove_submenu_page( $menu_slug, $menu_slug );
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

			add_action(
				'admin_enqueue_scripts',
				function () {
					wp_add_inline_style( 'admin-menu', '#adminmenu li.menu-top.wp-menu-separator { min-height: auto; }' );
				}
			);

			if ( ! $this->is_empty_string( $this->show_in_rest() ) ) {

				add_action(
					'admin_enqueue_scripts',
					function () {

						wp_add_inline_script(
							'wp-data',
							sprintf(
								'wp.domReady(function(){
							wp.data.dispatch( "core" ).addEntities( [{
								name: "%s",
								kind: "%s",
								baseURL: "%s",
								label: "%s"
							}] );
							});',
								$this->core_data_entity_name(),
								$this->core_data_entity_kind(),
								sprintf( '/%s/%s', $this->show_in_rest(), $this->rest_api_base() ),
								$this->get_page_title()
							)
						);
					}
				);
			}
		}

		/**
		 * REST API Init.
		 *
		 * @return void
		 */
		abstract public function rest_api_init(): void;

		/**
		 * Settings Init.
		 *
		 * @return void
		 */
		abstract public function settings_init(): void;

		/**
		 * Load Settings Page.
		 *
		 * @return void
		 */
		abstract public function settings_page_init(): void;

		/**
		 * Process Settings Actions.
		 *
		 * @return void
		 */
		abstract public function settings_actions(): void;

		/**
		 * Process Single Settings action.
		 *
		 * @param string $current_action action key.
		 *
		 * @return void
		 */
		abstract public function process_actions( string $current_action ): void;

		/**
		 * Settings template. Can override for custom ui page.
		 *
		 * @see https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions
		 * @return void
		 */
		abstract public function display_settings_page(): void;

		/**
		 * Parent menu slug.
		 *
		 * @return string Parent Menu Slug
		 */
		public function parent_menu(): string {
			return 'storepress';
		}

		/**
		 * Get settings capability.
		 *
		 * @return string
		 */
		public function capability(): string {
			return 'manage_options';
		}

		/**
		 * Menu position.
		 *
		 * @return int
		 */
		public function menu_position(): int {
			return 45;
		}

		/**
		 * Menu Icon.
		 *
		 * @return string
		 */
		public function menu_icon(): string {
			return 'dashicons-admin-generic';
		}

		/**
		 * Show Settings in REST. If empty or false rest api will disable.
		 *
		 * @return string|bool
		 * @example GET: /wp-json/<page-id>/<rest-api-version>/<rest-api-base>
		 * @example GET: /wp-json/<page-id>/v1/settings
		 */
		public function show_in_rest() {
			return sprintf( '%s/%s', $this->get_page_id(), $this->rest_api_version() );
		}

		/**
		 * Rest API version
		 *
		 * @return string
		 */
		public function rest_api_version(): string {
			return 'v1';
		}

		/**
		 * Rest API Base
		 *
		 * @return string
		 */
		public function rest_api_base(): string {
			return 'settings';
		}

		/**
		 * WP Core Data Entity Kind.
		 *
		 * @return string
		 */
		public function core_data_entity_kind(): string {
			return $this->is_submenu() ? $this->show_in_rest() : $this->get_parent_menu();
		}

		/**
		 * WP Core Data Entity Name.
		 *
		 * @return string
		 */
		public function core_data_entity_name(): string {
			return $this->is_submenu() ? $this->rest_api_base() : $this->get_page_id();
		}

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
		 * Get Main Menu Icon
		 *
		 * @return string menu icon;
		 */
		public function get_menu_icon(): string {
			return $this->menu_icon();
		}

		/**
		 * Get Main Menu Position
		 *
		 * @return int menu position;
		 */
		public function get_menu_position(): int {
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
		 * Get Settings Page Visibility Capability.
		 *
		 * @return string
		 */
		public function get_capability(): string {
			return $this->capability();
		}

		/**
		 * REST API Response Capability for GET Method.
		 *
		 * @return string
		 */
		public function rest_get_capability(): string {
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
		 * @param float  $position Separator Position.
		 * @param string $separator_additional_class Separator Additional Class. Default: empty.
		 * @param string $capability                 Menu Separator Capability. Default: manage_options.
		 *
		 * @return void
		 */
		private function admin_menu_separator( float $position, string $separator_additional_class = '', string $capability = 'manage_options' ): void {

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

		/**
		 * Check if menu is main menu or submenu
		 *
		 * @return boolean
		 */
		public function is_submenu(): bool {
			return false !== strpos( $this->get_parent_slug(), '.php' );
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
