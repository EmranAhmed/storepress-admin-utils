# StorePress Admin Utils

StorePress Admin Utils is a comprehensive PHP library for WordPress that simplifies the creation of admin interfaces for plugins. It provides a structured, object-oriented approach to building settings pages, managing plugin updates, handling rollbacks, and displaying administrative notices.

## Core Features

## Settings Framework

The library's cornerstone is its powerful settings framework, which allows developers to create complex settings pages with multiple tabs and a wide variety of field types.

### Field Types

It supports a rich set of field types including `text`, `unit`, `textarea`, `checkbox`, `toggle`,`radio`, `select`, `color`, `number`, and more advanced fields like `toggle` switches and `group` fields.

### Structure

Settings are organized into sections and tabs, providing a clean and intuitive user experience. The framework handles the rendering of the entire settings page, including the navigation tabs, fields, and save/reset buttons.

### Data Management 

It streamlines the process of saving, retrieving, and deleting plugin options from the database. It also includes mechanisms for data sanitization and validation.

## Plugin Updater

StorePress Admin Utils includes a robust module for managing plugin updates from a custom, non-WordPress.org server.

### Custom Update Server

Developers can specify a URL to their own update server in the plugin's header. The library then communicates with this server to check for new versions.

### Update Process

It handles the entire update process, from checking for new versions and displaying update notifications in the WordPress admin to downloading and installing the new plugin package. The `README.md` file provides a clear example of how to set up the server-side endpoint to respond to update requests.

## Plugin Rollback

A key feature is the ability to roll back a plugin to a previous version.

## Simple DI Container

Simple DI Container with singleton and factory support.

### Rollback UI

It adds a "Rollback" link to the plugin's action links on the plugins page. This leads to a dedicated page where the user can select a previous version to install.

### Version Management

The rollback functionality is tied into the update server, which must provide a list of available versions and their corresponding package URLs.

## REST API Integration

The settings framework can automatically expose plugin settings via the WordPress REST API.

### Endpoints

It creates REST API endpoints for fetching settings, allowing for headless WordPress implementations or integration with other applications.

### Configuration

Developers can easily enable or disable this feature and customize the API namespace and version.

## Upgrade & Compatibility Notices

The library provides a class for managing admin notices, which is particularly useful for handling compatibility issues between a primary plugin and its extensions. 
It can display notices in the admin area and on the plugins page if an incompatible version of an extension is detected.

## Usage and Implementation

To use StorePress Admin Utils, developers typically extend the core classes provided by the library, such as `Settings`, `Updater`, and `Upgrade_Notice`. By implementing the abstract methods in these classes, developers can configure the library to suit their plugin's specific needs.

## Installation

```shell
composer require storepress/admin-utils
```

## Plugin Directory Structure

```
example/
│
├── example.php                            # Main plugin entry point, registers hooks, initializes Init class
├── composer.json                          # Composer dependencies & PSR-4 autoloading configuration
├── CLAUDE.md                              # AI assistant guidance for codebase conventions
├── README.md                              # Plugin Readme file
├── CHANGELOG.md                           # Plugin Changelog file
│
└── includes/                              # PHP source files (PSR-4: StorePress\Example\)
    │
    ├── Init.php                           # Plugin bootstrap: loads autoloader, registers/boots services
    ├── functions.php                      # Utility functions.
    │
    ├── Features/                          # Feature modules
    │   ├── Blocks.php                     # Gutenberg blocks.    
    │
    ├── Integrations/                      # Classes that integrate with WordPress/external systems
    │   ├── AdminPage.php                  # Base settings page with sidebar and localized strings
    │   ├── DeactivationFeedback.php       # Collects user feedback on plugin deactivation via dialog
    │   ├── ProPluginInCompatibility.php   # Checks pro plugin version compatibility (v3.0.0+)
    │   └── Updater.php                    # Handles plugin updates from custom server with rollback
    │
    ├── ServiceContainers/                 # Dependency injection
    │   └── ServiceContainer.php           # DI container singleton for registering/resolving services
    │
    ├── ServiceProviders/                  # Service registration & bootstrapping
    │   └── ServiceProvider.php            # Registers all services and boots them in correct order
    │
    └── Services/                          # Business logic services
        └── Settings.php                   # Defines admin settings tabs and fields (General, Basic, Advanced, Rest)
```

## Usage

### Plugin entry file.

```php
<?php
  /**
  Plugin Name: Plugin Example
  Plugin URI: https://storepress.com/plugins/plugin-example/
  Description: Example Plugin.
  Author: Emran Ahmed
  Version: 1.0.0
  Tested up to: 6.3
  Author URI: https://storepress.com/emran/
  Update URI: http://sites.local/
  */
  
  defined( 'ABSPATH' ) || die( 'Keep Silent' );
  
  use StorePress\Example\Init;
  
  // Include the main class.
  if ( ! class_exists( Init::class, false ) ) {
    require_once __DIR__ . '/includes/Init.php';
  }
  
  /**
   * Plugin class init
   *
   * @return Init
   */
  function plugin_example(): Init {
    return Init::instance(__FILE__);
  }
  
  add_action( 'plugins_loaded', 'plugin_example' );
```

### Sample `Init.php` file

