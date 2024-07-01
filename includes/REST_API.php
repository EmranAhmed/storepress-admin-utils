<?php
	/**
	 * Admin Settings Rest API Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\REST_API' ) ) {
	/**
	 * Admin Settings REST API Class.
	 *
	 * @name REST_API
	 * @see \WP_REST_Controller
	 * @example    Default REST URL /wp-json/<plugin-page-id>/v1/settings
	 */
	class REST_API extends \WP_REST_Controller {

		use Common;

		/**
		 * Settings Object.
		 *
		 * @var Settings
		 */
		protected Settings $settings;
		/**
		 * API Display Permission.
		 *
		 * @var string
		 */
		protected string $permission;

		/**
		 * API Namespace.
		 *
		 * @var string
		 */
		protected $namespace;

		/**
		 * Rest base.
		 *
		 * @var string
		 */
		protected $rest_base = 'settings';

		/**
		 * Construct.
		 *
		 * @param Settings $settings Setting Class Instance.
		 */
		public function __construct( Settings $settings ) {
			$this->settings   = $settings;
			$this->permission = $this->get_settings()->get_capability();
			$this->namespace  = $this->get_settings()->show_in_rest();
		}

		/**
		 * Get Settings Object.
		 *
		 * @return Settings
		 */
		public function get_settings(): Settings {
			return $this->settings;
		}

		/**
		 * Registers the routes for the StorePress's settings.
		 *
		 * @return void
		 * @see register_rest_route()
		 */
		public function register_routes() {

			if ( $this->is_empty_string( $this->namespace ) ) {
				return;
			}

			// @see: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => \WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'args'                => array(),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
					),

					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Checks if a given request has access to read and manage settings.
		 *
		 * @phpstan-param \WP_REST_Request $request
		 * @param \WP_REST_Request $request Full details about the request.
		 *
		 * @return bool TRUE if the request has read access for the item, otherwise FALSE.
		 * @phpstan-ignore missingType.generics, method.childReturnType
		 */
		public function get_item_permissions_check( $request ): bool {
			return current_user_can( $this->permission );
		}

		/**
		 * Retrieves the settings.
		 *
		 * @param \WP_REST_Request $request Full details about the request.
		 *
		 * @return \WP_REST_Response|\WP_Error Array on success, or WP_Error object on failure.
		 * @see \WP_REST_Settings_Controller::get_item()
		 * @phpstan-ignore missingType.generics
		 */
		public function get_item( $request ) {
			$options  = $this->get_registered_options();
			$page_id  = $this->get_settings()->get_page_id();
			$response = array();

			foreach ( $options as $name => $args ) {
				/**
				 * Filters the value of a setting recognized by the REST API.
				 *
				 * Allow hijacking the setting value and overriding the built-in behavior by returning a
				 * non-null value.  The returned value will be presented as the setting value instead.
				 *
				 * @param mixed  $result Value to use for the requested setting. Can be a scalar
				 *                       matching the registered schema for the setting, or null to
				 *                       follow the default get_option() behavior.
				 * @param string $name   Setting name (as shown in REST API responses).
				 * @param array  $args   Custom field array with value.
				 * @since 1.0.0
				 */
				$response[ $name ] = apply_filters( "storepress_rest_pre_get_{$page_id}_setting", null, $name, $args );

				if ( is_null( $response[ $name ] ) ) {
					// Set value.
					$response[ $name ] = $args['value'];
				}

				/*
				 * Because get_option() is lossy, we have to
				 * cast values to the type they are registered with.
				 */
				$response[ $name ] = $this->prepare_value( $response[ $name ], $args['schema'] );
			}

			return new \WP_REST_Response( $response, 200 );
		}

		/**
		 * Prepares a value for output based off a schema array.
		 *
		 * @param mixed                          $value  Value to prepare.
		 * @param array<string, string|string[]> $schema Schema to match.
		 *
		 * @return mixed The prepared value.
		 */
		protected function prepare_value( $value, array $schema ) {
			/*
			 * If the value is not valid by the schema, set the value to null.
			 * Null values are specifically non-destructive, so this will not cause
			 * overwriting the current invalid value to null.
			 */
			if ( is_wp_error( rest_validate_value_from_schema( $value, $schema ) ) ) {
				return null;
			}

			return rest_sanitize_value_from_schema( $value, $schema );
		}

		/**
		 * Retrieves all the registered options for the Settings API.
		 *
		 * @return array<string, mixed> Array of registered options.
		 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
		 */
		protected function get_registered_options(): array {
			$rest_options = array();

			foreach ( $this->get_settings()->get_all_fields() as $name => $field ) {

				if ( ! $field->has_show_in_rest() ) {
					continue;
				}

				$rest_args = array();

				if ( is_array( $field->get_attribute( 'show_in_rest' ) ) ) {
					$rest_args = $field->get_attribute( 'show_in_rest' );
				}

				$defaults = array(
					'name'   => $rest_args['name'] ?? $field->get_id(),
					'schema' => array(),
				);

				$rest_args = array_merge( $defaults, $rest_args );

				$default_schema = array(
					'type'        => $field->get_rest_type(),
					'description' => $field->get_title(),
					/** 'readonly'    => true,
					// 'context'     => array( 'view' ),
					// 'default'     => $field->get_default_value(),
					*/
				);

				if ( $field->has_attribute( 'required' ) ) {
					$default_schema['required'] = true;
				}

				if ( 'color' === $field->get_type() ) {
					$default_schema['format'] = 'hex-color';
				}

				if ( 'url' === $field->get_type() ) {
					$default_schema['format'] = 'uri';
				}

				if ( 'email' === $field->get_type() ) {
					$default_schema['format'] = 'email';
				}

				if ( $field->is_type_group() ) {
					$group_fields          = $field->get_group_fields();
					$default_properties    = array();
					$group_rest_properties = array();

					foreach ( $group_fields as $group_field ) {
						// @TODO: Check is multiple, has options, hex color, number

						$id = $group_field->get_id();

						if ( ! $group_field->has_show_in_rest() ) {
							continue;
						}

						if ( is_array( $group_field->get_attribute( 'show_in_rest' ) ) ) {
							$group_rest_properties[ $id ] = $group_field->get_attribute( 'show_in_rest' );
						}

						$default_properties[ $id ] = array();

						$default_properties[ $id ]['type']        = $group_field->get_rest_type();
						$default_properties[ $id ]['description'] = $group_field->get_title();
						$default_properties[ $id ]['readonly']    = true;

						if ( $group_field->has_attribute( 'required' ) ) {
							$default_properties[ $id ]['required'] = true;
						}

						if ( 'color' === $group_field->get_type() ) {
							// @phpstan-ignore offsetAssign.dimType
							$default_properties[ $id ]['type']['format'] = 'hex-color';
						}

						if ( 'url' === $group_field->get_type() ) {
							$default_properties[ $id ]['type']['format'] = 'uri';
						}

						if ( 'email' === $group_field->get_type() ) {
							$default_properties[ $id ]['type']['format'] = 'email';
						}
					}

					$properties = array_merge( $default_properties, $group_rest_properties );

					if ( count( $properties ) > 0 ) {
						$default_schema['properties'] = $properties;
					}
				}

				$rest_args['schema']      = array_merge( $default_schema, $rest_args['schema'] );
				$rest_args['option_name'] = $field->get_id();
				if ( $field->is_type_group() ) {
					$rest_args['value'] = $field->get_rest_group_values();
				} else {
					$rest_args['value'] = $field->get_rest_value();
				}

				// Skip over settings that don't have a defined type in the schema.
				if ( $this->is_empty_string( $rest_args['schema']['type'] ) ) {
					continue;
				}

				/*
				 * Allow the supported types for settings, as we don't want invalid types
				 * to be updated with arbitrary values that we can't do decent sanitizing for.
				 */
				if ( ! in_array( $rest_args['schema']['type'], array( 'number', 'integer', 'string', 'boolean', 'array', 'object' ), true ) ) {
					continue;
				}

				$rest_args['schema'] = rest_default_additional_properties_to_false( $rest_args['schema'] );

				$rest_options[ $rest_args['name'] ] = $rest_args;
			}

			return $rest_options;
		}

		/**
		 * Retrieves the site setting schema, conforming to JSON Schema.
		 *
		 * @return array<string, mixed> Item schema data.
		 */
		public function get_item_schema(): array {
			if ( $this->is_empty_array( $this->schema ) ) {
				return $this->add_additional_fields_schema( $this->schema );
			}

			$options = $this->get_registered_options();

			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'settings',
				'type'       => 'object',
				'properties' => array(),
			);

			foreach ( $options as $option_name => $option ) {
				$schema['properties'][ $option_name ]                = $option['schema'];
				$schema['properties'][ $option_name ]['arg_options'] = array(
					'sanitize_callback' => array( $this, 'sanitize_callback' ),
				);
			}

			$this->schema = $schema;

			return $this->add_additional_fields_schema( $this->schema );
		}

		/**
		 * Custom sanitize callback used for all options to allow the use of 'null'.
		 *
		 * By default, the schema of settings will throw an error if a value is set to
		 * `null` as it's not a valid value for something like "type => string". We
		 * provide a wrapper sanitizer to allow the use of `null`.
		 *
		 * @param mixed            $value   The value for the setting.
		 * @param \WP_REST_Request $request The request object.
		 * @param string           $param   The parameter name.
		 *
		 * @return mixed|\WP_Error
		 * @phpstan-ignore missingType.generics
		 */
		public function sanitize_callback( $value, \WP_REST_Request $request, string $param ) {
			if ( is_null( $value ) ) {
				return $value;
			}

			return rest_parse_request_arg( $value, $request, $param );
		}
	}
}
