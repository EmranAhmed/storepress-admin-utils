<?php
	/**
	 * Abstract Settings Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Abstracts;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Factory\SettingsFactory;
	use StorePress\AdminUtils\Services\Internal\Settings\Field;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractSettings' ) ) {

	/**
	 * Abstract base class for WordPress settings pages with tabs, fields, and REST API.
	 *
	 * @name AbstractSettings
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractSettings extends AbstractAdminMenu {

		// =========================================================================
		// Properties
		// =========================================================================

		/**
		 * Fields callback method name convention.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		protected string $fields_callback_fn_name_convention = 'add_%s_settings_fields';

		/**
		 * Sidebar callback method name convention.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		protected string $sidebar_callback_fn_name_convention = 'add_%s_settings_sidebar';

		/**
		 * Page callback method name convention.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		protected string $page_callback_fn_name_convention = 'add_%s_settings_page';

		/**
		 * Cached saved options.
		 *
		 * @var array<string, mixed>
		 *
		 * @since 1.0.0
		 */
		protected array $options = array();

		// =========================================================================
		// Abstract Methods
		// =========================================================================

		/**
		 * Get settings ID used as the option name in wp_options.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		abstract public function settings_id(): string;

		/**
		 * Factory instance for creating settings components.
		 *
		 * @var SettingsFactory
		 *
		 * @since 3.1.0
		 */
		protected SettingsFactory $factory;

		// =========================================================================
		// Constructor and Initialization Methods
		// =========================================================================

		/**
		 * Constructor.
		 *
		 * @param SettingsFactory|null $factory Factory class name. Default SettingsFactory.
		 *
		 * @since 1.0.0
		 */
		public function __construct( ?SettingsFactory $factory = null ) {

			$this->factory = $factory ?? SettingsFactory::instance();

			$this->register_rest_api();
			parent::__construct();
		}

		/**
		 * Get the settings factory instance.
		 *
		 * @return SettingsFactory
		 *
		 * @since 3.1.0
		 */
		public function get_factory(): SettingsFactory {
			return $this->factory;
		}

		/**
		 * Register admin scripts and plugin action links.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function page_init(): void {

			// Register admin scripts on admin_enqueue_scripts hook.
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 20 );

			// Add settings link to plugin action links.
			foreach ( $this->get_plugins_basename() as $basename ) {
				add_filter( 'plugin_action_links_' . $basename, array( $this, 'plugin_action_links' ), 15 );
			}

			$this->settings_actions();
		}

		/**
		 * Enqueue scripts and display settings messages on page load.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function page_loaded(): void {
			$this->enqueue_scripts();
			$this->settings_messages();
		}

		// =========================================================================
		// Settings Configuration Methods
		// =========================================================================

		/**
		 * Public accessor for the settings ID.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 *
		 * @see self::settings_id()
		 */
		public function get_settings_id(): string {
			return $this->settings_id();
		}

		/**
		 * Define settings tabs. Override in subclass.
		 *
		 * @return array<string, mixed>
		 *
		 * @since 1.0.0
		 */
		public function add_settings(): array {
			$this->subclass_should_implement( __FUNCTION__ );

			return array();
		}

		/**
		 * Get settings tabs defined in add_settings().
		 *
		 * @return array<string, mixed>
		 *
		 * @since 1.0.0
		 *
		 * @see self::add_settings()
		 */
		final public function get_settings(): array {
			return $this->add_settings();
		}

		/**
		 * Whether to show the reset button. Default true.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function show_reset_button(): bool {
			return true;
		}

		/**
		 * Translatable strings. Override in subclass for localization.
		 *
		 * @return array{
		 *     'unsaved_warning_text': string,
		 *     'reset_warning_text': string,
		 *     'reset_button_text': string,
		 *     'settings_link_text': string,
		 *     'settings_error_message_text': string,
		 *     'settings_updated_message_text': string,
		 *     'settings_deleted_message_text': string,
		 *     'settings_tab_not_available_text': string
		 * }
		 *
		 * @since 1.0.0
		 */
		public function localize_strings(): array {
			$this->subclass_should_implement( __FUNCTION__ );

			return array(
				'unsaved_warning_text'            => 'The changes you made will be lost if you navigate away from this page.',
				'reset_warning_text'              => 'Are you sure to reset?',
				'reset_button_text'               => 'Reset All',
				'settings_link_text'              => 'Settings',
				'settings_error_message_text'     => 'Settings not saved',
				'settings_updated_message_text'   => 'Settings Saved',
				'settings_deleted_message_text'   => 'Settings Reset',
				'settings_tab_not_available_text' => 'Settings Tab is not available.',
			);
		}

		/**
		 * Get localized string by key.
		 *
		 * @param string $string_key The localized string key.
		 *
		 * @return string Empty string if not found.
		 *
		 * @since 1.0.0
		 */
		public function get_localized_string( string $string_key ): string {
			$strings = $this->localize_strings();

			return $strings[ $string_key ] ?? '';
		}

		/**
		 * Additional allowed HTML tags for field output. Override to customize.
		 *
		 * @return array<string, mixed>
		 *
		 * @since 1.0.0
		 */
		public function allowed_tags(): array {
			return array();
		}

		// =========================================================================
		// Tab Methods
		// =========================================================================

		/**
		 * Get default tab name. Default 'general'.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function default_tab_name(): string {
			return 'general';
		}

		/**
		 * Get current active tab from URL query or default.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		final public function get_current_tab(): string {
			$default_tab_query_key = $this->default_tab_name();

			$available_tab_keys = array_keys( $this->get_tabs() );

			$tab_query_key = in_array( $default_tab_query_key, $available_tab_keys, true ) ? $default_tab_query_key : (string) $available_tab_keys[0];

			$tab = $this->http_get_var( 'tab', sanitize_title( $tab_query_key ) );

			return sanitize_title( wp_unslash( $tab ) );
		}

		/**
		 * Get all tabs configuration with callbacks.
		 *
		 * @return array<int|string, mixed>
		 *
		 * @since 1.0.0
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
					'id'            => $key,
					'name'          => $tab,
					'hidden'        => false,
					'external'      => false,
					'icon'          => null,
					'css-classes'   => array(),
					'sidebar'       => true,
					'sidebar_width' => 20,
					// page_callback, fields_callback, sidebar_callback are added below.
				);

				if ( is_array( $tab ) ) {
					$navs[ $key ] = wp_parse_args( $tab, $item );
				} else {
					$navs[ $key ] = $item;
				}

				if ( is_numeric( $navs[ $key ]['sidebar'] ) ) {
					$navs[ $key ]['sidebar_width'] = absint( $navs[ $key ]['sidebar'] );
					$navs[ $key ]['sidebar']       = true;
				}

				$page_callback    = array( $this, $this->get_page_callback_fn_name( $key ) );
				$fields_callback  = array( $this, $this->get_fields_callback_fn_name( $key ) );
				$sidebar_callback = array( $this, $this->get_sidebar_callback_fn_name( $key ) );

				$navs[ $key ]['buttons'] = ! is_callable( $page_callback );

				$navs[ $key ]['page_callback']    = is_callable( $page_callback ) ? $page_callback : null;
				$navs[ $key ]['fields_callback']  = is_callable( $fields_callback ) ? $fields_callback : null;
				$navs[ $key ]['sidebar_callback'] = is_callable( $sidebar_callback ) ? $sidebar_callback : null;
			}

			return $navs;
		}

		/**
		 * Get the expected page callback method name for a given tab.
		 *
		 * Returns the method name following the convention (e.g. `add_{tab}_settings_page`).
		 *
		 * @param string $tab_id Tab identifier.
		 *
		 * @return string Method name, e.g. `add_general_settings_page`.
		 *
		 * @since 1.0.0
		 */
		public function get_page_callback_fn_name( string $tab_id ): string {
			return sprintf( $this->page_callback_fn_name_convention, $this->convert_to_snake_key( $tab_id ) );
		}

		/**
		 * Get the expected fields callback method name for a given tab.
		 *
		 * Returns the method name following the convention (e.g. `add_{tab}_settings_fields`).
		 *
		 * @param string $tab_id Tab identifier.
		 *
		 * @return string Method name, e.g. `add_general_settings_fields`.
		 *
		 * @since 1.0.0
		 */
		public function get_fields_callback_fn_name( string $tab_id ): string {
			return sprintf( $this->fields_callback_fn_name_convention, $this->convert_to_snake_key( $tab_id ) );
		}

		/**
		 * Get the expected sidebar callback method name for a given tab.
		 *
		 * Returns the method name following the convention (e.g. `add_{tab}_settings_sidebar`).
		 *
		 * @param string $tab_id Tab identifier.
		 *
		 * @return string Method name, e.g. `add_general_settings_sidebar`.
		 *
		 * @since 1.0.0
		 */
		public function get_sidebar_callback_fn_name( string $tab_id ): string {
			return sprintf( $this->sidebar_callback_fn_name_convention, $this->convert_to_snake_key( $tab_id ) );
		}

		/**
		 * Get a single tab configuration by ID, or the current tab if empty.
		 *
		 * @param string $tab_id Optional. Tab identifier. Default empty string.
		 *
		 * @return array<string, mixed>
		 *
		 * @since 1.0.0
		 */
		final public function get_tab( string $tab_id = '' ): array {
			$tabs = $this->get_tabs();

			$_tab_id = $this->is_empty_string( $tab_id ) ? $this->get_current_tab() : $tab_id;

			return $tabs[ $_tab_id ] ?? array(
				'page_callback' => function () {
					printf( '<div class="notice error"><p>%s</p></div>', esc_html( $this->get_localized_string( 'settings_tab_not_available_text' ) ) );
				},
			);
		}

		/**
		 * Check if current tab has save button.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		final public function has_save_button(): bool {
			$data = $this->get_tab();

			return true === $data['buttons'];
		}

		/**
		 * Check if current tab has sidebar.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		final public function has_sidebar(): bool {
			$data = $this->get_tab();

			return true === $data['sidebar'];
		}

		/**
		 * Get current tab sidebar width percentage.
		 *
		 * @return int
		 *
		 * @since 1.0.0
		 */
		final public function get_sidebar_width(): int {
			$data = $this->get_tab();

			return absint( $data['sidebar_width'] );
		}

		/**
		 * Get sidebar width CSS variable string. Empty if no sidebar.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		final public function get_sidebar_width_css(): string {
			if ( $this->has_sidebar() ) {
				return sprintf( '--storepress-settings-sidebar-width: %d%%', $this->get_sidebar_width() );
			}

			return '';
		}

		/**
		 * Get tab page callback.
		 *
		 * @return callable|null
		 *
		 * @since 1.0.0
		 */
		private function get_tab_page_callback(): ?callable {
			$data = $this->get_tab();

			return $data['page_callback'];
		}

		/**
		 * Get tab fields callback.
		 *
		 * @return callable|null
		 *
		 * @since 1.0.0
		 */
		private function get_tab_fields_callback(): ?callable {
			$data = $this->get_tab();

			return $data['fields_callback'];
		}

		/**
		 * Get tab sidebar callback.
		 *
		 * @return callable|null
		 *
		 * @since 1.0.0
		 */
		private function get_tab_sidebar(): ?callable {
			$data = $this->get_tab();

			return $data['sidebar_callback'];
		}

		// =========================================================================
		// Field Methods
		// =========================================================================

		/**
		 * Get all fields from all tabs indexed by field ID.
		 *
		 * @return Field[]
		 *
		 * @since 1.0.0
		 */
		public function get_all_fields(): array {
			$tabs = $this->get_tabs();

			$all_fields = array();

			foreach ( $tabs as $tab ) {

				$fields_callback = $tab['fields_callback'];

				if ( is_callable( $fields_callback ) ) {
					$fields = $fields_callback();

					$current_fields = $this->get_factory()->create_fields( $this, $fields )->get_fields();

					foreach ( $current_fields as $field ) {
						$all_fields[ $field->get_id() ] = $field;
					}
				}
			}

			return $all_fields;
		}

		/**
		 * Get field by ID from all tabs.
		 *
		 * @param string $field_id The field ID.
		 *
		 * @return Field|null
		 *
		 * @since 1.0.0
		 */
		public function get_field( string $field_id ): ?Field {
			$fields = $this->get_all_fields();

			return $fields[ $field_id ] ?? null;
		}

		/**
		 * Get available fields for current tab.
		 *
		 * @return Field[]
		 *
		 * @since 1.0.0
		 */
		public function get_available_fields(): array {
			$field_cb         = $this->get_tab_fields_callback();
			$available_fields = array();
			if ( is_callable( $field_cb ) ) {
				$fields           = $field_cb();
				$available_fields = $this->get_factory()->create_fields( $this, $fields )->get_fields();
			}

			return $available_fields;
		}

		/**
		 * Get available field by ID for current tab.
		 *
		 * @param string $field_id The field ID.
		 *
		 * @return Field|null
		 *
		 * @since 1.0.0
		 */
		public function get_available_field( string $field_id ): ?Field {
			$fields = $this->get_available_fields();

			return $fields[ $field_id ] ?? null;
		}

		/**
		 * Get all unique registered field types across tabs.
		 *
		 * @return string[]
		 *
		 * @since 1.0.0
		 */
		public function get_registered_field_types(): array {
			$tabs = $this->get_tabs();

			$all_types = array();

			foreach ( $tabs as $tab ) {

				$fields_callback = $tab['fields_callback'];

				if ( is_callable( $fields_callback ) ) {
					$fields = call_user_func( $fields_callback );
					foreach ( $fields as $field ) {
						$all_types[] = $field['type'];
					}
				}
			}

			return array_unique( $all_types );
		}

		/**
		 * Check if a field type is registered.
		 *
		 * @param string $field_type The field type to check.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function has_field_type( string $field_type ): bool {
			$types = $this->get_registered_field_types();

			return in_array( $field_type, $types, true );
		}

		/**
		 * Check for duplicate field IDs and trigger error if found.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		private function check_unique_field_ids(): void {
			$tabs = $this->get_tabs();

			$_field_keys = array();

			foreach ( $tabs as $tab ) {
				$tab_id          = $tab['id'];
				$fields_callback = $tab['fields_callback'];

				if ( is_callable( $fields_callback ) ) {
					$fields = $fields_callback();
					/* @var array<string, mixed> $field */
					foreach ( $fields as $field ) {
						if ( 'section' === $field['type'] ) {
							continue;
						}

						if ( in_array( $field['id'], $_field_keys, true ) ) {

							$fields_fn_name = $this->get_fields_callback_fn_name( $tab_id );
							$message        = sprintf( 'Duplicate field id "<strong>%s</strong>" found. Please use unique field id.', $field['id'] );

							wp_trigger_error( $fields_fn_name, $message );

						} else {
							$_field_keys[] = $field['id'];
						}
					}
				}
			}
		}

		// =========================================================================
		// Field ID Helper Methods
		// =========================================================================

		/**
		 * Get generated field HTML ID attribute.
		 *
		 * @param string     $field_id  The field ID.
		 * @param int|string $option_id Optional. Default empty string.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_field_id( string $field_id, $option_id = '' ): string {
			$string_option_id = (string) $option_id;
			return $this->is_empty_string( $string_option_id ) ? sprintf( '%s', $field_id ) : sprintf( '%s__%s', $field_id, $option_id );
		}

		/**
		 * Get field CSS selector.
		 *
		 * @param string     $field_id  The field ID.
		 * @param int|string $option_id Optional. Default empty string.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_field_selector( string $field_id, $option_id = '' ): string {
			return sprintf( '#%s', $this->get_field_id( $field_id, $option_id ) );
		}

		/**
		 * Get generated group field HTML ID attribute.
		 *
		 * @param string     $group_id  The group ID.
		 * @param string     $field_id  The field ID.
		 * @param int|string $option_id Optional. Default empty string.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_group_field_id( string $group_id, string $field_id, $option_id = '' ): string {
			$string_option_id = (string) $option_id;
			return $this->is_empty_string( $string_option_id ) ? sprintf( '%s__%s__group', $group_id, $field_id ) : sprintf( '%s__%s__%s__group', $group_id, $field_id, $option_id );
		}

		/**
		 * Get group field CSS selector.
		 *
		 * @param string     $group_id  The group ID.
		 * @param string     $field_id  The field ID.
		 * @param int|string $option_id Optional. Default empty string.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_group_field_selector( string $group_id, string $field_id, $option_id = '' ): string {
			return sprintf( '#%s', $this->get_group_field_id( $group_id, $field_id, $option_id ) );
		}

		// =========================================================================
		// Display/UI Methods
		// =========================================================================

		/**
		 * Display settings page template. Override for custom UI.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function render(): void {
			include_once $this->get_package_templates_path() . '/classic-template.php';
		}

		/**
		 * Display tabs navigation. Used in UI template.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function display_tabs(): void {
			echo wp_kses_post( implode( '', $this->get_navs() ) );
		}

		/**
		 * Get navigation HTML items.
		 *
		 * @return string[]
		 *
		 * @since 1.0.0
		 */
		private function get_navs(): array {
			$tabs = $this->get_tabs();

			$current_tab = $this->get_current_tab();

			$navs = array();

			/* @var array<int|string, mixed> $tab */
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

				// @TODO: Update with common script. // $this->>get_html_attributes()

				$navs[] = sprintf( '<a %s target="%s" href="%s" class="%s">%s</span><span>%s</span></a>', $attrs, esc_attr( $tab_target ), esc_url( $tab_url ), esc_attr( implode( ' ', $tab['css-classes'] ) ), wp_kses_post( $icon ), esc_html( $tab['name'] ) );
			}

			return $navs;
		}

		/**
		 * Display settings fields. Used in UI template.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function display_fields(): void {

			$fields_callback = $this->get_tab_fields_callback();
			$page_callback   = $this->get_tab_page_callback();
			$current_tab     = $this->get_current_tab();

			if ( is_callable( $page_callback ) ) {
				return;
			}

			$this->check_unique_field_ids();

			if ( is_callable( $fields_callback ) ) {
				$get_fields = $fields_callback();

				if ( is_array( $get_fields ) ) {

					settings_fields( $this->get_option_group_name() );

					// @TODO Get Fields.
					$fields = $this->get_factory()->create_fields( $this, $get_fields );

					$fields->display();

					$this->display_buttons();
				}
			} else {
				$classes             = array();
				$fields_fn_name      = $this->get_fields_callback_fn_name( $current_tab );
				$page_fn_name        = $this->get_page_callback_fn_name( $current_tab );
				$class_relative_path = $this->get_class_relative_path( $this );
				$classes[]           = $class_relative_path;

				$parent_class_relative_path = '';
				if ( $this->has_parent_class( $this ) ) {
					$parent_class_relative_path = $this->get_parent_class_relative_path( $this );
					$classes[]                  = $parent_class_relative_path;
				}

				$classes = array_map(
					static function ( string $class_file ) {
						return sprintf( 'in <strong>%s</strong> file.', $class_file );
					},
					$classes
				);

				$classes_files = implode( '<br /> or ', $classes );

				$message = sprintf( 'Should return fields array from "<strong>%s</strong>". Or For custom page create "<strong>%s</strong>"<br /> %s<br /><br />', $fields_fn_name, $page_fn_name, $classes_files );
				wp_trigger_error( '', $message );
			}
		}

		/**
		 * Display custom page content. Used in UI template.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function display_page(): void {
			$callback = $this->get_tab_page_callback();

			if ( is_callable( $callback ) ) {
				$callback();
			}
		}

		/**
		 * Display sidebar content. Used in UI template.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function display_sidebar(): void {
			$tab_sidebar = $this->get_tab_sidebar();
			// Load sidebar based on callback.
			if ( is_callable( $tab_sidebar ) ) {
				$tab_sidebar();
			} else {
				// Load default sidebar.
				$this->get_default_sidebar();
			}
		}

		/**
		 * Default sidebar content. Override or create tab-specific sidebar method.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function get_default_sidebar(): void {
			$current_tab       = $this->get_current_tab();
			$callback_function = $this->get_sidebar_callback_fn_name( $current_tab );

			/* translators: %s: Method name. */
			$message  = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __FUNCTION__ );
			$message .= sprintf( '<br />Create "<strong>%1$s</strong>" method for "<strong>%2$s</strong>" tab sidebar in "<strong>%3$s</strong>". <br /><br />', $callback_function, $current_tab, $this->get_class_relative_path( $this ) );
			wp_trigger_error( '', $message );
		}

		/**
		 * Display submit and reset action buttons.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function display_buttons(): void {
			$submit_button      = get_submit_button( '', 'primary large', 'submit', false, '' );
			$reset_button       = $this->get_reset_button();
			$allowed_input_html = $this->get_kses_allowed_input_html();
			printf( '<p class="submit">%s %s</p>', wp_kses( $submit_button, $allowed_input_html ), wp_kses_post( $reset_button ) );
		}

		/**
		 * Get reset button HTML. Empty string if hidden.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_reset_button(): string {
			if ( ! $this->show_reset_button() ) {
				return '';
			}

			$button_text = $this->get_localized_string( 'reset_button_text' );

			return sprintf( '<a href="%s" class="storepress-settings-reset-action-link button-link-delete">%s</a>', esc_url( $this->get_reset_uri() ), esc_html( $button_text ) );
		}

		// =========================================================================
		// URL/URI Methods
		// =========================================================================

		/**
		 * Get settings URI query arguments.
		 *
		 * @param array<string, mixed> $extra Optional. Additional arguments. Default empty array.
		 *
		 * @return array<string, mixed>
		 *
		 * @since 1.0.0
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
		 * Get full admin URL for the settings page.
		 *
		 * @param array<string, mixed> $extra Optional. Additional query arguments. Default empty array.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_settings_uri( array $extra = array() ): string {
			$admin_url = $this->is_submenu() ? $this->get_menu_slug() : 'admin.php';
			$args      = $this->get_uri_args( $extra );

			return admin_url( add_query_arg( $args, $admin_url ) );
		}

		/**
		 * Get tab URI.
		 *
		 * @param string $tab_id The tab ID.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_tab_uri( string $tab_id ): string {
			return $this->get_settings_uri( array( 'tab' => $tab_id ) );
		}

		/**
		 * Get form action URI.
		 *
		 * @param array<string, mixed> $extra Optional. Additional arguments. Default empty array.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		final public function get_action_uri( array $extra = array() ): string {
			return $this->get_settings_uri( $extra );
		}

		/**
		 * Get reset URI with nonce.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		final public function get_reset_uri(): string {
			return wp_nonce_url( $this->get_settings_uri( array( 'action' => 'reset' ) ), $this->get_nonce_action() );
		}

		/**
		 * Get nonce action string.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		final public function get_nonce_action(): string {
			$group = $this->get_option_group_name();

			return sprintf( '%s-options', $group );
		}

		/**
		 * Get option group name for settings_fields().
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		final public function get_option_group_name(): string {
			$page = $this->get_current_page_slug();
			$tab  = $this->get_current_tab();

			return sprintf( '%s-%s', $page, $tab );
		}

		// =========================================================================
		// Action Processing Methods
		// =========================================================================

		/**
		 * Handle settings form update and reset actions.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function settings_actions(): void {

			// Bail if this is not an admin page.
			if ( $this->http_get_var( 'page' ) !== $this->get_current_page_slug() ) {
				return;
			}

			// Bail if this is not an action.
			if ( ! $this->http_request_var( 'action', false ) ) {
				return;
			}

			check_admin_referer( $this->get_nonce_action() );

			$plugin_page    = sanitize_text_field( wp_unslash( $this->http_get_var( 'page' ) ) );
			$current_action = sanitize_text_field( wp_unslash( $this->http_request_var( 'action' ) ) );

			$has_plugin_page = ! $this->is_empty_string( $plugin_page );
			$has_action      = ! $this->is_empty_string( $current_action );

			if ( $has_plugin_page && $has_action && $plugin_page === $this->get_current_page_slug() ) {
				$this->process_actions( $current_action );
			}
		}

		/**
		 * Process form actions (update or reset).
		 *
		 * @param string $current_action The action to process.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function process_actions( string $current_action ): void {
			if ( 'update' === $current_action ) {
				$this->process_action_update();
			}

			if ( 'reset' === $current_action ) {
				$this->process_action_reset();
			}

			/**
			 * Fires after a settings form action has been processed.
			 *
			 * @param string           $current_action The action that was processed ('update' or 'reset').
			 * @param AbstractSettings $settings           The current settings instance.
			 *
			 * @since 1.0.0
			 */
			do_action( 'storepress_admin_utils_services_settings_process_action', $current_action, $this );
		}

		/**
		 * Process update settings action.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function process_action_update(): void {
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

			/**
			 * Fires after settings have been updated.
			 *
			 * @param AbstractSettings $settings The settings instance.
			 *
			 * @since 1.0.0
			 */
			do_action( 'storepress_admin_utils_services_settings_after_action_update', $this );

			wp_safe_redirect(
				$this->get_action_uri(
					array( 'message' => 'updated' )
				)
			);
			exit;
		}

		/**
		 * Process reset settings action.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function process_action_reset(): void {
			check_admin_referer( $this->get_nonce_action() );

			$this->delete_options();

			/**
			 * Fires after settings have been reset.
			 *
			 * @param AbstractSettings $settings The settings instance.
			 *
			 * @since 1.0.0
			 */
			do_action( 'storepress_admin_utils_services_settings_after_action_reset', $this );

			wp_safe_redirect(
				$this->get_action_uri(
					array( 'message' => 'deleted' )
				)
			);
			exit;
		}

		/**
		 * Sanitize form fields from POST data.
		 *
		 * @param array<string, mixed> $_post The POST data.
		 *
		 * @return array{ public: array<string, mixed>, private: array<string, mixed> }
		 *
		 * @since 1.0.0
		 */
		private function sanitize_fields( array $_post ): array {
			$fields = $this->get_available_fields();

			$public_data  = array();
			$private_data = array();

			foreach ( $fields as $key => $field ) {

				$sanitize_callback = $field->get_sanitize_callback();
				$type              = $field->get_type();
				$options           = $field->get_options();
				$default           = $field->get_default_value();

				if ( $field->is_private() ) {
					$id                  = $field->get_private_name();
					$private_data[ $id ] = map_deep( $_post[ $key ], $sanitize_callback );
					continue;
				}

				// Conditional value key is not set here if it's hidden.
				// So if is there any default value, we will set.
				if ( ! isset( $_post[ $key ] ) && ! in_array( $type, array( 'toggle', 'checkbox', 'group' ), true ) ) {
					$_post[ $key ] = $default;
				}

				switch ( $type ) {
					case 'toggle':
					case 'checkbox':
						// Add default checkbox and toggle value.
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
							$group_sanitize_callback = $group_field->get_sanitize_callback();
							$group_default           = $group_field->get_default_value();

							// Group Conditional value key is not set here if it's hidden.
							if ( ! isset( $_post[ $key ][ $group_field_id ] ) && ! in_array( $group_field_type, array( 'toggle', 'checkbox' ), true ) ) {
								$_post[ $key ][ $group_field_id ] = $group_default;
							}

							// Add default checkbox value.
							if ( ! isset( $_post[ $key ][ $group_field_id ] ) && in_array( $group_field_type, array( 'toggle', 'checkbox' ), true ) ) {
								$_post[ $key ][ $group_field_id ] = ( count( $group_field_options ) > 0 ) ? array() : 'no';
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

		// =========================================================================
		// Option/Data Methods
		// =========================================================================

		/**
		 * Get all saved options from wp_options.
		 *
		 * @param array<string, mixed> $default_value Optional. Default empty array.
		 *
		 * @return bool|array<string, mixed>|null
		 *
		 * @since 1.0.0
		 */
		public function get_options( array $default_value = array() ) {
			if ( ! $this->is_empty_array( $this->options ) ) {
				return $this->options;
			}
			$this->options = get_option( $this->get_settings_id(), $default_value );

			return $this->options;
		}

		/**
		 * Get single option value by field ID.
		 *
		 * @param string $field_id      The field ID.
		 * @param mixed  $default_value Optional. Default null.
		 *
		 * @return mixed|null
		 *
		 * @since 1.0.0
		 */
		public function get_option( string $field_id, $default_value = null ) {
			$field = $this->get_field( $field_id );

			return $field ? $field->get_value( $default_value ) : $default_value;
		}

		/**
		 * Get group option value.
		 *
		 * @param string $group_id      The group ID.
		 * @param string $field_id      The field ID within the group.
		 * @param mixed  $default_value Optional. Default null.
		 *
		 * @return mixed|null
		 *
		 * @since 1.0.0
		 */
		public function get_group_option( string $group_id, string $field_id, $default_value = null ) {
			$field = $this->get_field( $group_id );

			return $field ? $field->get_group_value( $field_id, $default_value ) : $default_value;
		}

		/**
		 * Update options with public/private data.
		 *
		 * @param array<string, mixed> $_data The data to update.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		private function update_options( array $_data ): void {
			$old_data = $this->get_options();

			if ( ! $this->is_empty_array( $old_data ) ) {
				$current_data = array_merge( $old_data, $_data['public'] );
			} else {
				$current_data = $_data['public'];
			}

			foreach ( $_data['private'] as $key => $value ) {
				update_option( esc_attr( $key ), $value );
			}

			$_data = $this->before_update_options( $current_data );

			update_option( $this->get_settings_id(), $_data );
		}

		/**
		 * Modify option data before saving. Override to customize.
		 *
		 * @param array<string, ?mixed> $_data The option data.
		 *
		 * @return array<string, ?mixed>
		 *
		 * @since 1.0.0
		 */
		public function before_update_options( array $_data ): array {
			return $_data;
		}

		/**
		 * Delete all options.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		final public function delete_options(): bool {
			return delete_option( $this->get_settings_id() );
		}

		// =========================================================================
		// Message Methods
		// =========================================================================

		/**
		 * Display settings messages based on redirect query arguments.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function settings_messages(): void {
			// We are just checking message request from uri redirect.
			if ( ! $this->get_message_query_arg_value() ) {
				return;
			}

			$strings = $this->localize_strings();

			$message = $this->get_message_query_arg_value();

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
		 * Get 'message' query argument value from URL.
		 *
		 * @return false|string
		 *
		 * @since 1.0.0
		 */
		final public function get_message_query_arg_value() {
			// We are just checking message query args request from uri redirect.
			if ( is_null( $this->http_get_var( 'message' ) ) ) {
				return false;
			}

			return sanitize_text_field( $this->http_get_var( 'message' ) );
		}

		/**
		 * Display settings errors/messages. Used in UI template.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function display_settings_messages(): void {
			settings_errors( $this->get_current_page_slug() );
		}

		/**
		 * Add settings message.
		 *
		 * @param string $message The message text.
		 * @param string $type    Optional. Default 'updated'.
		 *
		 * @return self
		 *
		 * @since 1.0.0
		 */
		final public function add_settings_message( string $message, string $type = 'updated' ): self {
			add_settings_error( $this->get_current_page_slug(), sprintf( '%s_message', $this->get_settings_id() ), $message, $type );

			return $this;
		}

		// =========================================================================
		// Admin Page Methods
		// =========================================================================

		/**
		 * Check if current page is this settings admin page.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function is_admin_page(): bool {
			// We have to check is valid current page.
			return ( is_admin() && $this->get_current_page_slug() === $this->http_get_var( 'page' ) );
		}

		/**
		 * Add settings link to plugin action links.
		 *
		 * @param string[] $links Existing plugin action links.
		 *
		 * @return string[]
		 *
		 * @since 1.0.0
		 */
		public function plugin_action_links( array $links ): array {

			$link_text = $this->get_localized_string( 'settings_link_text' );

			$class = sprintf( 'storepress-%s-settings', $this->get_plugin_slug() );

			$action_links = sprintf( '<a href="%1$s" aria-label="%2$s">%2$s</a>', esc_url( $this->get_settings_uri() ), esc_html( $link_text ) );

			$links[ $class ] = $action_links;

			return $links;
		}

		// =========================================================================
		// Script Methods
		// =========================================================================

		/**
		 * Register settings package scripts on the admin page.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function register_admin_scripts(): void {
			if ( ! $this->is_admin_page() ) {
				return;
			}

			$this->register_package_scripts( 'settings', $this->localize_strings() );
		}

		/**
		 * Enqueue settings scripts and WooCommerce scripts if needed.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts(): void {
			$this->enqueue_package_scripts( 'settings' );

			if ( $this->has_field_type( 'wc-enhanced-select' ) ) {
				wp_enqueue_style( 'woocommerce_admin_styles' );
				wp_enqueue_script( 'wc-enhanced-select' );
			}
		}

		// =========================================================================
		// REST API Methods
		// =========================================================================

		/**
		 * Get REST API namespace. Return false to disable REST API.
		 *
		 * @return string|bool
		 *
		 * @since 1.0.0
		 */
		public function show_in_rest() {
			return sprintf( '%s/%s', $this->get_page_slug(), $this->rest_api_version() );
		}

		/**
		 * Get REST API version prefix. Default 'v1'.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function rest_api_version(): string {
			return 'v1';
		}

		/**
		 * Get REST API base path. Default 'settings'.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function rest_api_base(): string {
			return 'settings';
		}

		/**
		 * Get WordPress Core Data entity kind for wp.data registration.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function core_data_entity_kind(): string {
			return $this->is_submenu() ? $this->show_in_rest() : $this->get_menu_slug();
		}

		/**
		 * Get WordPress Core Data entity name for wp.data registration.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function core_data_entity_name(): string {
			return $this->is_submenu() ? $this->rest_api_base() : $this->get_page_slug();
		}

		/**
		 * Get required capability for REST API GET requests.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function rest_get_capability(): string {
			return $this->get_capability();
		}

		/**
		 * Callback for 'rest_api_init'. Registers REST API routes.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function rest_api_init(): void {
			$this->get_factory()->create_rest_api( $this )->register_routes();
		}

		/**
		 * Register settings entity with WordPress Core Data API (wp.data).
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function rest_api_entity(): void {

			if ( $this->is_empty_string( $this->show_in_rest() ) ) {
				return;
			}

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

		/**
		 * Register REST API and wp.data entity hooks.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		final public function register_rest_api(): void {

			// Register REST API routes.
			add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

			// Register WordPress Data API entity.
			add_action( 'admin_enqueue_scripts', array( $this, 'rest_api_entity' ) );
		}
	}
}