```php
<?php
	/**
	 * Plugin Initialization Class File.
	 *
	 * Handles plugin bootstrap, dependency loading, and service provider initialization.
	 *
	 * @package    StorePress/B
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\Example;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	use StorePress\Example\ServiceContainers\ServiceContainer;
	use StorePress\Example\ServiceProviders\ServiceProvider;
	
	/**
	 * Plugin Initialization Class.
	 *
	 * Bootstraps the plugin by loading vendor autoloaders, registering the service provider,
	 * and initializing hooks. Uses the singleton pattern to ensure only one instance exists.
	 *
	 * @name Init
	 *
	 * @example
	 * // Initialize the plugin from main plugin file.
	 * Init::instance( __FILE__ );
	 *
	 * @example
	 * // Access the service container.
	 * $container = Init::instance( __FILE__ )->get_container();
	 *
	 * @example
	 * // Get the plugin file path.
	 * $plugin_file = plugin_b()->get_plugin_file();
	 */
	class Init {

		// =====================================================================
		// Properties
		// =====================================================================

		/**
		 * Plugin file path.
		 *
		 * Stores the absolute path to the main plugin file.
		 *
		 * @var string
		 */
		protected string $plugin_file;

		// =====================================================================
		// Singleton Instance
		// =====================================================================

		/**
		 * Return singleton instance of the Init class.
		 *
		 * The instance will be created if it does not exist yet.
		 *
		 * @param string $plugin_file The absolute path to the main plugin file.
		 *
		 * @return self The singleton instance.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Get or create the singleton instance.
		 * $init = Init::instance( __FILE__ );
		 *
		 * @example
		 * // Access from global function.
		 * function plugin_b(): Init {
		 *     return Init::instance( __FILE__ );
		 * }
		 */
		public static function instance( string $plugin_file ): self {
			static $instance = null;
			return $instance ??= new self( $plugin_file );
		}

		// =====================================================================
		// Constructor
		// =====================================================================

		/**
		 * Initialize the plugin.
		 *
		 * Loads vendor autoloaders and functions, registers the service provider,
		 * boots the service provider, and runs initialization hooks.
		 *
		 * @param string $plugin_file The absolute path to the main plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @see Init::includes()          Loads required files.
		 * @see Init::service_provider()  Gets the service provider instance.
		 * @see Init::hooks()             Registers WordPress hooks.
		 */
		public function __construct( string $plugin_file ) {

			$this->plugin_file = $plugin_file;

			$this->includes();

			$this->service_provider()->register();

			$this->service_provider()->boot();

			$this->hooks();
		}

		// =====================================================================
		// File Loading Methods
		// =====================================================================

		/**
		 * Load required files.
		 *
		 * Includes the vendor autoload_packages.php file if it exists and the
		 * plugin functions.php file for utility functions.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @see Init::get_plugin_file() Gets the plugin file path.
		 *
		 * @example
		 * // Files loaded:
		 * // - vendor/autoload_packages.php (if exists)
		 * // - includes/functions.php
		 */
		public function includes(): void {
			$vendor_path = untrailingslashit( plugin_dir_path( $this->get_plugin_file() ) ) . '/vendor';

			if ( file_exists( $vendor_path . '/autoload_packages.php' ) ) {
				require_once $vendor_path . '/autoload_packages.php';
			}

			require_once __DIR__ . '/functions.php';
		}

		// =====================================================================
		// Getter Methods
		// =====================================================================

		/**
		 * Get the plugin file path.
		 *
		 * Returns the absolute path to the main plugin file, useful for
		 * deriving plugin directory, URL, basename, and version.
		 *
		 * @return string The absolute path to the main plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Get the plugin file path.
		 * $file = $init->get_plugin_file();
		 * // Returns: /path/to/wp-content/plugins/plugin-b/plugin-b.php
		 *
		 * @example
		 * // Derive plugin directory.
		 * $dir = plugin_dir_path( $init->get_plugin_file() );
		 *
		 * @example
		 * // Derive plugin URL.
		 * $url = plugin_dir_url( $init->get_plugin_file() );
		 *
		 * @example
		 * // Get plugin basename.
		 * $basename = plugin_basename( $init->get_plugin_file() );
		 * // Returns: plugin-b/plugin-b.php
		 */
		public function get_plugin_file(): string {
			return $this->plugin_file;
		}

		// =====================================================================
		// Hook Methods
		// =====================================================================

		/**
		 * Register WordPress hooks.
		 *
		 * Hook into WordPress actions and filters for plugin functionality.
		 * This method is intended to be extended with additional hook registrations
		 * as the plugin grows.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @example
		 * // Override in subclass or extend with hooks:
		 * public function hooks(): void {
		 *     add_action( 'init', array( $this, 'register_post_types' ) );
		 *     add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ) );
		 * }
		 */
		public function hooks(): void {}

		// =====================================================================
		// Service Container Methods
		// =====================================================================

		/**
		 * Get the service provider instance.
		 *
		 * Returns the singleton instance of ServiceProvider which manages
		 * dependency injection and service registration for the plugin.
		 *
		 * @return ServiceProvider The service provider instance.
		 *
		 * @since 1.0.0
		 *
		 * @see ServiceProvider::instance() Creates or returns the provider instance.
		 *
		 * @example
		 * // Access the service provider.
		 * $provider = $init->service_provider();
		 *
		 * @example
		 * // Register a new service.
		 * $init->service_provider()->register();
		 *
		 * @example
		 * // Boot all registered services.
		 * $init->service_provider()->boot();
		 */
		public function service_provider(): ServiceProvider {
			return ServiceProvider::instance( $this );
		}

		/**
		 * Get the dependency injection container.
		 *
		 * Provides access to the service container for resolving dependencies
		 * registered with the service provider.
		 *
		 * @return ServiceContainer The dependency injection container.
		 *
		 * @since 1.0.0
		 *
		 * @see Init::service_provider()       Gets the service provider.
		 * @see ServiceProvider::get_container() Gets the container from provider.
		 *
		 * @example
		 * // Get a registered service.
		 * $settings = $init->get_container()->get( Settings::class );
		 *
		 * @example
		 * // Check if a service is registered.
		 * if ( $init->get_container()->has( Updater::class ) ) {
		 *     $updater = $init->get_container()->get( Updater::class );
		 * }
		 *
		 * @example
		 * // Access from global function.
		 * $container = plugin_b()->get_container();
		 * $settings = $container->get( Settings::class );
		 */
		public function get_container(): ServiceContainer {
			 return $this->service_provider()->get_container();
		}
	}
```

### `AbstractSettings` class usages example

