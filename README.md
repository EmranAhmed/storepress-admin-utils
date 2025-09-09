# StorePress Admin Utils

StorePress Admin Utils is a comprehensive PHP library for WordPress that simplifies the creation of admin interfaces for plugins. It provides a structured, object-oriented approach to building settings pages, managing plugin updates, handling rollbacks, and displaying administrative notices.

## Core Features

## Settings Framework

The library's cornerstone is its powerful settings framework, which allows developers to create complex settings pages with multiple tabs and a wide variety of field types.

### Field Types

It supports a rich set of field types including `text`, `textarea`, `checkbox`, `radio`, `select`, `color`, `number`, and more advanced fields like `toggle` switches and `group` fields.

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

## Usage

### Plugin instance

```php
<?php

add_action( 'plugins_loaded', function () {

    $settings = Settings::instance();
    
    if ( ! is_admin() ) {
        print_r( $settings->get_option( 'input' ) );
        print_r( $settings->get_option( 'input', 'default' ) );
    }
} );
```

### Plugin `AdminPage.php`

```php
<?php

namespace Plugin_A;

class AdminPage extends \StorePress\AdminUtils\Settings {
        
    public function localize_strings(): array {
        return array(
            'unsaved_warning_text'          => 'The changes you made will be lost if you navigate away from this page.',
            'reset_warning_text'            => 'Are you sure to reset?',
            'reset_button_text'             => 'Reset All',
            'settings_link_text'            => 'Settings',
            'settings_updated_message_text' => 'Settings Saved',
            'settings_deleted_message_text' => 'Settings Reset',
            'settings_error_message_text'   => 'Settings Not saved.',
        );
    }
    
    public function parent_menu_title(): string {
        return 'StorePress';
    }
    
    public function page_title(): string {
        return 'Plugin A Page Title';
    }
    
    /*
     // Parent menu id.
     public function parent_menu() {
        return 'edit.php?post_type=wporg_product';
     }
    */
    public function menu_title(): string {
        return 'Plugin A Menu';
    }
    
    // Settings page slug.
    public function page_id(): string {
        return 'plugin-a';
    }
    
    // Option name to save data.
    public function settings_id(): string {
        return 'plugin_a_option';
    }
    
    public function plugin_file(): string {
        return __FILE__;
    }
    
    public function get_default_sidebar() {
        echo 'Default sidebar';
    }
    
    // To Disable rest api.
    // URL will be: `/wp-json/<page_id>/<rest_api_version>/settings`
    public function show_in_rest(): ?string {
        return false;
    }
    // NOTE: You have to create and proper access to get REST API response.
    // Create: "Application Passwords" from "WP Admin -> Users -> Profile" to use.
    // Will return: /wp-json/my-custom-uri/settings
    public function show_in_rest(): ?string {
        return 'my-custom-uri';
    }
    
    // Settings and Rest API Display Capability. Default is: manage_options
    public function capability(): string {
    return 'edit_posts';
    }
    
    // Change rest api version. Default is: v1
    public function rest_api_version(): string {
    return 'v2';
    }
    
    // Adding custom scripts.
    public function enqueue_scripts() {
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
    public function process_actions($current_action){
    
        parent::process_actions($current_action);
      
        if ( 'custom-action' === $current_action ) {
          $this->process_action_custom();
        }
    }
    
    // Task: 03
    public function process_action_custom(){
        check_admin_referer( $this->get_nonce_action() );
        
        
        
        // Process your task.
        
        
        
        wp_safe_redirect( $this->get_action_uri( array( 'message' => 'custom-action-done' ) ) ); 
        exit;
    }
    
    // Task: 04
    public function settings_messages(){
      
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

### Plugin `AdminSettings.php`

```php
<?php

namespace Plugin_A;

class AdminSettings extends \Plugin_A\AdminPage {
    
    // Settings Tabs.
    public function add_settings(): array {
        return array(
            'general' => 'General',
            'basic'   => array( 
                    'name' => 'Basic', 
                    'sidebar' => 30, // Sidebar Width.
                ),
            'advance' => array( 
                    'name' => 'Advanced', 
                    'icon' => 'dashicons dashicons-analytics', 
                    'sidebar' => false, 
                    'hidden' => false, 
                    'external' => false 
                ),
        );
    }
    
    // Naming Convention: add_<TAB ID>_settings_sidebar()
    public function add_general_settings_sidebar() {
        echo 'Hello from general sidebar';
    }
    
