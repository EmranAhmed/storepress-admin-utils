<?php
	
	namespace StorePress\AdminUtils;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	/**
	 * Admin Settings
	 *
	 * @package    StorePress
	 * @subpackage AdminUtils
	 * @name Settings
	 * @version    1.0
	 */
	if ( ! class_exists( '\StorePress\AdminUtils\Settings' ) ) {
		abstract class Settings extends Menu {
			
			/**
			 * @var string $fields_callback_fn_name_convention
			 */
			private string $fields_callback_fn_name_convention = 'add_%s_settings_fields';
			/**
			 * @var string $sidebar_callback_fn_name_convention
			 */
			private string $sidebar_callback_fn_name_convention = 'add_%s_settings_sidebar';
			/**
			 * @var string $page_callback_fn_name_convention
			 */
			private string $page_callback_fn_name_convention = 'add_%s_settings_page';
			/**
			 * @var array $options Store All Saved Options
			 */
			private array $options = array();
			
			/**
			 * @return string
			 */
			abstract public function settings_id(): string;
			
			/**
			 * @return string
			 */
			abstract public function plugin_file(): string;
			
			/**
			 * Show Settings in REST
			 * @return true
			 */
			public function show_in_rest(): bool {
				return true;
			}
			
			public function settings_init() {
				add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 20 );
				
				// GET: /wp-json/<page-id>/v1/settings
				if ( $this->show_in_rest() ) {
					add_action( 'rest_api_init', function () {
						// Settings.
						( new REST_API( $this ) )->register_routes();
					} );
				}
				
				add_action( 'plugin_action_links_' . plugin_basename( $this->get_plugin_file() ), array( $this, 'plugin_action_links' ) );
			}
			
			public function plugin_action_links( $links ): array {
				$action_links = array(
					'settings' => sprintf( '<a href="%1$s" aria-label="%2$s">%2$s</a>', esc_url( $this->get_settings_uri() ), esc_html__( 'Settings' ) ),
				);
				
				return array_merge( $action_links, $links );
			}
			
			/**
			 * Admin Scripts
			 * @return void
			 */
			public function register_admin_scripts() {
				
				if ( $this->is_admin_page() ) {
					$plugin_dir_url  = untrailingslashit( plugin_dir_url( $this->get_plugin_file() ) );
					$plugin_dir_path = untrailingslashit( plugin_dir_path( $this->get_plugin_file() ) );
					
					$script_src_url    = $plugin_dir_url . '/vendor/storepress/admin-utils/build/admin-settings.js';
					$style_src_url     = $plugin_dir_url . '/vendor/storepress/admin-utils/build/admin-settings.css';
					$script_asset_file = $plugin_dir_path . '/vendor/storepress/admin-utils/build/admin-settings.asset.php';
					$script_assets     = include $script_asset_file;
					
					wp_register_script( 'storepress-admin-settings', $script_src_url, $script_assets[ 'dependencies' ], $script_assets[ 'version' ], true );
					wp_register_style( 'storepress-admin-settings', $style_src_url, array(), $script_assets[ 'version' ] );
					wp_localize_script( 'storepress-admin-settings', 'StorePressAdminUtilsSettingsParams', $this->script_params() );
				}
			}
			
			/**
			 * @return void
			 */
			public function enqueue_admin_scripts() {
				wp_enqueue_script( 'storepress-admin-settings' );
				wp_enqueue_style( 'storepress-admin-settings' );
			}
			
			/**
			 * @return array
			 */
			public function script_params(): array {
				return array(
					'i18n_nav_switch_warning' => esc_html__( 'The changes you made will be lost if you navigate away from this page.' ),
					'i18n_reset_warning'      => esc_html__( 'Are you sure?' ),
				);
			}
			
			/**
			 * @return string
			 */
			public function i18n_reset_title(): string {
				return esc_html__( 'Reset All' );
			}
			
			/**
			 * @return array
			 */
			public function add_settings(): array {
				
				$message = esc_html__( 'not implemented. Must be overridden in subclass.' );
				$this->trigger_error( __METHOD__, $message );
				
				return array();
			}
			
			/**
			 * @return array
			 */
			final public function get_settings(): array {
				return $this->add_settings();
			}
			
			// used on ui template.
			
			/**
			 * @return void
			 */
			final public function display_sidebar() {
				$tab_sidebar = $this->get_tab_sidebar();
				
				if ( is_callable( $tab_sidebar ) ) {
					call_user_func( $tab_sidebar );
				} else {
					// load default sidebar
					$this->get_default_sidebar();
				}
			}
			
			/**
			 * @return callable|null
			 */
			private function get_tab_sidebar(): ?callable {
				$data = $this->get_tab();
				
				return $data[ 'sidebar_callback' ];
			}
			
			/**
			 * @return void
			 */
			public function get_default_sidebar() {
				$current_tab       = $this->get_current_tab();
				$callback_function = sprintf( $this->sidebar_callback_fn_name_convention, $current_tab );
				$message           = sprintf( __( 'not implemented. Must be overridden in subclass. Create "%s" method for "%s" tab sidebar.' ), $callback_function, $current_tab );
				$this->trigger_error( __METHOD__, $message );
			}
			
			// used on ui template.
			
			/**
			 * @return void
			 */
			final public function display_fields() {
				$fields_callback = $this->get_tab_fields_callback();
				$page_callback   = $this->get_tab_page_callback();
				$current_tab     = $this->get_current_tab();
				
				if ( is_callable( $page_callback ) ) {
					return;
				}
				
				$this->check_unique_field_ids();
				
				if ( is_callable( $fields_callback ) ) {
					$get_fields = call_user_func( $fields_callback );
					
					if ( is_array( $get_fields ) ) {
						
						settings_fields( $this->get_option_group_name() );
						
						$fields = new Fields( $get_fields, $this );
						$fields->display();
						
						$this->display_buttons();
					}
					
				} else {
					$fields_fn_name = sprintf( $this->fields_callback_fn_name_convention, $current_tab );
					$page_fn_name   = sprintf( $this->page_callback_fn_name_convention, $current_tab );
					$message        = sprintf( 'Should return fields array from "<strong>%s()</strong>". Or For custom page create "<strong>%s()</strong>"', $fields_fn_name, $page_fn_name );
					$this->trigger_error( '', $message );
				}
			}
			
			/**
			 * @return void
			 */
			public function display_buttons() {
				$submit_button = get_submit_button( null, 'primary large', 'submit', false, null );
				$reset_button  = $this->get_reset_button();
				echo sprintf( '<p class="submit">%s %s</p>', $submit_button, $reset_button );
			}
			
			/**
			 * @return string
			 */
			public function get_reset_button(): string {
				return sprintf( '<a href="%s" class="storepress-settings-reset-action-link button-link-delete">%s</a>', esc_url( $this->get_reset_uri() ), esc_html( $this->i18n_reset_title() ) );
			}
			
			/**
			 * @return callable|null
			 */
			private function get_tab_fields_callback(): ?callable {
				$data = $this->get_tab();
				
				return $data[ 'fields_callback' ];
			}
			
			/**
			 * @return callable|null
			 */
			private function get_tab_page_callback(): ?callable {
				$data = $this->get_tab();
				
				return $data[ 'page_callback' ];
			}
			
			// used on ui template.
			
			/**
			 * @return void
			 */
			final public function display_page() {
				$callback = $this->get_tab_page_callback();
				
				if ( is_callable( $callback ) ) {
					call_user_func( $callback );
				}
			}
			
			/**
			 * @return array
			 */
			final public function get_tabs(): array {
				$tabs = $this->get_settings();
				$navs = array();
				
				foreach ( $tabs as $key => $tab ) {
					if ( empty( $key ) ) {
						$key = $this->default_tab_name();
					}
					
					$item = array(
						'id'          => $key,
						'name'        => $tab,
						'hidden'      => false,
						'external'    => false,
						'icon'        => null,
						'css-classes' => array(),
						'sidebar'     => true,
						//'page_callback'    => null,
						//'fields_callback'  => null,
						//'sidebar_callback' => null,
					);
					
					if ( is_array( $tab ) ) {
						$navs[ $key ] = wp_parse_args( $tab, $item );
					} else {
						$navs[ $key ] = $item;
					}
					
					$page_callback    = array( $this, sprintf( $this->page_callback_fn_name_convention, $key ) );
					$fields_callback  = array( $this, sprintf( $this->fields_callback_fn_name_convention, $key ) );
					$sidebar_callback = array( $this, sprintf( $this->sidebar_callback_fn_name_convention, $key ) );
					
					$navs[ $key ][ 'buttons' ] = ! is_callable( $page_callback );
					
					$navs[ $key ][ 'page_callback' ]    = is_callable( $page_callback ) ? $page_callback : null;
					$navs[ $key ][ 'fields_callback' ]  = is_callable( $fields_callback ) ? $fields_callback : null;
					$navs[ $key ][ 'sidebar_callback' ] = is_callable( $sidebar_callback ) ? $sidebar_callback : null;
					
				}
				
				return $navs;
			}
			
			/***
			 * @return Field[]
			 */
			public function get_all_fields(): array {
				$tabs       = $this->get_tabs();
				$all_fields = array();
				
				foreach ( $tabs as $tab ) {
					
					$fields_callback = $tab[ 'fields_callback' ];
					
					if ( is_callable( $fields_callback ) ) {
						$fields = call_user_func( $fields_callback );
						foreach ( $fields as $field ) {
							if ( 'section' === $field[ 'type' ] ) {
								continue;
							}
							$_field = ( new Field( $field ) )->add_settings( $this );
							
							$all_fields[ $field[ 'id' ] ] = $_field;
							// $all_fields[ $field[ 'id' ] ] = $field;
						}
					}
				}
				
				return $all_fields;
			}
			
			/**
			 * @return void
			 */
			private function check_unique_field_ids() {
				$tabs = $this->get_tabs();
				
				$_field_keys = array();
				
				foreach ( $tabs as $tab ) {
					$tab_id          = $tab[ 'id' ];
					$fields_callback = $tab[ 'fields_callback' ];
					
					if ( is_callable( $fields_callback ) ) {
						$fields = call_user_func( $fields_callback );
						/**
						 * @var array $field
						 */
						foreach ( $fields as $field ) {
							if ( 'section' === $field[ 'type' ] ) {
								continue;
							}
							
							if ( in_array( $field[ 'id' ], $_field_keys ) ) {
								
								$fields_fn_name = sprintf( $this->fields_callback_fn_name_convention, $tab_id );
								$message        = sprintf( 'Duplicate field id "<strong>%s</strong>" found. Please use unique field id.', $field[ 'id' ] );
								
								$this->trigger_error( $fields_fn_name, $message );
								
							} else {
								$_field_keys[] = $field[ 'id' ];
							}
						}
					}
				}
			}
			
			
			// used on ui template.
			
			/**
			 * @return void
			 */
			final public function display_tabs() {
				echo implode( '', $this->get_navs() );
			}
			
			/**
			 * @return array
			 */
			private function get_navs(): array {
				
				$tabs = $this->get_tabs();
				
				$current_tab = $this->get_current_tab();
				
				$navs = array();
				/**
				 * @var array $tab
				 */
				foreach ( $tabs as $tab_id => $tab ) {
					
					if ( ! empty( $tab[ 'hidden' ] ) ) {
						continue;
					}
					
					$tab[ 'css-classes' ][] = 'nav-tab';
					$tab[ 'attributes' ]    = array();
					if ( $current_tab === $tab_id ) {
						$tab[ 'css-classes' ][]                = 'nav-tab-active';
						$tab[ 'attributes' ][ 'aria-current' ] = 'page';
					}
					
					$tab_url    = empty( $tab[ 'external' ] ) ? $this->get_tab_uri( $tab_id ) : $tab[ 'external' ];
					$tab_target = empty( $tab[ 'external' ] ) ? '_self' : '_blank';
					$icon       = empty( $tab[ 'icon' ] ) ? '' : sprintf( '<span class="%s"></span>', $tab[ 'icon' ] );
					$attributes = $tab[ 'attributes' ];
					
					$attrs = implode( ' ', array_map( function ( $key ) use ( $attributes ) {
						
						if ( in_array( $key, array( 'target', 'href', 'class' ) ) ) {
							return '';
						}
						
						if ( is_bool( $attributes[ $key ] ) ) {
							return $attributes[ $key ] ? $key : '';
						}
						
						return sprintf( '%s="%s"', $key, esc_attr( $attributes[ $key ] ) );
						
					}, array_keys( $attributes ) ) );
					
					$navs[] = sprintf( '<a %s target="%s" href="%s" class="%s">%s</span><span>%s</span></a>', $attrs, esc_attr( $tab_target ), esc_url( $tab_url ), esc_attr( implode( ' ', $tab[ 'css-classes' ] ) ), wp_kses_post( $icon ), esc_html( $tab[ 'name' ] ) );
				}
				
				return $navs;
			}
			
			// used on ui template.
			
			/**
			 * @return string
			 */
			final public function get_action_uri(): string {
				return $this->get_settings_uri();
			}
			
			// used on ui template.
			
			/**
			 * @return string
			 */
			final public function get_reset_uri(): string {
				return wp_nonce_url( $this->get_settings_uri( array( $this->action_query_args() => 'reset' ) ), $this->get_nonce() );
			}
			
			/**
			 * @return string
			 */
			final public function get_nonce(): string {
				$group = $this->get_option_group_name();
				
				return sprintf( '%s-options', $group );
			}
			
			/**
			 * @return string
			 */
			final public function get_option_group_name(): string {
				$page = $this->get_current_page_slug();
				$tab  = $this->get_current_tab();
				
				return sprintf( '%s-%s', $page, $tab );
			}
			
			/**
			 * @return string
			 */
			public function get_plugin_file(): string {
				return $this->plugin_file();
			}
			
			/**
			 * @return string
			 */
			public function get_settings_id(): string {
				return $this->settings_id();
			}
			
			// override for custom ui page
			
			/**
			 * @return void
			 */
			public function display_settings_page() {
				// Follow: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions
				include __DIR__ . '/templates/classic-template.php';
			}
			
			/**
			 * @return bool
			 */
			final public function is_valid_save(): bool {
				return isset( $_POST[ 'option_page' ] ) && ( $_POST[ 'option_page' ] === $this->get_option_group_name() );
			}
			
			/**
			 * @return bool
			 */
			final public function is_valid_action(): bool {
				
				$action_query = $this->action_query_args();
				$nonce        = $_REQUEST[ '_wpnonce' ] ?? null;
				
				return isset( $_REQUEST[ $action_query ] ) && wp_verify_nonce( $nonce, $this->get_nonce() );
			}
			
			/**
			 * @return string
			 */
			final public function get_current_action(): string {
				$action_query = $this->action_query_args();
				
				return empty( $_REQUEST[ $action_query ] ) ? '' : sanitize_title( wp_unslash( $_REQUEST[ $action_query ] ) ); // WPCS: input var okay, CSRF ok.
			}
			
			/**
			 * @return false|mixed|null
			 */
			public function get_options() {
				
				if ( ! empty( $this->options ) ) {
					return $this->options;
				}
				$this->options = get_option( $this->get_settings_id(), array() );
				
				return $this->options;
			}
			
			/**
			 * @return bool
			 */
			public function delete_options(): bool {
				return delete_option( $this->get_settings_id() );
			}
			
			/**
			 * @param array $data
			 *
			 * @return void
			 */
			final public function save_options( array $data ) {
				
				$old_data = $this->get_options();
				if ( ! empty( $old_data ) ) {
					$current_data = array_merge( $old_data, $data[ 'public' ] );
				} else {
					$current_data = $data[ 'public' ];
				}
				
				foreach ( $data[ 'private' ] as $key => $value ) {
					update_option( esc_attr( $key ), $value );
				}
				
				update_option( $this->get_settings_id(), $current_data );
			}
			
			/**
			 * @param string $field_id
			 * @param mixed  $default . Default null
			 *
			 * @return mixed|null
			 */
			public function get_option( string $field_id, $default = null ) {
				$field = $this->get_field( $field_id );
				
				return $field->get_value( $default );
			}
			
			/**
			 * @param string $group_id
			 * @param string $field_id
			 * @param mixed  $default . Default: null
			 *
			 * @return mixed|null
			 */
			public function get_group_option( string $group_id, string $field_id, $default = null ) {
				$field = $this->get_field( $group_id );
				
				return $field->get_group_value( $field_id, $default );
			}
			
			// Current tab fields
			
			/***
			 * @return Field[]
			 */
			private function get_available_fields(): array {
				$field_cb         = $this->get_tab_fields_callback();
				$available_fields = array();
				if ( is_callable( $field_cb ) ) {
					$fields = call_user_func( $field_cb );
					/**
					 * @var array $field
					 */
					foreach ( $fields as $field ) {
						if ( 'section' !== $field[ 'type' ] ) {
							$_field                             = ( new Field( $field ) )->add_settings( $this );
							$available_fields[ $field[ 'id' ] ] = $_field;
						}
					}
				}
				
				return $available_fields;
			}
			
			// Current tab
			
			/**
			 * @param string $field_id
			 *
			 * @return Field|null
			 */
			private function get_available_field( string $field_id ): ?Field {
				$fields = $this->get_available_fields();
				
				return $fields[ $field_id ] ?? null;
			}
			
			/**
			 * @param string $field_id
			 *
			 * @return Field|null
			 */
			private function get_field( $field_id ): ?Field {
				$fields = $this->get_all_fields();
				
				return $fields[ $field_id ] ?? null;
			}
			
			
			/***
			 *
			 * @param array $_post
			 *
			 * @return array{ public: array, private: array }
			 * @todo Add Fields Sanitization
			 */
			private function sanitize_fields( array $_post ): array {
				
				$fields = $this->get_available_fields();
				
				$public_data  = array();
				$private_data = array();
				
				// @TODO: We should move sanitization to Field
				foreach ( $fields as $key => $field ) {
					
					if ( $field->is_private() ) {
						$id                  = $field->get_private_name();
						$private_data[ $id ] = sanitize_text_field( $_post[ $key ] );
						continue;
					}
					
					$type = $field->get_type();
					
					switch ( $type ) {
						case 'text':
						case 'number':
						case 'regular-text':
						case 'small-text':
						case 'tiny-text':
						case 'large-text':
						case 'radio':
							$public_data[ $key ] = sanitize_text_field( $_post[ $key ] );
							break;
						case 'email':
							$public_data[ $key ] = sanitize_email( $_post[ $key ] );
							break;
						case 'url':
							$public_data[ $key ] = sanitize_url( $_post[ $key ] );
							break;
						case 'color':
							$public_data[ $key ] = sanitize_hex_color( $_post[ $key ] );
							break;
						case 'checkbox':
							
							$options = $field->get_options();
							
							if ( count( $options ) > 1 ) {
								$public_data[ $key ] = map_deep( $_post[ $key ], 'sanitize_text_field' );
							} else {
								if ( isset( $_post[ $key ] ) ) {
									$public_data[ $key ] = sanitize_text_field( $_post[ $key ] );
								} else {
									$public_data[ $key ] = 'no';
								}
							}
							break;
						case 'select':
							
							$is_multiple = $field->has_attribute( 'multiple' );;
							
							if ( $is_multiple ) {
								$public_data[ $key ] = map_deep( $_post[ $key ], 'sanitize_text_field' );
							} else {
								$public_data[ $key ] = sanitize_text_field( $_post[ $key ] );
							}
							break;
						case 'group':
							$group_fields = $field->get_group_fields();
							
							foreach ( $group_fields as $group_field ) {
								$group_field_id          = $group_field->get_id();
								$group_field_type        = $group_field->get_type();
								$group_field_options     = $group_field->get_options();
								$group_field_is_multiple = $group_field->has_attribute( 'multiple' );
								
								if ( 'checkbox' === $group_field_type && ! isset( $_post[ $key ][ $group_field_id ] ) ) {
									$_post[ $key ][ $group_field_id ] = ( count( $group_field_options ) > 1 ) ? array() : 'no';
								}
								
								if ( 'select' === $group_field_type && ! isset( $_post[ $key ][ $group_field_id ] ) ) {
									$_post[ $key ][ $group_field_id ] = $group_field_is_multiple ? array() : '';
								}
							}
							
							$public_data[ $key ] = map_deep( $_post[ $key ], 'sanitize_text_field' );
							
							break;
					}
				}
				
				return array( 'public' => $public_data, 'private' => $private_data );
			}
			
			/**
			 * @return void
			 */
			public function save_settings_page() {
				if ( $this->is_valid_save() ) {
					
					check_admin_referer( $this->get_nonce() );
					
					$_post = $_POST[ $this->get_settings_id() ];
					
					$data = $this->sanitize_fields( $_post );
					
					$this->save_options( $data );
					
					wp_safe_redirect( add_query_arg( 'settings-updated', 'true', $this->get_action_uri() ) );
					exit;
				}
			}
			
			/**
			 * @return void
			 */
			public function action_settings_page() {
				if ( $this->is_valid_action() ) {
					switch ( $this->get_current_action() ) {
						case 'reset':
							$this->delete_options();
							break;
					}
					
					wp_safe_redirect( add_query_arg( 'deleted', 'true', $this->get_action_uri() ) );
					exit;
				}
			}
			
			/**
			 * @return void
			 */
			public function settings_page_init() {
				$this->enqueue_admin_scripts();
				$this->action_settings_page();
				$this->save_settings_page();
				$this->settings_messages();
			}
			
			/**
			 * @return void
			 */
			public function settings_messages() {
				if ( isset( $_GET[ 'settings-updated' ] ) ) {
					$this->add_settings_message( __( 'Settings Saved' ) );
				}
				if ( isset( $_GET[ 'deleted' ] ) ) {
					$this->add_settings_message( __( 'Settings Reset' ) );
				}
			}
			
			/**
			 * used on ui template.
			 * @return void
			 */
			final public function display_settings_messages() {
				settings_errors( $this->get_current_page_slug() );
			}
			
			/**
			 * @param string $message  Message
			 * @param string $type     Message type. Optional. Message type, controls HTML class. Possible values include 'error',
			 *                         'success', 'warning', 'info', 'updated'. Default: 'updated'.
			 *
			 * @return Settings
			 */
			final public function add_settings_message( string $message, string $type = 'updated' ): Settings {
				add_settings_error( $this->get_current_page_slug(), sprintf( '%s_message', $this->get_settings_id() ), $message, $type );
				
				return $this;
			}
			
			/**
			 * @return string Parent Menu Slug
			 */
			public function parent_menu(): string {
				return 'storepress';
			}
			
			public function capability(): string {
				return 'manage_options';
			}
			
			public function menu_position(): string {
				return '45';
			}
			
			public function menu_icon(): string {
				return 'dashicons-admin-settings';
			}
			
			public function default_tab_name(): string {
				return 'general';
			}
			
			public function get_current_tab(): string {
				$default_tab_query_key = $this->default_tab_name();
				
				return empty( $_GET[ 'tab' ] ) ? $default_tab_query_key : sanitize_title( wp_unslash( $_GET[ 'tab' ] ) ); // WPCS: input var okay, CSRF ok.
			}
			
			final public function get_tab( $tab_id = '' ) {
				$tabs = $this->get_tabs();
				
				$_tab_id = empty( $tab_id ) ? $this->get_current_tab() : $tab_id;
				
				return $tabs[ $_tab_id ];
			}
			
			final public function has_save_button(): bool {
				$data = $this->get_tab();
				
				return ! empty( $data[ 'buttons' ] );
				
			}
			
			final public function has_sidebar(): bool {
				$data = $this->get_tab();
				
				return ! empty( $data[ 'sidebar' ] );
			}
			
			/**
			 * @param $tab_id
			 *
			 * @return string
			 */
			public function get_tab_uri( $tab_id ): string {
				return $this->get_settings_uri( array( 'tab' => $tab_id ) );
			}
			
			/**
			 * @param array $extra
			 *
			 * @return string
			 */
			public function get_settings_uri( array $extra = array() ): string {
				
				$admin_url = $this->is_submenu() ? $this->get_parent_slug() : 'admin.php';
				$args      = $this->get_uri_args( $extra );
				
				return admin_url( add_query_arg( $args, $admin_url ) );
			}
			
			/**
			 * @param array $extra
			 *
			 * @return array
			 */
			public function get_uri_args( array $extra = array() ): array {
				
				$current_tab = $this->get_current_tab();
				
				$args = array(
					'page' => $this->get_current_page_slug(),
				);
				
				if ( ! empty( $current_tab ) ) {
					$args[ 'tab' ] = $current_tab;
				}
				
				return wp_parse_args( $extra, $args );
			}
			
			/**
			 * @return bool
			 */
			public function is_admin_page(): bool {
				return ( is_admin() && isset( $_GET[ 'page' ] ) && $this->get_current_page_slug() === $_GET[ 'page' ] );
			}
		}
	}