```php
<?php
	
	namespace StorePress\Example\Integrations;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	
	class AdminPage extends AbstractSettings {
		
		public function settings_id(): string {
			return 'example-plugin-settings';
		}
		
		public function get_default_sidebar(): void {
			echo 'Hello from default sidebar';
			
		}
		
		public function localize_strings(): array {
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
		
		
		
		   // Adding custom scripts.
    public function enqueue_scripts(): void {
        parent::enqueue_scripts();
        if ( $this->has_field_type( 'wc-enhanced-select' ) ) {
            wp_enqueue_style( 'woocommerce_admin_styles' );
            wp_enqueue_script( 'wc-enhanced-select' );
        }
    }
    
    // Adding Custom TASK: 
    public function get_custom_action_uri(): string {
       return wp_nonce_url( $this->get_settings_uri( array( 'action' => 'custom-action' ) ), $this->get_nonce_action() );
    }
    
    // Task: 02
    public function process_actions($current_action): void{
    
        parent::process_actions($current_action);
      
        if ( 'custom-action' === $current_action ) {
          $this->process_action_custom();
        }
    }
    
    // Task: 03
    public function process_action_custom(): void{
        check_admin_referer( $this->get_nonce_action() );
        
        
        
        // Process your task.
        
        
        
        wp_safe_redirect( $this->get_action_uri( array( 'message' => 'custom-action-done' ) ) ); 
        exit;
    }
    
    // Task: 04
    public function settings_messages(): void{
      
      parent::settings_messages();
      
      $message = $this->get_message_query_arg_value();
      
      if ( 'custom-action-done' === $message ) {
          $this->add_settings_message( 'Custom action done successfully.' );
      }
      
      if ( 'custom-action-fail' === $message ) {
          $this->add_settings_message( 'Custom action failed.', 'error' );
      }
    }
	}
```

