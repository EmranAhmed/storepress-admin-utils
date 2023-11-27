# StorePress Admin Utils

Admin Utility functions for StorePress WordPress Plugin Projects.

## Installation

```php
composer require storepress/admin-utils
```

## Usage

### Plugin Class
```php
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
namespace Plugin_A;

class AdminPage extends \StorePress\AdminUtils\Settings {
    
    public function parent_menu_title(): string {
        return 'StorePress';
    }
    
    public function page_title(): string {
        return 'Plugin A Page Title';
    }
    
    /*
    * public function parent_menu() {
    *    return 'edit.php?post_type=wporg_product';
    * }
    */
    
    /**
     * public function capability(){
     *   return 'edit_posts';
    }*/
    
    public function menu_title(): string {
        return 'Plugin A Menu';
    }
    
    public function page_id(): string {
        return 'plugin-a';
    }
    
    public function settings_id(): string {
        return 'plugin_a_option';
    }
    
    public function plugin_file(): string {
        return __FILE__;
    }
    
    public function get_default_sidebar() {
        echo 'Default sidebar';
    }
}
```

### Plugin `AdminSettings.php`

```php
namespace Plugin_A;

class AdminSettings extends \Plugin_A\AdminPage {
    
    public function add_settings(): array {
        return array(
            'general' => 'General',
            'basic'   => 'Basic',
            'advance' => array( 
                    'name' => 'Advanced', 
                    'icon' => 'dashicons dashicons-analytics', 
                    'sidebar' => false, 
                    'hidden' => false, 
                    'external' => false 
                ),
        );
    }
    
    public function add_general_settings_sidebar() {
        echo 'Hello from general sidebar';
    }
    
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
                'default'     => 'ok'
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
                //'id'          => 'section2',
                'type'        => 'section',
                'title'       => 'Section 02',
                'description' => 'Section of 02',
            ),
        );
    }
    
    public function add_basic_settings_page() {
        echo 'custom page ui';
    }
}
```

### Plugin `Settings.php` file

```php
namespace Plugin_A;

class Settings extends AdminSettings {
		/**
		 * @var object|null
		 */
		protected static $instance = null;
		
		/**
		 * @return self
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			
			return self::$instance;
		}
	}
```
- Now use `Settings::instance();` on `Plugin::init()`

### Plugin Update:
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
namespace Plugin_A;
class Updater extends \StorePress\AdminUtils\Updater {
    
    /**
     * @var object|null $instance
     */
    protected static ?object $instance = null;
    
    /**
     * @return self
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function plugin_file(): string {
        return plugin_a()->get_plugin_file();
    }
    
    public function license_key(): string {
        return plugin_a()->get_option( 'license' );
    }
    
    public function license_key_empty_message(): string {
        return 'add license empty message';
    }
    
    public function check_update_link_text(): string {
        return 'check now...';
    }
    
    public function product_id(): string {
        return 000;
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
}
```

- Now use `Updater::instance();` on `Plugin::init()`