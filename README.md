# StorePress Admin Utils

Admin Utility functions for StorePress WordPress Plugin Projects.

## Installation

```php
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
                'description' => 'Input desc of 01',
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
    'type'        => 'text', // text, password, toggle, code, small-text, tiny-text, large-text, textarea, email, url, number, color, select, wc-enhanced-select, radio, checkbox
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
    /**
     * @return self
     */
    public static function instance() {
        static $instance = null;
        
        if ( is_null( $instance ) ) {
            $instance = new self();
        }
        
        return $instance;
    }
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
    /**
     * @return self
     */
    public static function instance() {
        static $instance = null;
        
        if ( is_null( $instance ) ) {
            $instance = new self();
        }
        
        return $instance;
    }
    
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
    /**
     * @return self
     */
    public static function instance() {
        static $instance = null;
        
        if ( is_null( $instance ) ) {
            $instance = new self();
        }
        
        return $instance;
    }
    
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
		 *      'license_key_empty_message': string,
		 *      'check_update_link_text': string,
		 *      'rollback_action_running': string,
		 *      'rollback_action_button': string,
		 *      'rollback_cancel_button': string,
		 *      'rollback_current_version': string,
		 *      'rollback_last_updated': string,
		 *      'rollback_view_changelog': string,
		 *      'rollback_page_title': string,
		 *      'rollback_page_title': string,
		 *      'rollback_link_text': string,
		 *      'rollback_failed': string,
		 *      'rollback_success': string,
		 *      'rollback_plugin_not_available': string,
		 *      'rollback_no_access': string,
		 *  }
		 */
		public function localize_strings(): array {
			return array(
				'license_key_empty_message'     => 'License key is not available.',
				'check_update_link_text'        => 'Check Update',
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
			);
		}
    
    // Without hostname. Host name will prepend from Update URI 
    public function update_server_path(): string {
        return '/updater-api/wp-json/plugin-updater/v1/check-update';
    }
    
    public function plugin_icons(): array {
        return [ '2x' => '', '1x' => '', 'svg' => '', ];
    }
    
    public function plugin_banners(): array {
        return [ '2x' => '', '1x' => '' ];
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
     *     'faq'=>'',
     * 
     *     'changelog'=>'',
     *
     *     'new_version'=>'x.x.x', // * REQUIRED
     *
     *     'last_updated'=>'2023-11-11 3:24pm GMT+6',
     *
     *     'upgrade_notice'=>'',
     *
     *     'package'=>'plugin.zip', // * REQUIRED ABSOLUTE URL
     *
     *     'tested'=>'x.x.x', // WP testes Version
     *
     *     'requires'=>'x.x.x', // Minimum Required WP
     *
     *     'requires_php'=>'x.x.x', // Minimum Required PHP
     *
     *     'requires_plugins'=> [], // Requires Plugins
     *
     *     'versions'=>[ 'trunk' => '' ], // Available versions
     *
     *     'preview_link'=>'', // Plugin Preview Link
     * 
     *     'allow_rollback'=>'yes', // yes | no
     *
     * ]
     */
    
    $data = array(
        'new_version'    => '1.3.4',
        'last_updated'   => '2023-12-12 09:58pm GMT+6',
        'package'        =>'https://updater.example.com/plugin.zip', // After license verified.
        'upgrade_notice' => 'Its Fine',
    );
    
    return rest_ensure_response( $data );
}
```