```php
<?php
	
	namespace StorePress\Example\Services;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\Example\Init;
	use StorePress\Example\Integrations\AdminPage;
	
	/**
	 * Admin Menu Class.
	 *
	 * @name Settings
	 * @phpstan-use CallerTrait<Init>
	 * @method Init get_caller()
	 */
	
	class Settings extends AdminPage {
		
		use SingletonTrait;
		
		public function add_settings(): array {
			return array(
				'general' => 'General',
				
				//'pure' => 'Pure',
				'basic'   => array(
					'name'     => 'Basic',
					'sidebar'  => 25,
				),
				
				'advance' => array(
					'name'     => 'Advanced',
					'icon'     => 'dashicons dashicons-analytics',
					'sidebar'  => false,
					'hidden'   => false,
					'external' => false,
				),
				'rest' => 'Rest',
			);
		}
		
		// Naming Convention: add_<TAB ID>_settings_page()
    public function add_basic_settings_page() {
        echo 'custom page ui';
    }
		
		public function add_general_settings_fields() {
			return array(
				array(
					'type'        => 'section',
					'title'       => 'Section title',
					'description' => 'Section description',
				),
				
				array(
					'id'          => 'field-text-mn',
					'type'        => 'text',
					'title'       => 'Input Type text',
					'description' => 'Input Description',
					'placeholder' => 'Placeholder',
					'default'     => 'text field default',
					'suffix'      => 'px',
					'required'    => true,
					'add_tag'     => array('PRO'),
					// 'condition' => array('selector'=>$this->get_field_selector('grps')),
					// 'html_datalist' => array('yes','no'),
					// 'show_in_rest'    => array( 'name' => 'custom_rest_id' ),
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				
				array(
					'id'          => 'grps',
					'type'        => 'toggle',
					'title'       => 'Show Grpups',
					'default'     => 'no',
					'tooltip'      => 'Textarea Help tooltip',
					'required'    => true,
					'add_tag'    => array('NEW', '#d63639'),
					// 'options'=>array('key'=> 'value','key2'=> 'value2')
				),
				
				
				array(
					'condition'=>array('selector'=>$this->get_field_selector('grps')),
					'id'          => 'input_group',
					'type'        => 'group',
					// 'show_in_rest'        => false,
					'title'       => 'Input text 01 general',
					'description' => 'Input desc of 01',
					'tooltip'      => 'Input Group Help Tooltip',
					'fields'      => array(
						
						array(
							'id'          => 'inputaxxx',
							'type'        => 'text',
							'title'       => 'Single Toggle',
							'placeholder' => 'Abcd',
							'default'     => 'yes',
							'html_datalist'=>array('yes','no'),
							'class'=>array('large-text'),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
						
						array(
							'id'          => 'field-color',
							'type'        => 'color',
							'title'       => 'Input Type Color',
							'description' => 'Input Type Color',
							'placeholder' => 'Placeholder',
							'default'     => '#ffccff',
							'html_datalist'=>array('#dddddd','#eeeeee'),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
							// 'required'    => true,
							//'class'       => array( 'large-text', 'code', 'custom-class' )
						),
						
						array(
							'id'          => 'inputakk',
							'type'        => 'unit',
							'title'       => 'Single Unit',
							'placeholder' => '',
							'default'     => '20px',
							'units'=>     array('%','px'),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
						
						array(
							'id'          => 'inputas',
							'type'        => 'toggle',
							'title'       => 'Single Toggle',
							'placeholder' => 'Abcd',
							'default'     => 'no',
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
							//'options'=>array('X', 'Y', 'Z'),
						),
						array(
							'id'          => 'input2',
							'type'        => 'checkbox',
							'title'       => 'Single Checkbox',
							'placeholder' => 'Abcd',
							'default'     => 'yes',
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
						array(
							//'show_in_rest'    => false,
							'condition'=>array('selector'=>$this->get_group_field_selector('input_group','inputas')),
							'id'                => 'input5',
							'type'              => 'number',
							'title'             => 'Width WW',
							'description'       => 'Input desc of 01',
							'placeholder'       => 'Abcd',
							'default'           => '100',
							'suffix'            => 'x',
							'sanitize_callback' => 'absint',
							'html_attributes'   => array( 'min' => 10 ),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
						array(
							//'show_in_rest'    => false,
							'id'              => 'input45',
							'type'            => 'textarea',
							'title'           => 'Width',
							'description'     => 'Input desc of 01',
							'placeholder'     => 'Abcd',
							'default'         => '100',
							'suffix'          => 'x',
							'html_attributes' => array( 'min' => 10 ),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
						
						
						array(
							'id'              => 'inputse2xx',
							'type'            => 'select',
							'title'           => 'Int value',
							'description'     => 'Input desc of 01<code>rxxx</code>',
							// 'default'     => array( 'home3', 'home1' ),
							// 'multiple'    => true,
							'default'         => '2',
							'class'=>array('x', 'y'),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
							'html_attributes' => array( 'data-demo' => true ),
							'options'         => array(
								'1' => 'Home One',
								'2' => 'Home Two',
								'3' => 'Home three',
							)
						),
						
						array(
							'id'          => 'input_wi',
							'type'        => 'radio',
							'title'       => 'Width X',
							'placeholder' => 'Abcd',
							'default'     => 'y',
							'options'     => array(
								'x' => 'Home X',
								'y' => 'Home Y',
								'z' => 'Home Z',
							),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
						array(
							'id'      => 'input_wx',
							'type'    => 'checkbox',
							'title'   => 'Multi Checkbox',
							'default' => array( 'y', 'z' ),
							'options' => array(
								'x' => 'Home X',
								'y' => 'Home Y',
								'z' => 'Home Z',
							),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
						
						array(
							'id'      => 'input_wewdsf',
							'type'    => 'toggle',
							'title'   => 'Multi Toggle Checkbox',
							'default' => array( 'y', 'z' ),
							'options' => array(
								'x' => 'Home X',
								'y' => 'Home Y',
								'z' => 'Home Z',
							),
							'tooltip'      => 'Textarea Help tooltip',
							'required'    => true,
						),
					),
				),
				
				////////////
				array(
					'id'           => 'field-password',
					'type'         => 'password',
					'title'        => 'Input Type Password',
					'description'  => 'Input Description',
					'placeholder'  => 'Placeholder',
					'default'      => 'text field default',
					//'suffix'       => 'px',
					'required'     => true,
					'tooltip'      => 'Password Help tooltip',
					'condition'=>array('selector'=>$this->get_field_selector('field-textarea')),
					
					//'show_in_rest' => array( 'name' => 'custom_rest_id' ),
					//'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'           => 'field-textarea',
					'type'         => 'textarea',
					'title'        => 'Input Type text',
					'description'  => 'Input Description',
					'placeholder'  => 'Placeholder',
					'default'      => '',
					'suffix'       => 'px',
					'required'     => true,
					'tooltip'      => 'Textarea Help tooltip',
					//'show_in_rest' => array( 'name' => 'custom_rest_id' ),
					//'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-text',
					'type'        => 'text',
					'title'       => 'Input Type text',
					'description' => 'Input Description',
					'placeholder' => 'Placeholder',
					'default'     => 'text field default',
					'suffix'      => 'px',
					'required'    => true,
					// 'html_datalist'=>array('yes','no'),
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-number',
					'type'        => 'number',
					'title'       => 'Input Type Number',
					'description' => 'Input Type Number',
					'placeholder' => '',
					'default'     => '1',
					'suffix'      => 'px',
					// 'required'    => true,
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				///////
				
				array(
					'id'          => 'field-color',
					'type'        => 'color',
					'title'       => 'Input Type Color',
					'description' => 'Input Type Color',
					'placeholder' => 'Placeholder',
					'default'     => '#ffccff',
					'html_datalist'=>array('#dddddd','#eeeeee'),
					// 'required'    => true,
					// 'show_in_rest'    => 'fieldColor',
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-radio',
					'type'        => 'radio',
					'title'       => 'Input Type Radio',
					'description' => 'Input Type Radio',
					'placeholder' => 'Placeholder',
					'default'     => 'y',
					'options'     => array(
						'x' => 'Home X',
						'y' => 'Home Y',
						'z' => 'Home Z',
					)
					// 'required'    => true,
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				// license
				array(
					'id'          => 'license',
					'type'        => 'code',
					'title'       => 'License',
					'private'     => true,
					'show_in_rest' => false,
					'description' => 'Input for license',
					'placeholder' => 'xxxx-xxxx-xxxx',
					//'class'       => 'code'
				),
				
				array(
					'id'          => 'input-single-check',
					'type'        => 'checkbox',
					'title'       => 'Single Checkbox Full',
					'description' => 'Single Checkbox Full desc',
					'default'     => 'yes',
					'full_width'=>true,
					'add_tag'    => array('NEW', '#d63639'),
				),
				
				array(
					'id'          => 'input-single-toggle',
					'type'        => 'toggle',
					'title'       => 'Single Toggle Full',
					'description' => 'Single Checkbox Full desc',
					'default'     => 'yes',
					// 'full_width'=>true
				),
				
				array(
					'id'      => 'input-multi-check',
					'type'    => 'checkbox',
					'title'   => 'Multiple Checkbox Full',
					'description' => 'Multiple Checkbox  Full desc',
					'default' => 'yes',
					'options' => array(
						'x'     => 'Home X',
						'y'     => 'Home Y',
						'new'   => array(
							'label'       => 'New',
							'description' => 'New Item',
						),
						'z'     => 'Home Z',
						'alpha' => 'Alpha',
						'yes' => 'YES',
					
					),
				),
				
				array(
					'id'      => 'input-multi-check-toggle',
					'type'    => 'toggle',
					'title'   => 'Multiple Toggle Full',
					'default' => 'yes',
					'options' => array(
						'x'     => 'Home X',
						'new'   => array(
							'label'       => 'New',
							'description' => 'New Item',
						),
						'y'     => 'Home Y',
						'z'     => 'Home Z',
						'alpha' => 'Alpha',
					)
				),
				
				array(
					'id'          => 'inputr',
					'type'        => 'radio',
					'title'       => 'Input text 01 general',
					'description' => 'Input desc of 01',
					'default'     => 'home2',
					'options'     => array(
						'home'  => 'Home One',
						'home2' => 'Home Twos',
					)
				),
				
				array(
					'id'          => 'inputc',
					'type'        => 'checkbox',
					'title'       => 'Input text 01 general single checkbox',
					'description' => 'Input desc of 01',
					'default'     => 'home3',
					'options'     => array(
						'home1' => 'Home One',
						'home3' => 'Home 3',
						'home2' => 'Home 2',
					)
				),
				
				array(
					'id'              => 'inputse',
					'type'            => 'wc-enhanced-select',
					'title'           => '2 Input text 01 general single selectbox',
					'description'     => 'Input desc of 01<code>xxx</code>',
					// 'default'     => array( 'home3', 'home1' ),
					// 'multiple'    => true,
					'default'         => 'home3',
					'class'=>array('x', 'y'),
					'html_attributes' => array( 'data-demo' => true ),
					'options'         => array(
						'home1' => 'Home One',
						'home3' => 'Home 3',
						'home2' => 'Home 2',
					)
				),
				
				array(
					'id'              => 'inputse2xx',
					'type'            => 'wc-enhanced-select',
					'title'           => 'Int value',
					'description'     => 'Input desc of 01<code>rxxx</code>',
					// 'default'     => array( 'home3', 'home1' ),
					// 'multiple'    => true,
					'default'         => '2',
					'class'=>array('x', 'y'),
					'html_attributes' => array( 'data-demo' => true ),
					'options'         => array(
						'1' => 'Home One',
						'2' => 'Home Two',
						'3' => 'Home three',
					)
				),
				
				array(
					'id'          => 'input2',
					'type'        => 'text',
					'title'       => 'Input text 02',
					'description' => 'Input desc of 02',
					'default'     => '',
					'placeholder' => 'Abcd 02'
				),
				
				array(
					'id'          => 'inputunit',
					'type'        => 'unit',
					'title'       => 'Input text unit',
					'description' => 'Input desc of unit',
					'default'     => '10px',
					'html_attributes' => array( 'min' => 0, 'max'=>100, 'step'=>5 ),
					'units'=>array('px', '%', 'em', 'rem'),
					'condition'=>array('selector'=>$this->get_field_selector('input2')),
				),
				
				array(
					'type'        => 'section',
					'title'       => 'Section 02',
					'description' => 'Section of 02',
				),
			);
		}
		
		public function add_rest_settings_fields() {
			return array(
				array(
					'type'        => 'section',
					'title'       => 'Section rest',
					'description' => 'Section description',
				),
				
				array(
					'id'           => 'field-textarea-x',
					'type'         => 'textarea',
					'title'        => 'Input Type text',
					'description'  => 'Input Description',
					'placeholder'  => 'Placeholder',
					'default'      => 'text field default',
					'suffix'       => 'px',
					'required'     => true,
					'add_tag'    => array('NEW'),
					// 'show_in_rest' => array( 'name' => 'custom_rest_id' ),
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-text-x',
					'type'        => 'text',
					'title'       => 'Input Type text',
					'description' => 'Input Description',
					'placeholder' => 'Placeholder',
					// 'default'     => 'text field default',
					'suffix'      => 'px',
					// 'required'    => true,
					'add_tag'    => array('PRO', '#d63639'),
					// 'show_in_rest'    => array('name'=>'fieldTextX'),
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-text-x-dep',
					'type'        => 'unit',
					'title'       => 'Input Type Unit extra comp',
					'description' => 'Input Type Number',
					'placeholder' => '',
					'default'     => '10px',
					'html_attributes' => array( 'min' => 0, 'max'=>100, 'step'=>5 ),
					'units'=>array('px', '%', 'em', 'rem'),
					'condition'=>array('selector'=> $this->get_field_selector('field-text-x')),
					// 'required'    => true,
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-text-x-dep-text',
					'type'        => 'text',
					'title'       => 'Input Type Unit extra comp 2',
					'description' => 'Input Type Number',
					'placeholder' => '',
					// 'default'     => '',
					'condition'=>array('selector'=>$this->get_field_selector('field-text-x')),
					// 'required'    => true,
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				
				
				array(
					'id'          => 'field-number-x',
					'type'        => 'number',
					'title'       => 'Input Type Number',
					'description' => 'Input Type Number',
					'placeholder' => '',
					'default'     => '1',
					'suffix'      => 'px',
					'add_tag'    => array('BETA', '#000'),
					// 'required'    => true,
					'show_in_rest'    => array('name'=>'fieldNumberX'),
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-extra2-x',
					'type'        => 'number',
					'title'       => 'Input Type Number extra',
					'description' => 'Input Type Number',
					'placeholder' => '',
					'default'     => '1',
					'suffix'      => 'px',
					'show_in_rest'    => 'fieldExtraX',
					// 'required'    => true,
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
				array(
					'id'          => 'field-extra2-unit',
					'type'        => 'unit',
					'title'       => 'Input Type Unit extra',
					'description' => 'Input Type Number',
					'placeholder' => '',
					'default'     => '10px',
					'show_in_rest'    => 'fieldExtra2Unit',
					'html_attributes' => array( 'min' => 0, 'max'=>100, 'step'=>5 ),
					'units'=>array('px', '%', 'em', 'rem'),
					// 'required'    => true,
					// 'class'       => array( 'large-text', 'code', 'custom-class' )
				),
				
			);
		}
	}
```

