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

	use StorePress\AdminUtils\ServiceContainers\InternalServiceContainer;
	use StorePress\AdminUtils\ServiceProviders\Internal\SettingsServiceProvider;
	use StorePress\AdminUtils\Services\Internal\Settings\API;
	use StorePress\AdminUtils\Services\Internal\Settings\Field;
	use StorePress\AdminUtils\Services\Internal\Settings\Fields;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\ManageServiceProviderTrait;
	use StorePress\AdminUtils\Traits\MethodShouldImplementTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Abstracts\AbstractSettings' ) ) {

	/**
	 * Abstract Settings Class.
	 *
	 * Provides a framework for building WordPress admin settings pages with
	 * tabbed navigation, fields, sidebars, and REST API support.
	 *
	 * @name AbstractSettings
	 *
	 * @phpstan-use CallerTrait<object>
	 * @phpstan-use ManageServiceProviderTrait<SettingsServiceProvider>
	 *
	 * @method SettingsServiceProvider get_service_provider() Returns the SettingsServiceProvider instance that owns this provider.
	 *
	 * @example Basic implementation:
	 *          ```php
	 *          class My_Settings extends AbstractSettings {
	 *              use Singleton;
	 *
	 *              public function plugin_file(): string {
	 *                  return MY_PLUGIN_FILE;
	 *              }
	 *
	 *              public function settings_id(): string {
	 *                  return 'my_plugin_settings';
	 *              }
	 *
	 *              public function page_id(): string {
	 *                  return 'my-plugin';
	 *              }
	 *
	 *              public function add_settings(): array {
	 *                  return array( 'general' => 'General Settings' );
	 *              }
	 *          }
	 *          ```
	 *
	 * @example With tabs:
	 *          ```php
	 *          public function add_settings(): array {
	 *              return array(
	 *                  'general' => 'General',
	 *                  'advanced' => array(
	 *                      'name'    => 'Advanced',
	 *                      'sidebar' => false,
	 *                  ),
	 *              );
	 *          }
	 *          ```
	 *
	 * @see AbstractAdminMenu For menu registration methods.
	 * @see SettingsServiceProvider For service provider integration.
	 *
	 * @since 1.0.0
	 */
	abstract class AbstractSettings extends AbstractAdminMenu {

		use CallerTrait;
		use ManageServiceProviderTrait;
		use MethodShouldImplementTrait;

		// =========================================================================
		// Properties
		// =========================================================================

		/**
		 * Fields callback function name convention.
		 *
		 * Used to generate method names for tab-specific field callbacks.
		 *
		 * @var string $fields_callback_fn_name_convention
		 *
		 * @example 'add_general_settings_fields' for 'general' tab.
		 */
		protected string $fields_callback_fn_name_convention = 'add_%s_settings_fields';

		/**
		 * Sidebar callback function name convention.
		 *
		 * Used to generate method names for tab-specific sidebar callbacks.
		 *
		 * @var string $sidebar_callback_fn_name_convention
		 *
		 * @example 'add_general_settings_sidebar' for 'general' tab.
		 */
		protected string $sidebar_callback_fn_name_convention = 'add_%s_settings_sidebar';

		/**
		 * Page callback function name convention.
		 *
		 * Used to generate method names for tab-specific page callbacks.
		 *
		 * @var string $page_callback_fn_name_convention
		 *
		 * @example 'add_general_settings_page' for 'general' tab.
		 */
		protected string $page_callback_fn_name_convention = 'add_%s_settings_page';

		/**
		 * Store all saved options.
		 *
		 * @var array<string, mixed> $options
		 */
		protected array $options = array();

		// =========================================================================
		// Abstract Methods
		// =========================================================================

		/**
		 * Get settings ID.
		 *
		 * Returns the unique identifier used as the option name in wp_options table.
		 *
		 * @return string The settings ID.
		 *
		 * @since 1.0.0
		 */
		abstract public function settings_id(): string;

		// =========================================================================
		// Constructor and Initialization Methods
		// =========================================================================

		/**
		 * Constructor.
		 *
		 * Initializes the settings page by setting up the caller, registering
		 * service providers, and calling parent constructor.
		 *
		 * @param object $caller The caller class instance (typically the main plugin class).
		 *
		 * @since 1.0.0
		 */
		public function __construct( object $caller ) {

			$this->set_caller( $caller );
			$this->register_service_provider( $this );
			$this->register_services();
			$this->register_rest_api();

			parent::__construct( $caller );
		}

		/**
		 * Initialize settings page.
		 *
		 * Registers admin scripts and plugin action links.
		 *
		 * @return void
		 *
		 * @see register_admin_scripts()
		 * @see plugin_action_links()
		 *
		 * @since 1.0.0
		 */
		final public function page_init(): void {

			// Register admin scripts on admin_enqueue_scripts hook.
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 20 );
			// Add settings link to plugin action links.
			add_filter( 'plugin_action_links_' . $this->get_plugin_basename(), array( $this, 'plugin_action_links' ), 15 );

			$this->settings_actions();
		}

		/**
		 * Settings page initialization.
		 *
		 * Enqueues scripts and displays settings messages.
		 *
		 * @return void
		 *
		 * @see enqueue_scripts()
		 * @see settings_messages()
		 *
		 * @since 1.0.0
		 */
		public function page_loaded(): void {
			$this->enqueue_scripts();
			$this->settings_messages();
		}

		// =========================================================================
		// Service Provider Methods
		// =========================================================================

		/**
		 * Create service provider instance.
		 *
		 * Returns a new SettingsServiceProvider instance for managing settings services.
		 *
		 * @param object $caller The caller class instance.
		 *
		 * @return SettingsServiceProvider The service provider instance.
		 *
		 * @since 1.0.0
		 */
		public function service_provider( object $caller ): SettingsServiceProvider {
			return new SettingsServiceProvider( $caller );
		}

		/**
		 * Get service container.
		 *
		 * @return InternalServiceContainer
		 */
		public function get_container(): InternalServiceContainer {
			return $this->get_service_provider()->get_container();
		}

		// =========================================================================
		// Settings Configuration Methods
		// =========================================================================

		/**
		 * Get settings ID wrapper.
		 *
		 * Returns the settings ID from the abstract method.
		 *
		 * @return string The settings ID.
		 *
		 * @see settings_id()
		 *
		 * @since 1.0.0
		 */
		public function get_settings_id(): string {
			return $this->settings_id();
		}

		/**
		 * Add settings tabs.
		 *
		 * Override this method in subclass to define settings tabs.
		 *
		 * @return array<string, mixed> Array of tab configurations.
		 *
		 * @throws \WP_Exception Throw exception if this method is not implemented in subclass.
		 *
		 * @since 1.0.0
		 */
		public function add_settings(): array {
			$this->subclass_should_implement( __FUNCTION__ );

			return array();
		}

		/**
		 * Get settings tabs.
		 *
		 * Returns the settings tabs defined in add_settings().
		 *
		 * @return array<string, mixed> Array of tab configurations.
		 *
		 * @see add_settings()
		 *
		 * @since 1.0.0
		 */
		final public function get_settings(): array {
			return $this->add_settings();
		}

		/**
		 * Control displaying reset button.
		 *
		 * Override this method to hide the reset button on settings pages.
		 *
		 * @return bool True to show reset button, false to hide.
		 *
		 * @since 1.0.0
		 */
		public function show_reset_button(): bool {
			return true;
		}

		/**
		 * Translatable strings.
		 *
		 * Override this method in subclass to provide localized strings.
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
		 * } Array of localized strings.
		 *
		 * @throws \WP_Exception Throw exception if this method is not implemented in subclass.
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
		 * Retrieves a specific localized string from localize_strings().
		 *
		 * @param string $string_key The localized string key.
		 *
		 * @return string The localized string or empty string if not found.
		 *
		 * @see localize_strings()
		 *
		 * @since 1.0.0
		 */
		public function get_localized_string( string $string_key ): string {
			$strings = $this->localize_strings();

			return $strings[ $string_key ] ?? '';
		}

		/**
		 * Add new allowed tags on fields markup.
		 *
		 * Override this method to add custom allowed HTML tags for field output.
		 *
		 * @return array<string, mixed> Array of allowed HTML tags.
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
		 * Get default tab name.
		 *
		 * Returns the default tab identifier used when no tab is specified.
		 *
		 * @return string The default tab name.
		 *
		 * @since 1.0.0
		 */
		public function default_tab_name(): string {
			return 'general';
		}

		/**
		 * Get current active tab.
		 *
		 * Determines the current tab from the URL query parameter or falls back
		 * to the default tab.
		 *
		 * @return string The current tab identifier.
		 *
		 * @see default_tab_name()
		 * @see get_tabs()
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
		 * Get all tabs configuration.
		 *
		 * Processes the settings array and generates tab configurations including
		 * callbacks for fields, sidebars, and custom pages.
		 *
		 * @return array<int|string, mixed> Array of tab configurations.
		 *
		 * @see get_settings()
		 * @see default_tab_name()
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

				if ( is_numeric( $navs[ $key ]['sidebar'] ) ) {
					$navs[ $key ]['sidebar_width'] = absint( $navs[ $key ]['sidebar'] );
					$navs[ $key ]['sidebar']       = true;
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
		 * Get single tab configuration.
		 *
		 * Retrieves the configuration for a specific tab by ID. If no ID is provided,
		 * returns the current active tab configuration.
		 *
		 * @param string $tab_id Optional. The tab identifier. Default empty string.
		 *
		 * @return array<string, mixed> The tab configuration array.
		 *
		 * @see get_tabs()
		 * @see get_current_tab()
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
		 * @return bool True if tab has save button, false otherwise.
		 *
		 * @see get_tab()
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
		 * @return bool True if tab has sidebar, false otherwise.
		 *
		 * @see get_tab()
		 *
		 * @since 1.0.0
		 */
		final public function has_sidebar(): bool {
			$data = $this->get_tab();

			return true === $data['sidebar'];
		}

		/**
		 * Get current tab sidebar width.
		 *
		 * @return int The sidebar width percentage.
		 *
		 * @see get_tab()
		 *
		 * @since 1.0.0
		 */
		final public function get_sidebar_width(): int {
			$data = $this->get_tab();

			return absint( $data['sidebar_width'] );
		}

		/**
		 * Get sidebar width CSS variable.
		 *
		 * @return string CSS variable string or empty string if no sidebar.
		 *
		 * @see has_sidebar()
		 * @see get_sidebar_width()
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
		 * @return callable|null The page callback or null.
		 *
		 * @see get_tab()
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
		 * @return callable|null The fields callback or null.
		 *
		 * @see get_tab()
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
		 * @return callable|null The sidebar callback or null.
		 *
		 * @see get_tab()
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
		 * Get all fields from all tabs.
		 *
		 * @return Field[] Array of Field objects indexed by field ID.
		 *
		 * @see get_tabs()
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
					foreach ( $fields as $field ) {
						if ( 'section' === $field['type'] ) {
							continue;
						}

						// @TODO Get Field.
						$_field = $this->get_container()->get( Field::class, $field );

						$all_fields[ $field['id'] ] = $_field;
					}
				}
			}

			return $all_fields;
		}

		/**
		 * Get field by ID.
		 *
		 * @param string $field_id The field ID.
		 *
		 * @return Field|null The Field object or null if not found.
		 *
		 * @see get_all_fields()
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
		 * @return Field[] Array of Field objects for current tab.
		 *
		 * @see get_tab_fields_callback()
		 *
		 * @since 1.0.0
		 */
		private function get_available_fields(): array {
			$field_cb         = $this->get_tab_fields_callback();
			$available_fields = array();
			if ( is_callable( $field_cb ) ) {
				$fields = $field_cb();
				/**
				 * Field
				 *
				 * @var array<string, mixed> $field
				 */
				foreach ( $fields as $field ) {
					if ( 'section' !== $field['type'] ) {
						// @TODO Get Field.
						$_field                           = $this->get_container()->get( Field::class, $field );
						$available_fields[ $field['id'] ] = $_field;
					}
				}
			}

			return $available_fields;
		}

		/**
		 * Get available field by ID for current tab.
		 *
		 * @param string $field_id The field ID.
		 *
		 * @return Field|null The Field object or null if not found.
		 *
		 * @see get_available_fields()
		 *
		 * @since 1.0.0
		 *
		 * @phpstan-ignore-next-line
		 */
		private function get_available_field( string $field_id ): ?Field {
			$fields = $this->get_available_fields();

			return $fields[ $field_id ] ?? null;
		}

		/**
		 * Get all registered field types.
		 *
		 * @return string[] Array of unique field types.
		 *
		 * @see get_tabs()
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
		 * @return bool True if field type exists, false otherwise.
		 *
		 * @see get_registered_field_types()
		 *
		 * @since 1.0.0
		 */
		public function has_field_type( string $field_type ): bool {
			$types = $this->get_registered_field_types();

			return in_array( $field_type, $types, true );
		}

		/**
		 * Check for duplicate field IDs.
		 *
		 * Triggers an error if duplicate field IDs are found.
		 *
		 * @return void
		 *
		 * @see get_tabs()
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

		// =========================================================================
		// Field ID Helper Methods
		// =========================================================================

		/**
		 * Get generated field HTML ID attribute.
		 *
		 * @param string     $field_id  The field ID.
		 * @param int|string $option_id Optional. The option ID. Default empty string.
		 *
		 * @return string The generated HTML ID.
		 *
		 * @since 1.0.0
		 */
		public function get_field_id( string $field_id, $option_id = '' ): string {
			return $this->is_empty_string( $option_id ) ? sprintf( '%s', $field_id ) : sprintf( '%s__%s', $field_id, $option_id );
		}

		/**
		 * Get field CSS selector.
		 *
		 * @param string     $field_id  The field ID.
		 * @param int|string $option_id Optional. The option ID. Default empty string.
		 *
		 * @return string The CSS selector string.
		 *
		 * @see get_field_id()
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
		 * @param int|string $option_id Optional. The option ID. Default empty string.
		 *
		 * @return string The generated HTML ID.
		 *
		 * @since 1.0.0
		 */
		public function get_group_field_id( string $group_id, string $field_id, $option_id = '' ): string {
			return $this->is_empty_string( $option_id ) ? sprintf( '%s__%s__group', $group_id, $field_id ) : sprintf( '%s__%s__%s__group', $group_id, $field_id, $option_id );
		}

		/**
		 * Get group field CSS selector.
		 *
		 * @param string     $group_id  The group ID.
		 * @param string     $field_id  The field ID.
		 * @param int|string $option_id Optional. The option ID. Default empty string.
		 *
		 * @return string The CSS selector string.
		 *
		 * @see get_group_field_id()
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
		 * Display settings page template.
		 *
		 * Override this method for custom UI page.
		 *
		 * @return void
		 *
		 * @see https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions
		 *
		 * @since 1.0.0
		 */
		public function render(): void {
			include_once $this->get_package_template_path() . '/classic-template.php';
		}

		/**
		 * Display tabs navigation.
		 *
		 * Used in UI template.
		 *
		 * @return void
		 *
		 * @see get_navs()
		 *
		 * @since 1.0.0
		 */
		final public function display_tabs(): void {
			echo wp_kses_post( implode( '', $this->get_navs() ) );
		}

		/**
		 * Get navigation HTML items.
		 *
		 * @return string[] Array of navigation HTML strings.
		 *
		 * @see get_tabs()
		 * @see get_current_tab()
		 *
		 * @since 1.0.0
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
		 * Display settings fields.
		 *
		 * Used in UI template.
		 *
		 * @return void
		 *
		 * @see get_tab_fields_callback()
		 * @see get_tab_page_callback()
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
					$fields = $this->get_container()->get( Fields::class, $get_fields );
					$fields->display();

					$this->display_buttons();
				}
			} else {
				$fields_fn_name      = sprintf( $this->fields_callback_fn_name_convention, $current_tab );
				$page_fn_name        = sprintf( $this->page_callback_fn_name_convention, $current_tab );
				$class_relative_path = $this->get_class_relative_path( $this );
				$message             = sprintf( 'Should return fields array from "<strong>%s</strong>". Or For custom page create "<strong>%s</strong>" in <strong>%s</strong><br />', $fields_fn_name, $page_fn_name, $class_relative_path );
				wp_trigger_error( '', $message );
			}
		}

		/**
		 * Display custom page content.
		 *
		 * Used in UI template.
		 *
		 * @return void
		 *
		 * @see get_tab_page_callback()
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
		 * Display sidebar content.
		 *
		 * Used in UI template.
		 *
		 * @return void
		 *
		 * @see get_tab_sidebar()
		 * @see get_default_sidebar()
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
		 * Get default sidebar content.
		 *
		 * Triggers an error to indicate the method should be overridden.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function get_default_sidebar(): void {
			$current_tab       = $this->get_current_tab();
			$callback_function = sprintf( $this->sidebar_callback_fn_name_convention, $current_tab );

			/* translators: %s: Method name. */
			$message  = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __FUNCTION__ );
			$message .= sprintf( '<br />Create "<strong>%1$s</strong>" method for "<strong>%2$s</strong>" tab sidebar in "<strong>%3$s</strong>". <br /><br />', $callback_function, $current_tab, $this->get_class_relative_path( $this ) );
			wp_trigger_error( '', $message );
		}

		/**
		 * Display action buttons (submit and reset).
		 *
		 * @return void
		 *
		 * @see get_reset_button()
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
		 * Get reset button HTML.
		 *
		 * @return string The reset button HTML or empty string if hidden.
		 *
		 * @see show_reset_button()
		 * @see get_reset_uri()
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
		 * Get settings URI arguments.
		 *
		 * Builds an array of query arguments for the settings page URL.
		 *
		 * @param array<string, mixed> $extra Optional. Additional arguments to merge. Default empty array.
		 *
		 * @return array<string, mixed> Array of URI arguments.
		 *
		 * @see get_current_tab()
		 * @see get_current_page_slug()
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
		 * Get settings page URI.
		 *
		 * Generates the full admin URL for the settings page.
		 *
		 * @param array<string, mixed> $extra Optional. Additional query arguments. Default empty array.
		 *
		 * @return string The settings page URL.
		 *
		 * @see is_submenu()
		 * @see get_uri_args()
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
		 * @return string The tab URL.
		 *
		 * @see get_settings_uri()
		 *
		 * @since 1.0.0
		 */
		public function get_tab_uri( string $tab_id ): string {
			return $this->get_settings_uri( array( 'tab' => $tab_id ) );
		}

		/**
		 * Get action URI.
		 *
		 * Used on settings form action attribute.
		 *
		 * @param array<string, mixed> $extra Optional. Additional arguments. Default empty array.
		 *
		 * @return string The action URL.
		 *
		 * @see get_settings_uri()
		 *
		 * @since 1.0.0
		 */
		final public function get_action_uri( array $extra = array() ): string {
			return $this->get_settings_uri( $extra );
		}

		/**
		 * Get reset URI.
		 *
		 * Used in UI template for reset action.
		 *
		 * @return string The reset URL with nonce.
		 *
		 * @see get_settings_uri()
		 * @see get_nonce_action()
		 *
		 * @since 1.0.0
		 */
		final public function get_reset_uri(): string {
			return wp_nonce_url( $this->get_settings_uri( array( 'action' => 'reset' ) ), $this->get_nonce_action() );
		}

		/**
		 * Get nonce action string.
		 *
		 * @return string The nonce action string.
		 *
		 * @see get_option_group_name()
		 *
		 * @since 1.0.0
		 */
		final public function get_nonce_action(): string {
			$group = $this->get_option_group_name();

			return sprintf( '%s-options', $group );
		}

		/**
		 * Get option group name.
		 *
		 * Used in UI template for settings_fields().
		 *
		 * @return string The option group name.
		 *
		 * @see get_current_page_slug()
		 * @see get_current_tab()
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
		 * Handle settings form actions.
		 *
		 * Processes update and reset actions from the settings form.
		 *
		 * @return void
		 *
		 * @see process_actions()
		 * @see get_nonce_action()
		 *
		 * @since 1.0.0
		 */
		final public function settings_actions(): void {
			if ( is_null( $this->http_request_var( 'action' ) ) || $this->http_get_var( 'page' ) !== $this->get_current_page_slug() ) {
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
		 * Process form actions.
		 *
		 * @param string $current_action The current action to process.
		 *
		 * @return void
		 *
		 * @see process_action_update()
		 * @see process_action_reset()
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
		}

		/**
		 * Process update settings action.
		 *
		 * @return void
		 *
		 * @see wp_removable_query_args()
		 * @see sanitize_fields()
		 * @see update_options()
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
		 * @see wp_removable_query_args()
		 * @see delete_options()
		 *
		 * @since 1.0.0
		 */
		public function process_action_reset(): void {
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
		 * Sanitize form fields.
		 *
		 * @param array<string, mixed> $_post The POST data.
		 *
		 * @return array{ public: array<string, mixed>, private: array<string, mixed> } Sanitized data.
		 *
		 * @see get_available_fields()
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
		 * Get all saved options.
		 *
		 * @param array<string, mixed> $default_value Optional. Default value. Default empty array.
		 *
		 * @return bool|array<string, mixed>|null The options array or default value.
		 *
		 * @see get_settings_id()
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
		 * Get single option value.
		 *
		 * @param string $field_id      The field ID.
		 * @param mixed  $default_value Optional. Default value. Default null.
		 *
		 * @return mixed|null The option value or default.
		 *
		 * @see get_field()
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
		 * @param mixed  $default_value Optional. Default value. Default null.
		 *
		 * @return mixed|null The option value or default.
		 *
		 * @see get_field()
		 *
		 * @since 1.0.0
		 */
		public function get_group_option( string $group_id, string $field_id, $default_value = null ) {
			$field = $this->get_field( $group_id );

			return $field ? $field->get_group_value( $field_id, $default_value ) : $default_value;
		}

		/**
		 * Update options.
		 *
		 * @param array<string, mixed> $_data The data to update.
		 *
		 * @return void
		 *
		 * @see get_options()
		 * @see before_update_options()
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
		 * Before update options hook.
		 *
		 * Override this method to modify option data before saving.
		 *
		 * @param array<string, ?mixed> $_data The option data.
		 *
		 * @return array<string, ?mixed> The modified option data.
		 *
		 * @since 1.0.0
		 */
		public function before_update_options( array $_data ): array {
			return $_data;
		}

		/**
		 * Delete all options.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @see get_settings_id()
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
		 * Display settings messages.
		 *
		 * Handles message display based on query arguments from redirects.
		 *
		 * @return void
		 *
		 * @see process_action_update()
		 * @see process_action_reset()
		 * @see wp_removable_query_args()
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
		 * Get message query argument value.
		 *
		 * @return false|string The message value or false if not set.
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
		 * Display settings messages in UI.
		 *
		 * Used in UI template.
		 *
		 * @return void
		 *
		 * @see get_current_page_slug()
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
		 * @param string $type    Optional. Message type: 'error', 'success', 'warning', 'info', 'updated'. Default 'updated'.
		 *
		 * @return self Returns self for method chaining.
		 *
		 * @see get_current_page_slug()
		 * @see get_settings_id()
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
		 * Check if current page is the settings admin page.
		 *
		 * @return bool True if on settings admin page, false otherwise.
		 *
		 * @see get_current_page_slug()
		 *
		 * @since 1.0.0
		 */
		public function is_admin_page(): bool {
			// We have to check is valid current page.
			return ( is_admin() && $this->get_current_page_slug() === $this->http_get_var( 'page' ) );
		}

		/**
		 * Plugin action links filter callback.
		 *
		 * Adds settings link to plugin action links.
		 *
		 * @param string[] $links Existing plugin action links.
		 *
		 * @return string[] Modified plugin action links.
		 *
		 * @see get_settings_uri()
		 * @see localize_strings()
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
		 * Register admin scripts.
		 *
		 * Registers the settings package scripts when on the settings admin page.
		 *
		 * @return void
		 *
		 * @see is_admin_page()
		 * @see register_package_scripts()
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
		 * Enqueue scripts.
		 *
		 * Enqueues the settings package scripts and WooCommerce scripts if needed.
		 *
		 * @return void
		 *
		 * @see enqueue_package_scripts()
		 * @see has_field_type()
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
		 * Determine if settings should be exposed via REST API.
		 *
		 * Returns the REST API namespace for this settings page. If empty string
		 * or false is returned, the REST API will be disabled for this settings page.
		 *
		 * @return string|bool The REST namespace or false to disable REST API.
		 *
		 * @see rest_api_version()
		 * @see rest_api_base()
		 * @see get_page_slug()
		 *
		 * @example REST endpoint format:
		 *          GET: /wp-json/<page-id>/<rest-api-version>/<rest-api-base>
		 *          GET: /wp-json/my-plugin/v1/settings
		 *
		 * @example Disable REST API:
		 *          ```php
		 *          public function show_in_rest() {
		 *              return false;
		 *          }
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function show_in_rest() {
			return sprintf( '%s/%s', $this->get_page_slug(), $this->rest_api_version() );
		}

		/**
		 * Get REST API version.
		 *
		 * Override this method to change the REST API version prefix.
		 *
		 * @return string The REST API version string.
		 *
		 * @see show_in_rest()
		 *
		 * @example Override version:
		 *          ```php
		 *          public function rest_api_version(): string {
		 *              return 'v2';
		 *          }
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function rest_api_version(): string {
			return 'v1';
		}

		/**
		 * Get REST API base path.
		 *
		 * Override this method to change the REST API base endpoint.
		 *
		 * @return string The REST API base path.
		 *
		 * @see show_in_rest()
		 *
		 * @example Override base:
		 *          ```php
		 *          public function rest_api_base(): string {
		 *              return 'options';
		 *          }
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function rest_api_base(): string {
			return 'settings';
		}

		/**
		 * Get WordPress Core Data entity kind.
		 *
		 * Used for registering the settings with WordPress Data API (wp.data).
		 * The entity kind is used to group related entities.
		 *
		 * @return string The entity kind string.
		 *
		 * @see is_submenu()
		 * @see show_in_rest()
		 * @see get_menu_slug()
		 * @see rest_api_entity()
		 *
		 * @since 1.0.0
		 */
		public function core_data_entity_kind(): string {
			return $this->is_submenu() ? $this->show_in_rest() : $this->get_menu_slug();
		}

		/**
		 * Get WordPress Core Data entity name.
		 *
		 * Used for registering the settings with WordPress Data API (wp.data).
		 * The entity name is the unique identifier within the entity kind.
		 *
		 * @return string The entity name string.
		 *
		 * @see is_submenu()
		 * @see rest_api_base()
		 * @see get_page_slug()
		 * @see rest_api_entity()
		 *
		 * @since 1.0.0
		 */
		public function core_data_entity_name(): string {
			return $this->is_submenu() ? $this->rest_api_base() : $this->get_page_slug();
		}

		/**
		 * Get REST API capability for GET requests.
		 *
		 * Returns the capability required to read settings via REST API.
		 * Override this method to change the required capability.
		 *
		 * @return string The required capability string.
		 *
		 * @see get_capability()
		 *
		 * @example Override capability:
		 *          ```php
		 *          public function rest_get_capability(): string {
		 *              return 'manage_woocommerce';
		 *          }
		 *          ```
		 *
		 * @since 1.0.0
		 */
		public function rest_get_capability(): string {
			return $this->get_capability();
		}

		/**
		 * Initialize REST API routes.
		 *
		 * Callback for the 'rest_api_init' action hook. Registers the REST API
		 * routes for this settings page using the API service.
		 *
		 * @return void
		 *
		 * @see register_rest_api()
		 * @see API::register_routes()
		 *
		 * @since 1.0.0
		 */
		public function rest_api_init(): void {

			// @TODO Get API.
			$this->get_container()->get( API::class )->register_routes();
		}

		/**
		 * Register WordPress Data API entity.
		 *
		 * Callback for the 'admin_enqueue_scripts' action hook. Adds an inline script
		 * to register the settings entity with WordPress Core Data API for use
		 * with Gutenberg and React-based admin interfaces.
		 *
		 * @return void
		 *
		 * @see show_in_rest()
		 * @see core_data_entity_name()
		 * @see core_data_entity_kind()
		 * @see rest_api_base()
		 * @see get_page_title()
		 *
		 * @since 1.0.0
		 */
		public function rest_api_entity(): void {

			if ( $this->is_empty_string( $this->show_in_rest() ) ) {
				return;
			}

			/**
			 * Example usages.
			 *
			 * @example
			 * ```js
			 *
			 * import { select } from '@wordpress/data';
			 *
			 * // For a single record (no ID needed if your endpoint returns one object)
			 * const settings = select( 'core' ).getEntityRecord( '<menu_slug>', '<page_slug>' );
			 * const settings = wp.data.select( 'core' ).getEntityRecord( '<menu_slug>', '<page_slug>' );
			 *
			 * // Or use the resolver hook in a component
			 * import { useEntityRecord } from '@wordpress/core-data';
			 *
			 * function MyComponent() {
			 * const { record, isResolving } = useEntityRecord( 'storepress', '<page_slug>' );
			 *
			 * if ( isResolving ) return <p>Loading...</p>;
			 *
			 * return <div>{ record?.['field-text'] }</div>;
			 *
			 * ```
			 */

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
		 * Register REST API hooks.
		 *
		 * Registers the action hooks for REST API initialization and
		 * WordPress Data API entity registration.
		 *
		 * @return void
		 *
		 * @see rest_api_init()
		 * @see rest_api_entity()
		 *
		 * @since 1.0.0
		 */
		public function register_rest_api(): void {
			// Register REST API routes on rest_api_init hook.
			add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
			// Register Data API entity on admin_enqueue_scripts hook.
			add_action( 'admin_enqueue_scripts', array( $this, 'rest_api_entity' ) );
		}
	}
}