    // Naming Convention: add_<TAB ID>_settings_fields()
    public function add_general_settings_fields() {
        return array(
            array(
                'type'        => 'section',
                'title'       => 'Section 01 General',
                'description' => 'Section of 01',
            ),
            
            array(
                'id'          => 'input',
                'type'        => 'text',
                'title'       => 'Input text 01 general',
                'description' => 'Input desc of 01 <code>xxx</code>',
                'placeholder' => 'Abcd',
                'default'     => 'ok',
                'html_datalist'=>array('yes','no'),
            ),
            array(
                'id'          => 'license',
                'type'        => 'text',
                'title'       => 'License',
                'private'     => true,
                //'description' => 'Input desc of 01',
                'placeholder' => 'xxxx',
                'class'       => 'code'
            ),
            
            array(
					      'id'          => 'inputunit',
					      'type'        => 'unit',
					      'title'       => 'Input text unit',
					      'description' => 'Input desc of unit',
					      'default'     => '10px',
					      'html_attributes' => array( 'min' => 0, 'max'=>100, 'step'=>'5' ),
					      'units'=>array('px', '%', 'em', 'rem')
				    ),
            
            array(
                'id'          => 'input_group',
                'type'        => 'group',
                'title'       => 'Input text 01 general',
                'description' => 'Input desc of 01',
                'fields'      => array(
                    array(
                        'id'          => 'input2',
                        'type'        => 'checkbox',
                        'title'       => 'Width',
                        'placeholder' => 'Abcd',
                        'default'     => 'yes'
                    ),
                    array(
                        'id'          => 'input5',
                        'type'        => 'number',
                        'title'       => 'Width',
                        'description' => 'Input desc of 01',
                        'placeholder' => 'Abcd',
                        'default'     => '100',
                        //'class'=>'tiny-text'
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
                        )
                    ),
                ),
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
                'id'          => 'inputse',
                'type'        => 'select',
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
                'id'          => 'input2',
                'type'        => 'text',
                'title'       => 'Input text 02',
                'description' => 'Input desc of 02',
                'value'       => 'Hello Worlds 2',
                'placeholder' => 'Abcd 02'
            ),
            
            array(
                'type'        => 'section',
                'title'       => 'Section 02',
                'description' => 'Section of 02',
            ),
        );
    }
    
    // Naming Convention: add_<TAB ID>_settings_fields()
    public function add_advance_settings_fields() {
        return array(
            array(
                'type'        => 'section',
                'title'       => 'Section 01 Advanced',
                'description' => 'Section of 01',
            ),
            
            array(
                'id'          => 'input3',
                'type'        => 'text',
                'title'       => 'Input text 01 advanced',
                'description' => 'Input desc of 01',
                'value'       => 'Hello Worlds',
                'placeholder' => 'Abcd'
            ),
            
            array(
                'id'          => 'input4',
                'type'        => 'text',
                'title'       => 'Input text 02',
                'description' => 'Input desc of 02',
                'value'       => 'Hello Worlds 2',
                'placeholder' => 'Abcd 02'
            ),
            
            
            array(
                'type'        => 'section',
                'title'       => 'Section 02',
                'description' => 'Section of 02',
            ),
        );
    }
    
    // Naming Convention: add_<TAB ID>_settings_page()
    public function add_basic_settings_page() {
        echo 'custom page ui';
    }
}
```

### Section data structure

```php
<?php
array(
    'type'        => 'section',
    'title'       => 'Section Title',
    'description' => 'Section Description',
)
```

### Field data structure

```php
<?php