### REST API

- URL will be: `/wp-json/<page_slug>/<rest_api_version>/settings`
- Default IS: `/wp-json/<page_slug>/v1/settings`
- Example: `/wp-json/plugin-a/v1/settings`

### WordPress Data Store Usages example

```js
import { select } from '@wordpress/data';

// For a single record (no ID needed if your endpoint returns one object)
const settings = select( 'core' ).getEntityRecord( '<parent_slug>', '<page_slug>' );
const settings = wp.data.select( 'core' ).getEntityRecord( '<parent_slug>', '<page_slug>' );

// Or use the resolver hook in a component
import { useEntityRecord } from '@wordpress/core-data';

function MyComponent() {
  const { record, isResolving } = useEntityRecord( 'storepress', '<page_slug>' );

  if ( isResolving ) return <p>Loading...</p>;

  return <div>{ record?.['field-text'] }</div>;
}
```

NOTE: If parent menu is a page like:

```php
public function parent_menu(): string {
		return 'edit.php?post_type=wporg_product';
}
```

It will create like:

```js
select( 'core' ).getEntityRecord( '<show_in_rest>', '<rest_api_base>' );
select( 'core' ).getEntityRecord( 'plugin-name/v1', 'settings' );
```

- See: [@wordpress/core-data](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-core-data/)


### Section data structure

```php
<?php
array(
    'type'        => 'section',
    'title'       => 'Section Title',
    'description' => 'Section Description',
)
```

### Field data options

