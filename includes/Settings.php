<?php
	/**
	 * Admin Settings Class file.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Settings' ) ) {

	/**
	 * Admin Settings Class
	 *
	 * @name Settings
	 * @see Menu
	 */
	abstract class Settings extends Menu {

		/**
		 * Fields callback function name convention.
		 *
		 * @var string $fields_callback_fn_name_convention
		 */
		private string $fields_callback_fn_name_convention = 'add_%s_settings_fields';
		/**
		 * Sidebar callback function name convention.
		 *
		 * @var string $sidebar_callback_fn_name_convention
		 */
		private string $sidebar_callback_fn_name_convention = 'add_%s_settings_sidebar';
		/**
		 * Page callback function name convention.
		 *
		 * @var string $page_callback_fn_name_convention
		 */
		private string $page_callback_fn_name_convention = 'add_%s_settings_page';
		/**
		 * Store All Saved Options
		 *
		 * @var array<string, mixed> $options
		 */
		private array $options = array();

		/**
		 * Add Setting ID.
		 *
		 * @return string
		 */
		abstract public function settings_id(): string;

		/**
		 * Plugin File name.
		 *
		 * @return string
		 */
		abstract public function plugin_file(): string;

		/**
		 * Show Settings in REST. If empty or false rest api will disable.
		 *
		 * @return string|bool
		 * @example GET: /wp-json/<page-id>/<rest-api-version>/settings
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
		 * Control displaying reset button.
		 *
		 * @return bool
		 */
		public function show_reset_button(): bool {
			return true;
		}

		/**
		 * Settings Hook
		 *
		 * @return void
		 */
		final public function settings_init() {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 20 );
			add_filter( 'plugin_action_links_' . plugin_basename( $this->get_plugin_file() ), array( $this, 'plugin_action_links' ) );
		}

		/**
		 * Setting Action
		 *
		 * @return void
		 */
		final public function settings_actions() {

			if ( ! isset( $_REQUEST['action'] ) || ! isset( $_GET['page'] ) || $_GET['page'] !== $this->get_current_page_slug() ) {
				return;
			}

			check_admin_referer( $this->get_nonce_action() );

			$plugin_page    = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			$current_action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );

			$has_plugin_page = ! $this->is_empty_string( $plugin_page );
			$has_action      = ! $this->is_empty_string( $current_action );

			if ( $has_plugin_page && $has_action && $plugin_page === $this->get_current_page_slug() ) {
				$this->process_actions( $current_action );
			}
		}

		/**
		 * Init rest api.
		 *
		 * @example GET /wp-json/<page-id>/<rest-api-version>/settings
		 * @return void
		 */
		public function rest_api_init() {
			( new REST_API( $this ) )->register_routes();
		}

		/**
		 * Plugin page plugin action link.
		 *
		 * @param string[] $links Plugin action available links.
		 *
		 * @return string[]
		 */
		public function plugin_action_links( array $links ): array {

			$strings = $this->localize_strings();

			$action_links = sprintf( '<a href="%1$s" aria-label="%2$s">%2$s</a>', esc_url( $this->get_settings_uri() ), esc_html( $strings['settings_link_text'] ) );

			$links[] = $action_links;

			return $links;
		}

		/**
		 * Register admin Scripts
		 *
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

				wp_register_script( 'storepress-admin-settings', $script_src_url, $script_assets['dependencies'], $script_assets['version'], true );
				wp_register_style( 'storepress-admin-settings', $style_src_url, array(), $script_assets['version'] );
				wp_localize_script( 'storepress-admin-settings', 'StorePressAdminUtilsSettingsParams', $this->localize_strings() );
			}
		}

		/**
		 * Enqueue Scripts.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'storepress-admin-settings' );
			wp_enqueue_style( 'storepress-admin-settings' );
		}

		/**
		 * Translated Strings.
		 *
		 * @abstract
		 *
		 * @return array{
		 *     'unsaved_warning_text': string,
		 *     'reset_warning_text': string,
		 *     'reset_button_text': string,
		 *     'settings_nav_label_text': string,
		 *     'settings_link_text': string,
		 *     'settings_error_message_text': string,
		 *     'settings_updated_message_text': string,
		 *     'settings_deleted_message_text': string
		 * }
		 * @example array{
		 *     unsaved_warning_text: string,
		 *     reset_warning_text: string,
		 *     reset_button_text: string,
		 *     settings_nav_label_text: string,
		 *     settings_link_text: string,
		 *     settings_error_message_text: string,
		 *     settings_updated_message_text: string,
		 *     settings_deleted_message_text:string
		 *     }
		 */
		public function localize_strings(): array {

			/* translators: %s: Method name. */
			$message = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __METHOD__ );
			wp_trigger_error( __METHOD__, $message );

			return array(
				'unsaved_warning_text'          => 'The changes you made will be lost if you navigate away from this page.',
				'reset_warning_text'            => 'Are you sure to reset?',
				'reset_button_text'             => 'Reset All',
				'settings_nav_label_text'       => 'Secondary menu',
				'settings_link_text'            => 'Settings',
				'settings_error_message_text'   => 'Settings not saved',
				'settings_updated_message_text' => 'Settings Saved',
				'settings_deleted_message_text' => 'Settings Reset',
			);
		}

		/**
		 * Get localize string by string key. Previously added on localize_strings()
		 *
		 * @param string $string_key Localized string key.
		 * @see localize_strings()
		 * @return string
		 */
		public function get_localized_string( string $string_key ): string {
			$strings = $this->localize_strings();

			return $strings[ $string_key ] ?? '';
		}

		/**
		 * Add Settings.
		 *
		 * @abstract
		 * @return array<string, mixed>
		 */
		public function add_settings(): array {

			/* translators: %s: Method name. */
			$message = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __METHOD__ );
			wp_trigger_error( __METHOD__, $message );
			return array();
		}

		/**
		 * Get settings.
		 *
		 * @return array<string, mixed>
		 */
		final public function get_settings(): array {
			return $this->add_settings();
		}

		/**
		 * Display Sidebar. Used on UI template.
		 *
		 * @return void
		 */
		final public function display_sidebar() {
			$tab_sidebar = $this->get_tab_sidebar();
			// Load sidebar based on callback.
			if ( is_callable( $tab_sidebar ) ) {
				call_user_func( $tab_sidebar );
			} else {
				// Load default sidebar.
				$this->get_default_sidebar();
			}
		}

		/**
		 * Get tab sidebar callback.
		 *
		 * @return callable|null
		 */
		private function get_tab_sidebar(): ?callable {
			$data = $this->get_tab();

			return $data['sidebar_callback'];
		}

		/**
		 * Get default sidebar.
		 *
		 * @abstract
		 * @return void
		 */
		public function get_default_sidebar() {
			$current_tab       = $this->get_current_tab();
			$callback_function = sprintf( $this->sidebar_callback_fn_name_convention, $current_tab );

			/* translators: %s: Method name. */
			$message  = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __METHOD__ );
			$message .= sprintf( 'Create "%1$s" method for "%2$s" tab sidebar.', $callback_function, $current_tab );
			wp_trigger_error( __METHOD__, $message );
		}

		/**
		 * Display Fields. Used on UI template.
		 *
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
				wp_trigger_error( '', $message );
			}
		}

		/**
		 * Display action buttons.
		 *
		 * @return void
		 */
		public function display_buttons() {
			$submit_button      = get_submit_button( '', 'primary large', 'submit', false, '' );
			$reset_button       = $this->get_reset_button();
			$allowed_input_html = $this->get_kses_allowed_input_html();
			printf( '<p class="submit">%s %s</p>', wp_kses( $submit_button, $allowed_input_html ), wp_kses_post( $reset_button ) );
		}

		/**
		 * Get settings reset button.
		 *
		 * @return string
		 */
		public function get_reset_button(): string {
			if ( ! $this->show_reset_button() ) {
				return '';
			}

			$strings = $this->localize_strings();

			return sprintf( '<a href="%s" class="storepress-settings-reset-action-link button-link-delete">%s</a>', esc_url( $this->get_reset_uri() ), esc_html( $strings['reset_button_text'] ) );
		}

		/**
		 * Get tab field callable function.
		 *
		 * @return callable|null
		 */
		private function get_tab_fields_callback(): ?callable {
			$data = $this->get_tab();

			return $data['fields_callback'];
		}

		/**
		 * Get tab page callable function.
		 *
		 * @return callable|null
		 */
		private function get_tab_page_callback(): ?callable {
			$data = $this->get_tab();

			return $data['page_callback'];
		}

		/**
		 * Display page. Used on UI Template.
		 *
		 * @return void
		 */
		final public function display_page() {
			$callback = $this->get_tab_page_callback();

			if ( is_callable( $callback ) ) {
				call_user_func( $callback );
			}
		}

		/**
		 * Get tabs.
		 *
		 * @return array<int|string, mixed>
		 */
		final public function get_tabs(): array {
			$tabs = $this->get_settings();
			$navs = array();

			$first_key = array_key_first( $tabs );

			foreach ( $tabs as $key => $tab ) {
				if ( is_string( $first_key ) && $this->is_empty_string( $first_key ) ) {
					$key = $this->default_tab_name();
				}

				if ( 0 === $first_key ) {
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
					/**
					 * More item.
					 *
					 * @example:
					 * 'page_callback'    => null,
					 * 'fields_callback'  => null,
					 * 'sidebar_callback' => null,
					 */
				);

				if ( is_array( $tab ) ) {
					$navs[ $key ] = wp_parse_args( $tab, $item );
				} else {
					$navs[ $key ] = $item;
				}

				$page_callback    = array( $this, sprintf( $this->page_callback_fn_name_convention, $key ) );
				$fields_callback  = array( $this, sprintf( $this->fields_callback_fn_name_convention, $key ) );
				$sidebar_callback = array( $this, sprintf( $this->sidebar_callback_fn_name_convention, $key ) );

				$navs[ $key ]['buttons'] = ! is_callable( $page_callback );

				$navs[ $key ]['page_callback']    = is_callable( $page_callback ) ? $page_callback : null;
				$navs[ $key ]['fields_callback']  = is_callable( $fields_callback ) ? $fields_callback : null;
				$navs[ $key ]['sidebar_callback'] = is_callable( $sidebar_callback ) ? $sidebar_callback : null;
			}

			return $navs;
		}

		/**
		 * Get Fields
		 *
		 * @return Field[]
		 */
		public function get_all_fields(): array {
			$tabs = $this->get_tabs();

			$all_fields = array();

			foreach ( $tabs as $tab ) {

				$fields_callback = $tab['fields_callback'];

				if ( is_callable( $fields_callback ) ) {
					$fields = call_user_func( $fields_callback );
					foreach ( $fields as $field ) {
						if ( 'section' === $field['type'] ) {
							continue;
						}
						$_field = ( new Field( $field ) )->add_settings( $this );

						$all_fields[ $field['id'] ] = $_field;
					}
				}
			}

			return $all_fields;
		}

		/**
		 * Check unique field ids.
		 *
		 * @return void
		 */
		private function check_unique_field_ids() {
			$tabs = $this->get_tabs();

			$_field_keys = array();

			foreach ( $tabs as $tab ) {
				$tab_id          = $tab['id'];
				$fields_callback = $tab['fields_callback'];

				if ( is_callable( $fields_callback ) ) {
					$fields = call_user_func( $fields_callback );
					/**
					 * Fields.
					 *
					 * @var array<string, mixed> $field
					 */
					foreach ( $fields as $field ) {
						if ( 'section' === $field['type'] ) {
							continue;
						}

						if ( in_array( $field['id'], $_field_keys, true ) ) {

							$fields_fn_name = sprintf( $this->fields_callback_fn_name_convention, $tab_id );
							$message        = sprintf( 'Duplicate field id "<strong>%s</strong>" found. Please use unique field id.', $field['id'] );

							wp_trigger_error( $fields_fn_name, $message );

						} else {
							$_field_keys[] = $field['id'];
						}
					}
				}
			}
		}


		// used on ui template.

		/**
		 * Display tabs. Used on UI template.
		 *
		 * @return void
		 */
		final public function display_tabs() {
			echo wp_kses_post( implode( '', $this->get_navs() ) );
		}

		/**
		 * Get navigations.
		 *
		 * @return string[]
		 */
		private function get_navs(): array {

			$tabs = $this->get_tabs();

			$current_tab = $this->get_current_tab();

			$navs = array();
			/**
			 * Available tabs.
			 *
			 * @var array<int|string, mixed> $tab
			 */


			foreach ( $tabs as $tab_id => $tab ) {

				if ( true === $tab['hidden'] ) {
					continue;
				}

				$tab['css-classes'][] = 'nav-tab';
				$tab['attributes']    = array();
				if ( $current_tab === $tab_id ) {
					$tab['css-classes'][]              = 'nav-tab-active';
					$tab['attributes']['aria-current'] = 'page';
				}

				$tab_url    = false === $tab['external'] ? $this->get_tab_uri( $tab_id ) : $tab['external'];
				$tab_target = false === $tab['external'] ? '_self' : '_blank';
				$icon       = is_null( $tab['icon'] ) ? '' : sprintf( '<span class="%s"></span>', $tab['icon'] );
				$attributes = $tab['attributes'];

				$attrs = implode(
					' ',
					array_map(
						function ( $key ) use ( $attributes ) {

							if ( is_bool( $attributes[ $key ] ) ) {
									return $attributes[ $key ] ? $key : '';
							}

							return sprintf( '%s="%s"', $key, esc_attr( $attributes[ $key ] ) );
						},
						array_keys( $attributes )
					)
				);

				$navs[] = sprintf( '<a %s target="%s" href="%s" class="%s">%s</span><span>%s</span></a>', $attrs, esc_attr( $tab_target ), esc_url( $tab_url ), esc_attr( implode( ' ', $tab['css-classes'] ) ), wp_kses_post( $icon ), esc_html( $tab['name'] ) );
			}

			return $navs;
		}

		/**
		 * Get action uri. used on settings form.
		 *
		 * @param array<string, mixed> $extra Extra arguments.
		 * @return string
		 */
		final public function get_action_uri( array $extra = array() ): string {
			return $this->get_settings_uri( $extra );
		}

		/**
		 * Get reset uri. used on ui template.
		 *
		 * @return string
		 */
		final public function get_reset_uri(): string {
			return wp_nonce_url( $this->get_settings_uri( array( 'action' => 'reset' ) ), $this->get_nonce_action() );
		}

		/**
		 * Get nonce action.
		 *
		 * @return string
		 */
		final public function get_nonce_action(): string {
			$group = $this->get_option_group_name();

			return sprintf( '%s-options', $group );
		}

		/**
		 * Get option group name for ui template.
		 *
		 * @return string
		 */
		final public function get_option_group_name(): string {
			$page = $this->get_current_page_slug();
			$tab  = $this->get_current_tab();

			return sprintf( '%s-%s', $page, $tab );
		}

		/**
		 * Get plugin file.
		 *
		 * @return string
		 */
		public function get_plugin_file(): string {
			return $this->plugin_file();
		}

		/**
		 * Get settings ID.
		 *
		 * @return string
		 */
		public function get_settings_id(): string {
			return $this->settings_id();
		}

		/**
		 * Settings template. Can override for custom ui page.
		 *
		 * @see https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions
		 * @return void
		 */
		public function display_settings_page() {
			include_once __DIR__ . '/templates/classic-template.php';
		}

		/**
		 * Process actions.
		 *
		 * @param string $current_action Current requested action.
		 *
		 * @return void
		 */
		public function process_actions( string $current_action ) {

			if ( 'update' === $current_action ) {
				$this->process_action_update();
			}

			if ( 'reset' === $current_action ) {
				$this->process_action_reset();
			}
		}

		/**
		 * Process update settings.
		 *
		 * @see wp_removable_query_args()
		 * @return void
		 */
		public function process_action_update() {

			check_admin_referer( $this->get_nonce_action() );

			if ( ! isset( $_POST[ $this->get_settings_id() ] ) ) {
				wp_safe_redirect(
					$this->get_action_uri(
						array(
							'message' => 'error',
						)
					)
				);
				exit;
			}

			$_post = map_deep( wp_unslash( $_POST[ $this->get_settings_id() ] ), 'sanitize_text_field' );

			$data = $this->sanitize_fields( $_post );

			$this->update_options( $data );

			wp_safe_redirect(
				$this->get_action_uri(
					array( 'message' => 'updated' )
				)
			);
			exit;
		}

		/**
		 * Process reset action.
		 *
		 * @see wp_removable_query_args()
		 * @return void
		 */
		public function process_action_reset() {

			check_admin_referer( $this->get_nonce_action() );

			$this->delete_options();

			wp_safe_redirect(
				$this->get_action_uri(
					array( 'message' => 'deleted' )
				)
			);
			exit;
		}

		/**
		 * Settings messages
		 *
		 * @see process_action_update()
		 * @see process_action_reset()
		 * @see wp_removable_query_args()
		 * @return void
		 */
		public function settings_messages() {

			// We are just checking message request from uri redirect.
			if ( ! isset( $_GET['message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$strings = $this->localize_strings();

			$message = sanitize_text_field( $_GET['message'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 'updated' === $message ) {
				$this->add_settings_message( $strings['settings_updated_message_text'] );
			}
			if ( 'deleted' === $message ) {
				$this->add_settings_message( $strings['settings_deleted_message_text'] );
			}
			if ( 'error' === $message ) {
				$this->add_settings_message( $strings['settings_error_message_text'], 'error' );
			}
		}

		/**
		 * Get All Saved Options
		 *
		 * @param array<string, mixed> $default_value Default Value. Default: empty array.
		 *
		 * @return bool|array<string, mixed>|null
		 */
		public function get_options( array $default_value = array() ) {

			if ( ! $this->is_empty_array( $this->options ) ) {
				return $this->options;
			}
			$this->options = get_option( $this->get_settings_id(), $default_value );

			return $this->options;
		}

		/**
		 * Delete Option
		 *
		 * @return bool
		 */
		final public function delete_options(): bool {
			return delete_option( $this->get_settings_id() );
		}

		/**
		 * Update Option
		 *
		 * @param array<string, mixed> $data Updated data.
		 *
		 * @return void
		 */
		private function update_options( array $data ) {

			$old_data = $this->get_options();

			if ( ! $this->is_empty_array( $old_data ) ) {
				$current_data = array_merge( $old_data, $data['public'] );
			} else {
				$current_data = $data['public'];
			}

			foreach ( $data['private'] as $key => $value ) {
				update_option( esc_attr( $key ), $value );
			}

			update_option( $this->get_settings_id(), $current_data );
		}

		/**
		 * Get Option
		 *
		 * @param string $field_id Option field id.
		 * @param mixed  $default_value Pass default value. Default is null.
		 *
		 * @return mixed|null
		 */
		public function get_option( string $field_id, $default_value = null ) {
			$field = $this->get_field( $field_id );

			return $field->get_value( $default_value );
		}

		/**
		 * Get Group Option
		 *
		 * @param string $group_id Group Id.
		 * @param string $field_id Field Id.
		 * @param mixed  $default_value Default: null.
		 *
		 * @return mixed|null
		 */
		public function get_group_option( string $group_id, string $field_id, $default_value = null ) {
			$field = $this->get_field( $group_id );

			return $field->get_group_value( $field_id, $default_value );
		}

		/**
		 * Get Available fields.
		 *
		 * @return Field[]
		 */
		private function get_available_fields(): array {
			$field_cb         = $this->get_tab_fields_callback();
			$available_fields = array();
			if ( is_callable( $field_cb ) ) {
				$fields = call_user_func( $field_cb );
				/**
				 * Field
				 *
				 * @var array<string, mixed> $field
				 */
				foreach ( $fields as $field ) {
					if ( 'section' !== $field['type'] ) {
						$_field                           = ( new Field( $field ) )->add_settings( $this );
						$available_fields[ $field['id'] ] = $_field;
					}
				}
			}

			return $available_fields;
		}

		/**
		 * Get available field.
		 *
		 * @param string $field_id Field id.
		 *
		 * @return Field|null
		 * @phpstan-ignore method.unused
		 */
		private function get_available_field( string $field_id ): ?Field {
			$fields = $this->get_available_fields();

			return $fields[ $field_id ] ?? null;
		}

		/**
		 * Get field.
		 *
		 * @param string $field_id Field id.
		 *
		 * @return Field|null
		 */
		private function get_field( string $field_id ): ?Field {
			$fields = $this->get_all_fields();

			return $fields[ $field_id ] ?? null;
		}

		/**
		 * Sanitize fields.
		 *
		 * @param array<string, mixed> $_post global post.
		 *
		 * @return array{ public: array<string, mixed>, private: array<string, mixed> }
		 */
		private function sanitize_fields( array $_post ): array {

			$fields = $this->get_available_fields();

			$public_data  = array();
			$private_data = array();

			foreach ( $fields as $key => $field ) {

				$sanitize_callback = $field->get_sanitize_callback();
				$type              = $field->get_type();
				$options           = $field->get_options();

				if ( $field->is_private() ) {
					$id                  = $field->get_private_name();
					$private_data[ $id ] = map_deep( $_post[ $key ], $sanitize_callback );
					continue;
				}

				switch ( $type ) {
					case 'checkbox':
						// Add default checkbox value.
						if ( ! isset( $_post[ $key ] ) ) {
							$_post[ $key ] = ( count( $options ) > 0 ) ? array() : 'no';
						}

						$public_data[ $key ] = map_deep( $_post[ $key ], $sanitize_callback );

						break;
					case 'group':
						$group_fields = $field->get_group_fields();

						foreach ( $group_fields as $group_field ) {
							$group_field_id          = $group_field->get_id();
							$group_field_type        = $group_field->get_type();
							$group_field_options     = $group_field->get_options();
							$group_sanitize_callback = $field->get_sanitize_callback();

							// Add default checkbox value.
							if ( 'checkbox' === $group_field_type ) {
								if ( ! isset( $_post[ $key ][ $group_field_id ] ) ) {
									$_post[ $key ][ $group_field_id ] = ( count( $group_field_options ) > 0 ) ? array() : 'no';
								}
							}

							$public_data[ $key ][ $group_field_id ] = map_deep( $_post[ $key ][ $group_field_id ], $group_sanitize_callback );
						}
						break;

					default:
						$public_data[ $key ] = map_deep( $_post[ $key ], $sanitize_callback );
						break;
				}
			}

			return array(
				'public'  => $public_data,
				'private' => $private_data,
			);
		}

		/**
		 * Settings page init.
		 *
		 * @return void
		 */
		public function settings_page_init() {
			$this->enqueue_scripts();
			$this->settings_messages();
		}

		/**
		 * Display settings message. Used on ui template.
		 *
		 * @return void
		 */
		final public function display_settings_messages() {
			settings_errors( $this->get_current_page_slug() );
		}

		/**
		 * Add settings message.
		 *
		 * @param string $message  Message.
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
			return 'dashicons-admin-settings';
		}

		/**
		 * Default tab name.
		 *
		 * @return string
		 */
		public function default_tab_name(): string {
			return 'general';
		}

		/**
		 * Get current tab.
		 *
		 * @return string
		 */
		final public function get_current_tab(): string {
			$default_tab_query_key = $this->default_tab_name();

			$available_tab_keys = array_keys( $this->get_tabs() );

			$tab_query_key = in_array( $default_tab_query_key, $available_tab_keys, true ) ? $default_tab_query_key : (string) $available_tab_keys[0];

			return ! isset( $_GET['tab'] ) ? sanitize_title( $tab_query_key ) : sanitize_title( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Get tab.
		 *
		 * @param string $tab_id tab id.
		 *
		 * @return array<string, mixed>
		 */
		final public function get_tab( string $tab_id = '' ): array {
			$tabs = $this->get_tabs();

			$_tab_id = $this->is_empty_string( $tab_id ) ? $this->get_current_tab() : $tab_id;

			return $tabs[ $_tab_id ] ?? array(
				'page_callback' => function () {
					echo '<div class="notice error"><p>Settings Tab is not available.</p></div>';
				},
			);
		}

		/**
		 * Have save button.
		 *
		 * @return bool
		 */
		final public function has_save_button(): bool {
			$data = $this->get_tab();

			return true === $data['buttons'];
		}

		/**
		 * Has sidebar.
		 *
		 * @return bool
		 */
		final public function has_sidebar(): bool {
			$data = $this->get_tab();

			return true === $data['sidebar'];
		}

		/**
		 * Get Tab URI.
		 *
		 * @param string $tab_id Tab id.
		 *
		 * @return string
		 */
		public function get_tab_uri( string $tab_id ): string {
			return $this->get_settings_uri( array( 'tab' => $tab_id ) );
		}

		/**
		 * Get Settings uri.
		 *
		 * @param array<string, mixed> $extra Extra arguments for uri.
		 *
		 * @return string
		 */
		public function get_settings_uri( array $extra = array() ): string {

			$admin_url = $this->is_submenu() ? $this->get_parent_slug() : 'admin.php';
			$args      = $this->get_uri_args( $extra );

			return admin_url( add_query_arg( $args, $admin_url ) );
		}

		/**
		 * Get Settings URI Arguments.
		 *
		 * @param array<string, mixed> $extra Extra arguments.
		 *
		 * @return array<string, mixed>
		 */
		public function get_uri_args( array $extra = array() ): array {

			$current_tab = $this->get_current_tab();

			$args = array(
				'page' => $this->get_current_page_slug(),
			);

			if ( ! $this->is_empty_string( $current_tab ) ) {
				$args['tab'] = $current_tab;
			}

			return wp_parse_args( $extra, $args );
		}

		/**
		 * Check is admin page.
		 *
		 * @return bool
		 */
		public function is_admin_page(): bool {
			// We have to check is valid current page.
			return ( is_admin() && isset( $_GET['page'] ) && $this->get_current_page_slug() === $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}
}