array(
    'id'          => 'input3', // Field ID.
    'type'        => 'text', // text, unit, password, toggle, code, small-text, tiny-text, large-text, textarea, email, url, number, color, select, wc-enhanced-select, radio, checkbox
    'title'       => 'Input Label',
    
    // Optional.
    'full_width' => true, // To make field full width.
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
    'condition'   =>array('selector'=>'#input2'),
    'condition'   =>array('selector'=>'#input2', 'value'=>'hello'),
    'units'       =>array('px', '%', 'em', 'rem'), // For unit type

    'sanitize_callback'=>'absint', // Use custom sanitize function. Default is: sanitize_text_field.
    'show_in_rest'    => false, // Hide from rest api field. Default is: true
    'show_in_rest'    => array('name'=>'custom_rest_id'), // Change field id on rest api.
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

### Plugin `Settings.php` file

```php
<?php
namespace Plugin_A;

class Settings extends AdminSettings {
    use \StorePress\AdminUtils\Singleton;
}
```

- Now use `Settings::instance();` on `Plugin::init()`

### REST API

- URL will be: `/wp-json/<page_id>/<rest_api_version>/settings`

### Upgrade Notice

- Show notice for incompatible extended plugin.

```php
namespace Plugin_A;

class Upgrade_Notice extends \StorePress\AdminUtils\Upgrade_Notice {

    use \StorePress\AdminUtils\Singleton;
    
    public function plugin_file(): string {
        return plugin_a()->get_pro_plugin_file();
    }
   
    public function compatible_version(): string {
        return '3.0.0'; // passed from parent plugin
    }
    
    public function localize_notice_format(): string {
        return __( 'You are using an incompatible version of <strong>%1$s - (%2$s)</strong>. Please upgrade to version <strong>%3$s</strong> or upper.', 'plugin-x');
    }
    
    // Optional
    public function show_admin_notice(): bool {
        return true;
    }
    
    // Optional
    public function show_plugin_row_notice(): bool {
        return true;
    }
}
```

- Now use `Upgrade_Notice::instance();` on `Plugin::init()`

### Plugin Update

- NOTE: Update server and client server should not be same WordPress setup.

- You must add `Update URI:` on plugin file header to perform update.

```php
<?php
/**
 * Plugin Name: Plugin A
 * Tested up to: 6.4.1
 * Update URI: https://update.example.com/
*/
```

### Plugin `Updater.php` file

```php
<?php

namespace Plugin_A;

class Updater extends \StorePress\AdminUtils\Updater {

		use \StorePress\AdminUtils\Singleton;
    
    public function plugin_file(): string {
        return plugin_a()->get_plugin_file();
    }
    
    public function license_key(): string {
        return plugin_a()->get_option( 'license' );
    }
    
    public function product_id(): int {
        return 100;
    }
    
     /**
		 * Translatable Strings.
		 *
		 * @abstract
		 *
		 * @return array{
		 *      'license_key_empty_message'     => string,
		 *      'check_update_link_text'        => string,
		 *      'rollback_changelog_title'      => string,
		 *      'rollback_action_running'       => string,
		 *      'rollback_action_button'        => string,
		 *      'rollback_cancel_button'        => string,
		 *      'rollback_current_version'      => string,
		 *      'rollback_last_updated'         => string,
		 *      'rollback_view_changelog'       => string,
		 *      'rollback_page_title'           => string,
		 *      'rollback_link_text'            => string,
		 *      'rollback_failed'               => string,
		 *      'rollback_success'              => string,
		 *      'rollback_plugin_not_available' => string,
		 *      'rollback_no_access'            => string,
		 *      'rollback_not_available'        => string,
		 *      'rollback_no_target_version'    => string,
		 *  }
		 */
		public function localize_strings(): array {
			return array(
				'license_key_empty_message'     => 'License key is not available.',
				'check_update_link_text'        => 'Check Update',
				'rollback_changelog_title'      => 'Changelog',
				'rollback_action_running'       => 'Rolling back',
				'rollback_action_button'        => 'Rollback',
				'rollback_cancel_button'        => 'Cancel',
				'rollback_current_version'      => 'Current version',
				'rollback_last_updated'         => 'Last updated %s ago.',
				'rollback_view_changelog'       => 'View Changelog',
				'rollback_page_title'           => 'Rollback Plugin',
				'rollback_link_text'            => 'Rollback',
				'rollback_failed'               => 'Rollback failed.',
				'rollback_success'              => 'Rollback success: %s rolled back to version %s.',
				'rollback_plugin_not_available' => 'Plugin is not available.',
				'rollback_no_access'            => 'Sorry, you are not allowed to rollback plugins for this site.',
				'rollback_not_available'        => 'Rollback is not available for plugin: %s',
				'rollback_no_target_version'    => 'Plugin version not selected.',
			);
		}
    
    // Without hostname. Host name will prepend from Update URI 
    public function update_server_path(): string {
        return '/updater-api/wp-json/plugin-updater/v1/check-update';
    }
     
    // If you need to send additional arguments to update server.
    // Check get_request_args() method.
    public function additional_request_args(): array {
        return array(
            'domain'=> $this->get_client_hostname();
        );
    }
}
```

- Now use `Updater::instance();` on `Plugin::init()`

## Plugin Deactivate Feedback `InactiveFeedback.php`

```php
<?php
	
	namespace StorePress\A;
	
	defined( 'ABSPATH' ) || die( 'Keep Silent' );
	
	class InactiveFeedback extends \StorePress\AdminUtils\Deactivation_Feedback {
		
		use \StorePress\AdminUtils\Singleton;
		
		// Where to send feedback data.
		public function api_url(): string {
			return 'https://state.example.com/wp-json/feedback/v1/deactivate';
		}
		
		public function reasons(): array {
			$current_user = wp_get_current_user();
			
			return array(
				'temporary_deactivation' => array(
					'title' => esc_html__( 'It\'s a temporary deactivation.', 'text-domain' ),
				),
				
				'dont_know_about' => array(
					'title' => esc_html__( 'I couldn\'t understand how to make it work.', 'text-domain' ),
					'message' => __( 'Its Plugin A.', 'text-domain' ),
				),
				
				'no_longer_needed' => array(
					'title' => esc_html__( 'I no longer need the plugin.', 'text-domain' ),
				),
				
				'found_a_better_plugin' => array(
					'title' => esc_html__( 'I found a better plugin.', 'text-domain' ),
					'input' => array(
						'placeholder'=>esc_html__( 'Please let us know which one', 'text-domain' ),
					),
				),
				
				'broke_site_layout' => array(
					'title' => __( 'The plugin <strong>broke my layout</strong> or some functionality.', 'text-domain' ),
					'message' => __( '<a target="_blank" href="#">Please open a support ticket</a>, we will fix it immediately.', 'text-domain' ),
				),
				
				'plugin_setup_help' => array(
					'title' => __( 'I need someone to <strong>setup this plugin.</strong>', 'text-domain' ),
					'input' => array(
						'placeholder'=>esc_html__( 'Your email address.', 'woo-variation-swatches' ),
						'value'=>sanitize_email( $current_user->user_email )
					),
					'message' => __( 'Please provide your email address to contact with you <br />and help you to set up and configure this plugin.', 'text-domain' ),
				),
				
				'plugin_config_too_complicated' => array(
					'title' => __( 'The plugin is <strong>too complicated to configure.</strong>', 'text-domain' ),
					'message' => __( '<a target="_blank" href="#">Have you checked our documentation?</a>.', 'text-domain' ),
				),
				
				'need_specific_feature' => array(
					'title' => esc_html__( 'I need specific feature that you don\'t support.', 'text-domain' ),
					'input' => array(
						'placeholder'=>esc_html__( 'Please share with us.', 'text-domain' ),
					),
				),
				
				'other' => array(
					'title' => esc_html__( 'Other', 'text-domain' ),
					'input' => array(
						'placeholder'=>esc_html__( 'Please share the reason', 'text-domain' ),
					),
				)
			);
		}
		
		public function options(): array {
			return plugin_a()->get_settings()->get_options();
		}
		
		public function title(): string {
			return 'QUICK FEEDBACK';
		}
		
		public function sub_title(): string {
			return 'May we have a little info about why you are deactivating?';
		}
		
		public function plugin_file(): string {
			return plugin_a()->get_plugin_file();
		}
		/**
		 * Get Dialog Button.
		 *
		 * @return array<int, mixed>
		 * array(
		 *     array(
		 *         'type'       => 'button',
		 *         'label'      => __( 'Send feedback & Deactivate' ),
		 *         'attributes' => array(
		 *             'disabled'        => true,
		 *             'type'            => 'submit',
		 *             'data-action'     => 'submit',
		 *             'data-label'      => __( 'Send feedback & Deactivate' ),
		 *             'data-processing' => __( 'Deactivate...' ),
		 *             'class'           => array( 'button', 'button-primary' ),
		 *          ),
		 *         'spinner'    => true,
		 *     ),
		 *
		 *     array(
		 *         'type'       => 'link',
		 *         'label'      => __( 'Skip & Deactivate' ),
		 *         'attributes' => array(
		 *             'href'  => '#',
		 *             'class' => array( 'skip-deactivate' ),
		 *         ),
		 *     ),
		 * )
		 */
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
		
		/**
		 * Dialog width.  - Optional.
		 *
		 * @return string
		 */
		public function get_dialog_width(): string {
			return ''; // 600px
		}
	}
```

- Now use `InactiveFeedback::instance();` on `Plugin::init()`


## Update Server

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

## Feedback Server

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