```php
<?php

array(
    'id'          => 'input3', // Field ID.
    'type'        => 'text', // text, unit, password, toggle, code, small-text, tiny-text, large-text, textarea, email, url, number, color, select, wc-enhanced-select, radio, checkbox
    'title'       => 'Input Label',
    
    // Optional.
    'full_width' => true, // To make field full width. Just remove this key if do not want to use.
    
    'add_tag' => "PRO", // Add TAG
    'add_tag' => array("PRO", 'BACKGROUND COLOR HEX CODE'), // Add PRO Label
    'add_tag' => array("BETA", 'BACKGROUND COLOR HEX CODE', 'TEXT COLOR HEX CODE'), // Add PRO Label
    
    'description' => 'Input field description',
    
    'default'       => 'Hello World', //  default value can be string or array
    'default'       => array('x','y'), //  default value can be string or array
    
    'placeholder' => '' // Placeholder
    'suffix'      => '' // Field suffix.
    'html_attributes' => array('min' => 10) // Custom html attributes.
    'html_datalist'   => array('value 1', 'value 2') // HTML Datalist for suggestion.
    'required'    => true, // If field is required and cannot be empty.
    'private'     => true, // Private field does not delete from db during reset all action trigger.
    'multiple'    => true, // for select box 
    'class'       => array( 'large-text', 'code', 'custom-class' ),
    'tooltip'     => 'Textarea Help tooltip',
    'condition'   => array( 'selector'=>$this->get_field_selector('input2') ), // Conditional field, show or hide based on other input value.
    'condition'   => array( 'selector'=>$this->get_field_selector('input2'), 'value'=>'hello' ),
    'units'       => array('px', '%', 'em', 'rem'), // For unit type

    'sanitize_callback'=>'absint', // Use custom sanitize function. Default is: sanitize_text_field.
    'show_in_rest'    => true, // Hide from rest api field. Default is: true
    'show_in_rest'    => 'custom_rest_id', // Change field id on rest api.
    'show_in_rest'    => array( 'name'=>'custom_rest_id' ), // Change field id on rest api.
    'show_in_rest'    => array( 'name'=>'custom_rest_id', 'schema'=>array() ), // Add input schema for REST Api. See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
    // Options array for select, radio and checkbox [key=>value]
    // If checkbox have no options or value, default will be yes|no
    'options' => array(
        'x' => 'Home X',
        'y' => 'Home Y',
        'z' => 'Home Z',
        'new'   => array(
            'label' => 'New',
            'description' => 'New Item',
        ),
    )
),
```

### `AbstractProPluginInCompatibility`  class usages example

- Show notice for incompatible pro or extended plugin.

```php
<?php
	
	namespace StorePress\Example\Integrations;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	use StorePress\AdminUtils\Abstracts\AbstractProPluginInCompatibility;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\Example\Init;
	
	/**
	 * Updater Class.
	 *
	 * @name ProPluginInCompatibility
	 * @phpstan-use CallerTrait<Init>
	 * @method Init get_caller()
	 */
	
	class ProPluginInCompatibility extends AbstractProPluginInCompatibility {
		
		use SingletonTrait;
		
		public function compatible_version(): string {
			return '3.0.0';
		}
		
		public function pro_plugin_file(): string {
			return 'plugin-pro/plugin-pro.php'; // OR FILE CONSTANCE OF PRO PLUGIN FILE.
		}
		
		public function localize_notice_format(): string {
			// translators: 1: Extended Plugin Name. 2: Extended Plugin Version. 3: Extended Plugin Compatible Version.
			return 'You are using an incompatible version of <strong>%1$s - (%2$s)</strong>. Please upgrade to version <strong>%3$s</strong> or upper.';
		}
	}
	
```

### `AbstractUpdater`  class usages example

- NOTE: Update server and client server should not be in same WordPress setup.

- You must add `Update URI:` on plugin file header to perform update.

```php
<?php
/**
 * Plugin Name: Plugin A
 * Tested up to: 6.4.1
 * Update URI: https://update.example.com/
*/
```

### `Updater.php` file

```php
<?php
<?php
	
	namespace StorePress\Example\Integrations;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	use StorePress\AdminUtils\Abstracts\AbstractUpdater;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\Example\Init;
	
	/**
	 * Updater Class.
	 *
	 * @name Updater
	 * @phpstan-use CallerTrait<Init>
	 * @method Init get_caller()
	 */
	
	class Updater extends AbstractUpdater {
		
		use SingletonTrait;
		
		public function license_key(): string {
			// $this->get_caller()->get_container()->get( Settings::class)->get_option( 'license' )
			return 'hello';
		}
		
		public function product_id(): int {
			return 123450;
		}
		
		public function update_server_path(): string {
			return '/storepress-admin-utils/wp-json/plugin-updater/v1/check-update';
		}
		
		public function localize_strings(): array {
			
			$name = $this->get_plugin_name();
			
			return array(
				'license_key_empty_message'     => 'License key is not available.',
				'check_update_link_text'        => sprintf('Check Update %s', $name),
				'rollback_changelog_title'      => 'Changelog',
				'rollback_action_running'       => 'Rolling back',
				'rollback_action_button'        => sprintf('Rollback %s', $name),
				'rollback_cancel_button'        => 'Cancel',
				'rollback_current_version'      => 'Current version',
				'rollback_last_updated'         => 'Last updated %s ago.',
				'rollback_view_changelog'       => sprintf('View Changelog for %s', $name),
				'rollback_page_title'           => sprintf( 'Rollback Plugin %s', $name),
				'rollback_link_text'            => sprintf('Rollback %s', $name),
				'rollback_failed'               => 'Rollback failed.',
				'rollback_success'              => 'Rollback success: %s rolled back to version %s.',
				'rollback_plugin_not_available' => 'Plugin is not available.',
				'rollback_no_access'            => 'Sorry, you are not allowed to rollback plugins for this site.',
				'rollback_not_available'        => 'Rollback is not available for plugin: %s',
				'rollback_no_target_version'    => 'Plugin version not selected.',
			);
		}
		
		// If you need to send additional arguments to update server.
		// Check get_request_args() method.
		public function additional_request_args(): array {
			return array( 'domain'=> $this->get_client_hostname() );
		}
	}
```

## `AbstractDeactivationFeedback`  class usages example

