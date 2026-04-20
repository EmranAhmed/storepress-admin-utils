<?php
/**
 * Settings Factory Class File.
 *
 * @package    StorePress/AdminUtils
 * @since      1.0.0
 * @version    1.0.0
 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Factory;

	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	use StorePress\AdminUtils\Services\Internal\Settings\API;
	use StorePress\AdminUtils\Services\Internal\Settings\Fields;
	use StorePress\AdminUtils\Traits\SingletonTrait;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Factory\SettingsFactory' ) ) {
	/**
	 * Settings Factory Class.
	 *
	 * Creates settings-related service instances (Fields, REST API) for a given settings owner.
	 *
	 * @name SettingsFactory
	 *
	 * @since 1.0.0
	 */
	class SettingsFactory {

		use SingletonTrait;

		/**
		 * Create a settings fields collection instance.
		 *
		 * @param AbstractSettings                 $settings Settings owner instance.
		 * @param array<int, array<string, mixed>> $fields   Field configuration arrays.
		 *
		 * @return Fields
		 *
		 * @since 1.0.0
		 */
		public function create_fields( AbstractSettings $settings, array $fields ): Fields {
			return new Fields( $settings, $fields );
		}

		/**
		 * Create a settings REST API instance.
		 *
		 * @param AbstractSettings $settings Settings owner instance.
		 *
		 * @return API
		 *
		 * @since 1.0.0
		 */
		public function create_rest_api( AbstractSettings $settings ): API {
			return new API( $settings );
		}
	}
}
