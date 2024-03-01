<?php
	
	namespace StorePress\AdminUtils;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	/**
	 * Admin Menu
	 *
	 * @package    StorePress/AdminUtils
	 * @name Menu
	 * @version    1.0
	 */
	if ( ! class_exists( '\StorePress\AdminUtils\Menu' ) ) {
		
		abstract class Menu {
			
			private static int   $position          = 2;
			private static array $slug_usages       = array();
			private string       $current_page_slug = '';
			
			/**
			 * Menu constructor.
			 */
			public function __construct() {
				
				add_action( 'admin_menu', function () {
					global $submenu, $menu;
					
					// Bail if submenu
					if ( $this->is_submenu() ) {
						return;
					}
					
					// Create unique Menu.
					foreach ( $menu as $m ) {
						if ( $m[ 2 ] === $this->get_parent_slug() ) {
							return;
						}
					}
					
					$capability = $this->get_capability();
					
					$separator_menu_position = sprintf( '%s.%s', $this->get_menu_position(), self::$position );
					$this->admin_menu_separator( $separator_menu_position, $this->get_parent_slug(), $capability );
					self::$position ++;
					
					$menu_position = sprintf( '%s.%s', $this->get_menu_position(), self::$position );
					add_menu_page( $this->get_parent_menu_title(), $this->get_parent_menu_title(), $capability, $this->get_parent_slug(), '', $this->get_menu_icon(), $menu_position );
					self::$position ++;
				},          9 );
				
				add_action( 'admin_menu', function () {
					global $submenu, $menu;
					
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
					
					$settings_page = add_submenu_page( $parent_slug, $this->page_title(), $this->menu_title(), $capability, $this->get_current_page_slug(), function () {
						
						if ( ! current_user_can( $this->get_capability() ) ) {
							return;
						}
						
						$this->display_settings_page();
					} );
					
					add_action( 'load-' . $settings_page, function () {
						
						if ( ! current_user_can( $this->get_capability() ) ) {
							return;
						}
						
						$this->settings_page_init();
					} );
				},          12 );
				
				add_action( 'admin_menu', function () {
					
					global $submenu, $menu;
					
					if ( $this->is_submenu() ) {
						return;
					}
					
					$slug = $this->get_parent_slug();
					
					if ( ! isset( $submenu[ $slug ] ) ) {
						return;
					}
					
					unset( $submenu[ $slug ][ 0 ] );
				},          60 );
				
				add_action( 'admin_init', function () {
					
					// Settings Actions
					$this->settings_actions();
					
					// Settings Init
					$this->settings_init();
				} );
				
				add_action( 'rest_api_init', function () {
					// Settings REST Init
					$this->rest_api_init();
				} );
			}
			
			abstract public function rest_api_init();
			
			abstract public function settings_init();
			
			abstract public function settings_page_init();
			
			abstract public function settings_actions();
			
			abstract public function process_actions( $current_action );
			
			/**
			 * Parent Menu Slug
			 *
			 * @return string
			 */
			abstract public function parent_menu(): string;
			
			/**
			 * Get Parent or Main Menu name
			 *
			 * @return string menu unique name;
			 */
			public function get_parent_menu(): string {
				return $this->parent_menu();
			}
			
			/**
			 * Parent Menu and Page Title
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
			 * Parent Menu Icon
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
			 * Parent Menu Position
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
			 * @return string
			 */
			abstract public function page_title(): string;
			
			/**
			 * @return string
			 */
			public function get_page_title(): string {
				return $this->page_title();
			}
			
			/**
			 * @return string
			 */
			abstract public function menu_title(): string;
			
			/**
			 * @return string
			 */
			public function get_menu_title(): string {
				return $this->menu_title();
			}
			
			/**
			 * @return string
			 */
			abstract public function page_id(): string;
			
			/**
			 * @return string
			 */
			public function get_page_id(): string {
				return $this->page_id();
			}
			
			/**
			 * @return string
			 */
			abstract public function capability(): string;
			
			/**
			 * @return string
			 */
			public function get_capability(): string {
				return $this->capability();
			}
			
			/**
			 * @return bool
			 */
			public function add_menu_separator(): bool {
				return true;
			}
			
			/**
			 * Adding Main Admin Menu Separator
			 *
			 * @param numeric-string $position                   Separator Position
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
				
				$menu[ $position ] = array(
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
			 * @return string
			 */
			private function get_page_slug(): string {
				return $this->get_page_id();
			}
			
			/**
			 * @return string
			 */
			public function get_current_page_slug(): string {
				return $this->current_page_slug;
			}
			
			/**
			 * @return string
			 */
			public function get_parent_slug(): string {
				return $this->get_parent_menu();
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
					
					$message = wp_kses( $message, array(
						'a' => array( 'href' ),
						'br',
						'code',
						'em',
						'strong',
					),                  array( 'http', 'https' ) );
					
					trigger_error( $message );
				}
			}
		}
	}