```php
<?php
	
	namespace StorePress\Example\Integrations;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	use StorePress\AdminUtils\Abstracts\AbstractDeactivationFeedback;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\Example\Init;
	
	/**
	 * Changelog Dialog Class.
	 *
	 * @name DeactivationFeedback
	 * @phpstan-use CallerTrait<Init>
	 * @method Init get_caller()
	 */
	
	class DeactivationFeedback extends AbstractDeactivationFeedback {
		
		use SingletonTrait;
		
		/**
		 * Get deactivation title.
		 *
		 * @return string
		 */
		public function title(): string {
			return 'QUICK FEEDBACK from plugin B';
		}
		
		public function sub_title(): string {
			return 'May we have a little info about why you are deactivating?';
		}
		
		/**
		 * Set API URL to send feedback.
		 *
		 * @return string
		 * @example https://example.com/wp-json/__NAMESPACE__/v1/deactivate
		 */
		public function api_url(): string {
			return 'http://sites.local/storepress-admin-utils/wp-json/feedback/v1/deactivate';
		}
		
		/**
		 * Get saved settings data.
		 *
		 * @return array<string, mixed>
		 */
		public function options(): array {
			// $this->get_caller()->get_container()->get( Settings::class)->get_options();
			return array();
		}
		
		public function get_buttons(): array {
			
			return array(
				array(
					'type'       => 'button',
					'label'      => __( 'Send feedback & Deactivate' ),
					'attributes' => array(
						'disabled'        => true,
						'type'            => 'submit',
						'data-action'     => 'submit',
						'data-label'      => __( 'Send feedback & Deactivate' ),
						'data-processing' => __( 'Deactivate...' ),
						'class'           => array( 'button', 'button-primary' ),
					),
					'spinner'    => true,
				),
				array(
					'type'       => 'link',
					'label'      => __( 'Skip & Deactivate' ),
					'attributes' => array(
						'href'  => '#',
						'class' => array( 'skip-deactivate' ),
					),
				),
			);
		}
		
		public function get_reasons(): array {
			$current_user = wp_get_current_user();
			$name = $this->get_plugin_name();
			
			return array(
				'temporary_deactivation' => array(
					'title'             => esc_html__( 'It\'s a temporary deactivation.', 'woo-variation-swatches' ),
				),
				
				'dont_know_about' => array(
					'title'             => esc_html__( 'I couldn\'t understand how to make it work.', 'woo-variation-swatches' ),
					'message'             => sprintf( 'Its Plugin %s.', $name),
				),
				
				'no_longer_needed' => array(
					'title'             => esc_html__( 'I no longer need the plugin.', 'woo-variation-swatches' ),
				),
				
				'found_a_better_plugin' => array(
					'title'             => esc_html__( 'I found a better plugin.', 'woo-variation-swatches' ),
					'input' => array(
						'placeholder'=>esc_html__( 'Please let us know which one', 'woo-variation-swatches' ),
					),
				),
				
				'broke_site_layout' => array(
					'title'             => __( 'The plugin <strong>broke my layout</strong> or some functionality.', 'woo-variation-swatches' ),
					'message'           => __( '<a target="_blank" href="https://getwooplugins.com/tickets/">Please open a support ticket</a>, we will fix it immediately.', 'woo-variation-swatches' ),
				),
				
				'plugin_setup_help' => array(
					'title'             => __( 'I need someone to <strong>setup this plugin.</strong>', 'woo-variation-swatches' ),
					'input' => array(
						'placeholder'=>esc_html__( 'Your email address.', 'woo-variation-swatches' ),
						'value'=>sanitize_email( $current_user->user_email )
					),
					'message'             => __( 'Please provide your email address to contact with you <br>and help you to set up and configure this plugin.', 'woo-variation-swatches' ),
				),
				
				'plugin_config_too_complicated' => array(
					'title'             => __( 'The plugin is <strong>too complicated to configure.</strong>', 'woo-variation-swatches' ),
					'message'             => __( '<a target="_blank" href="https://getwooplugins.com/documentation/woocommerce-variation-swatches/">Have you checked our documentation?</a>.', 'woo-variation-swatches' ),
				),
				
				'need_specific_feature' => array(
					'title'             => esc_html__( 'I need specific feature that you don\'t support.', 'woo-variation-swatches' ),
					
					'input' => array(
						'placeholder'=>esc_html__( 'Please share with us.', 'woo-variation-swatches' ),
					),
				),
				
				'other' => array(
					'title'             => esc_html__( 'Other', 'woo-variation-swatches' ),
					'input' => array(
						'placeholder'=>esc_html__( 'Please share the reason', 'woo-variation-swatches' ),
					),
				)
			);
		}
		
		/**
		 * Dialog width.  - Optional.
		 *
		 * @return string
		 */
		/*public function get_dialog_width(): string {
			return ''; // 600px
		}*/
	}
```


## `BaseServiceContainer` class usages example

```php
<?php

	declare( strict_types=1 );

	namespace StorePress\Example\ServiceContainers;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\ServiceContainers\BaseServiceContainer;
	use StorePress\AdminUtils\Traits\SingletonTrait;

	/**
	 * Dependency Injection Container Class.
	 *
	 * Extends the base service container to provide plugin-specific dependency
	 * injection capabilities. Uses the singleton pattern to ensure a single
	 * container instance throughout the plugin's lifecycle. Inherits service
	 * registration, resolution, and management functionality from BaseServiceContainer.
	 *
	 * @name ServiceContainer
	 */
	class ServiceContainer extends BaseServiceContainer {
		use SingletonTrait;
	}
```


## `AbstractServiceProvider` class usages example

```php
<?php

	declare( strict_types=1 );

	namespace StorePress\Example\ServiceProviders;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	use StorePress\AdminUtils\Abstracts\AbstractServiceProvider;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\Example\Integrations\DeactivationFeedback;
	use StorePress\Example\Integrations\ProPluginInCompatibility;
	use StorePress\Example\Services\Settings;
	use StorePress\Example\Integrations\Updater;
	use StorePress\Example\ServiceContainers\ServiceContainer;
	
	/**
	 * Plugin Service Provider Class.
	 *
	 * Extends AbstractServiceProvider to manage plugin-specific service registration
	 * and bootstrapping. Uses the singleton pattern to ensure a single provider
	 * instance manages all service lifecycle operations. Registers the Updater
	 * service and handles its initialization during the boot phase.
	 *
	 * @name ServiceProvider
	 */
	class ServiceProvider extends AbstractServiceProvider {
		
		use SingletonTrait;
		
		public function get_container(): ServiceContainer {
			return ServiceContainer::instance();
		}

		/**
		 * Register services with the container.
		 *
		 * Registers the Updater service as a factory closure that instantiates
		 * the Updater with the caller (Init) instance. Called during the service
		 * provider initialization phase before boot().
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function register(): void {
			
			$this->get_container()->register(
				Updater::class,
				function () {
					return Updater::instance( $this->get_caller() );
				}
			);
			
			$this->get_container()->register(
				DeactivationFeedback::class,
				function () {
					return DeactivationFeedback::instance( $this->get_caller() );
				}
			);
			
			$this->get_container()->register(
				ProPluginInCompatibility::class,
				function () {
					return ProPluginInCompatibility::instance( $this->get_caller() );
				}
			);
			
			/*$this->get_container()->register(
				AdminMenu::class,
				function () {
					return AdminMenu::instance( $this->get_caller() );
				}
			);*/
			$this->get_container()->register(
				Settings::class,
				function () {
					return Settings::instance( $this->get_caller() );
				}
			);
		}

		/**
		 * Bootstrap services after all providers are registered.
		 *
		 * Initializes registered services by resolving the Updater service
		 * from the container. Called after all services are registered to
		 * perform any necessary setup or initialization logic.
		 *
		 * @return void
		 * @since 2.0.0
		 */
		public function boot(): void {
			$this->get_container()->get( Updater::class );
			$this->get_container()->get( DeactivationFeedback::class );
			$this->get_container()->get( ProPluginInCompatibility::class );
			//$this->get_container()->get( AdminMenu::class );
			$this->get_container()->get( Settings::class );
		}
	}
```

## Preparing Update Server

```php
<?php

// Based on Update URI:  
// https://update.example.com/wp-json/plugin-updater/v1/check-update
add_action( 'rest_api_init', function () {
    register_rest_route( 'plugin-updater/v1', '/check-update', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'updater_get_plugin',
        'permission_callback' => '__return_true',
    ] );
} );

/**
 * @param WP_REST_Request $request REST request instance.
 *
 * @return WP_REST_Response|WP_Error WP_REST_Response instance if the plugin was found,
 *                                    WP_Error if the plugin isn't found.
 *                                   
 * @see Updater::prepare_remote_data()
 */
function updater_get_plugin( WP_REST_Request $request ) {
    
    $params = $request->get_params();
            
    $type        = $request->get_param( 'type' ); // plugins
    $plugin_name = $request->get_param( 'name' ); // plugin-dir/plugin-name.php
    $license_key = $request->get_param( 'license_key' ); // plugin
    $product_id  = $request->get_param( 'product_id' ); // plugin
    $args        = (array) $request->get_param( 'args' ); // plugin additional arguments.
    
    
    /**
     * $data [
     *
     *     'description'=>'',
     * 
     *     'active_installs'=>'1000',
     *
     *     'faq'=>'',
     * 
     *     'changelog'=>'',
     *
     *     'new_version'=>'x.x.x', // * REQUIRED
     * 
		 *     'banners'=>['low'=>'https://ps.w.org/woocommerce/assets/banner-772x250.png', 'high'=>'https://ps.w.org/woocommerce/assets/banner-1544x500.png'],
		 *
		 *     'banners_rtl'=>[],
		 *
		 *     Using SVG Icon Recommended.
		 *
		 *     'icons'=>[ 'svg' => 'https://ps.w.org/woocommerce/assets/icon.svg', '2x'  => 'https://ps.w.org/woocommerce/assets/icon-256x256.png', '1x'  => 'https://ps.w.org/woocommerce/assets/icon-128x128.png' ], // icons.
		 *  
		 *     'screenshots'=>[['src'=>'', 'caption'=>'' ], ['src'=>'', 'caption'=>''], ['src'=>'', 'caption'=>'']],
     *
     *     'last_updated'=>'2023-11-11 3:24pm GMT+6',
     *
     *     'upgrade_notice'=>'',
     * 
     *     'upgrade_notice'=>['1.1.0'=>'Notice for this version', '1.2.0'=>'Notice for 1.2.0 version'],
     *
     *     'package'=>'https://plugin-server.com/plugin-2.0.0.zip', // * REQUIRED ABSOLUTE URL
     *
     *     'tested'=>'x.x.x', // WP testes Version
     *
     *     'requires'=>'x.x.x', // Minimum Required WP
     *
     *     'requires_php'=>'x.x.x', // Minimum Required PHP
     *
     *     'requires_plugins'=> ['woocommerce'], // Requires Plugins
     *
     *     'versions'=> [  '1.0.0' => 'https://plugin-server.com/plugin-1.0.0.zip', '2.0.0' => 'https://plugin-server.com/plugin-2.0.0.zip' ], // Available versions
     *
     *     'preview_link'=>'', // Plugin Preview Link
     * 
     *     'allow_rollback'=>'yes', // yes | no // * REQUIRED for ROLLBACK
     *
     * ]
     */
    
    $data = array(
        'new_version'    => '1.3.4',
        'last_updated'   => '2023-12-12 09:58pm GMT+6',
        'package'        =>'https://updater.example.com/plugin.zip', // After license verified.
        'upgrade_notice' => 'Its Fine',
        'changelog'      =>'Change log text',
        'versions'       => [  
                '1.0.0' => 'https://plugin-server.com/plugin-1.0.0.zip', 
                '2.0.0' => 'https://plugin-server.com/plugin-2.0.0.zip' 
        ], // Available versions
       'allow_rollback'=>'yes', // yes | no // * REQUIRED for ROLLBACK
    );
    
    return rest_ensure_response( $data );
}
```

## Preparing Feedback Server

```php
<?php

// Sample API:  
// https://state.example.com/wp-json/feedback/v1/deactivate
add_action( 'rest_api_init', function () {
    register_rest_route( 'feedback/v1', '/deactivate', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'store_deactivate_data',
        'permission_callback' => '__return_true',
    ] );
} );

/**
 * @param WP_REST_Request $request REST request instance.
 *
 * @return WP_REST_Response|WP_Error WP_REST_Response instance if the plugin was found,
 *                                    WP_Error if the plugin isn't found.
 *                                   
 * @see Deactivation_Feedback::send_feedback()
 */
 
function store_deactivate_data( WP_REST_Request $request ) {
    
    $params = $request->get_params();
            
    $feedback  = (array) $request->get_param( 'feedback' );
    $wordpress = (array) $request->get_param( 'wordpress' );
    $theme     = (array) $request->get_param( 'theme' );
    $plugins   = (array) $request->get_param( 'plugins' );
    $server    = (array) $request->get_param( 'server' );
    
    // Save data
    
    return rest_ensure_response( true );
}
```

## Best Practices to write plugin based on this package.

1. **Use Singleton Pattern** - All services should use `SingletonTrait`
2. **Use CallerTrait** - Access the `Init` instance and container
3. **Register Before Boot** - Always register services before booting
4. **Lazy Loading** - Services are only instantiated when resolved
5. **Single Responsibility** - Each service handles one concern
6. **Extend Base Classes** - Use abstract classes from AdminUtils
