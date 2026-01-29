<?php
	/**
	 * Admin Settings Rest API Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\Settings;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\HelperMethodsTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Settings\API' ) ) {

	/**
	 * Admin Settings REST API Class.
	 *
	 * Provides REST API endpoints for reading settings from the AbstractSettings framework.
	 * Extends WP_REST_Controller to integrate with WordPress REST API infrastructure.
	 *
	 * @name API
	 *
	 * @phpstan-use CallerTrait<AbstractSettings>
	 *
	 * @method AbstractSettings get_caller() Returns the parent AbstractSettings instance.
	 *
	 * @see \WP_REST_Controller For base REST controller methods.
	 * @see AbstractSettings For settings page integration.
	 *
	 * @example Default REST URL format:
	 *          GET: /wp-json/<plugin-page-id>/v1/settings
	 *          GET: /wp-json/my-plugin/v1/settings
	 *
	 * @example Response format:
	 *          ```json
	 *          {
	 *              "field_id": "field_value",
	 *              "another_field": "another_value",
	 *              "group_field": {
	 *                  "sub_field": "sub_value"
	 *              }
	 *          }
	 *          ```
	 *
	 * @since 1.0.0
	 */
	class API extends \WP_REST_Controller {

		use HelperMethodsTrait;
		use CallerTrait;

		/**
		 * Required capability for API access.
		 *
		 * The WordPress capability required to read settings via REST API.
		 *
		 * @var string
		 */
		protected string $permission;

		/**
		 * REST API namespace.
		 *
		 * The namespace prefix for the REST routes (e.g., 'my-plugin/v1').
		 *
		 * @var string
		 */
		protected $namespace;

		/**
		 * REST API base path.
		 *
		 * The base path for the settings endpoint (e.g., 'settings').
		 *
		 * @var string
		 */
		protected $rest_base = 'settings';

		/**
		 * Constructor.
		 *
		 * Initializes the REST API controller with the parent settings instance.
		 *
		 * @param AbstractSettings $settings The settings class instance.
		 *
		 * @see set_caller()
		 *
		 * @since 1.0.0
		 */
		public function __construct( AbstractSettings $settings ) {
			$this->set_caller( $settings );

			$this->permission = $this->get_settings()->rest_get_capability();
			$this->namespace  = $this->get_settings()->show_in_rest();
			$this->rest_base  = $this->get_settings()->rest_api_base();
		}

		/**
		 * Get the parent settings instance.
		 *
		 * Returns the AbstractSettings instance that owns this API controller.
		 *
		 * @return AbstractSettings The settings instance.
		 *
		 * @see get_caller()
		 *
		 * @since 1.0.0
		 */
		public function get_settings(): AbstractSettings {
			return $this->get_caller();
		}

		/**
		 * Register REST API routes for settings.
		 *
		 * Registers the GET endpoint for reading settings. Only registers if
		 * the namespace is configured (i.e., show_in_rest() returns a value).
		 *
		 * @return void
		 *
		 * @see register_rest_route()
		 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
		 *
		 * @since 1.0.0
		 */
		public function register_routes(): void {

			if ( $this->is_empty_string( $this->namespace ) ) {
				return;
			}

			// Register REST route for reading settings.
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
		 * Check if request has permission to read settings.
		 *
		 * Verifies the current user has the required capability to access
		 * the settings endpoint.
		 *
		 * @param \WP_REST_Request $request Full details about the request.
		 *
		 * @return bool True if the request has read access, false otherwise.
		 *
		 * @phpstan-param \WP_REST_Request $request
		 *
		 * @since 1.0.0
		 */
		public function get_item_permissions_check( $request ): bool {
			return $this->is_empty_string( $this->permission ) || current_user_can( $this->permission );
		}

		/**
		 * Retrieve all settings via REST API.
		 *
		 * Returns all registered settings that have show_in_rest enabled.
		 * Values are validated and sanitized according to their schema.
		 *
		 * @param \WP_REST_Request $request Full details about the request.
		 *
		 * @return \WP_REST_Response|\WP_Error Response with settings data or error.
		 *
		 * @see \WP_REST_Settings_Controller::get_item()
		 * @see get_registered_options()
		 * @see prepare_value()
		 *
		 * @since 1.0.0
		 */
		public function get_item( $request ) {
			$options  = $this->get_registered_options();
			$page_id  = $this->get_settings()->get_page_slug();
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
		 * Prepare a value for REST API output based on schema.
		 *
		 * Validates and sanitizes the value according to the provided JSON schema.
		 * Returns null for invalid values to prevent destructive overwrites.
		 *
		 * @param mixed                          $value  The value to prepare.
		 * @param array<string, string|string[]> $schema The JSON schema to validate against.
		 *
		 * @return mixed The prepared value or null if invalid.
		 *
		 * @see rest_validate_value_from_schema()
		 * @see rest_sanitize_value_from_schema()
		 *
		 * @since 1.0.0
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
		 * Retrieve all registered settings fields for REST API.
		 *
		 * Builds an array of all settings fields that have show_in_rest enabled,
		 * including their values, schemas, and metadata.
		 *
		 * @return array<string, mixed> Array of registered options with schema and values.
		 *
		 * @see get_all_fields()
		 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
		 *
		 * @since 1.0.0
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

				if ( is_string( $field->get_attribute( 'show_in_rest' ) ) ) {
					$rest_args['name'] = $field->get_attribute( 'show_in_rest' );
				}

				$defaults = array(
					'name'   => $rest_args['name'] ?? $field->get_id(),
					'schema' => array(),
				);

				$rest_args = $this->array_merge( $defaults, $rest_args );

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
							$default_properties[ $id ]['format'] = 'hex-color';
						}

						if ( 'url' === $group_field->get_type() ) {
							$default_properties[ $id ]['format'] = 'uri';
						}

						if ( 'email' === $group_field->get_type() ) {
							$default_properties[ $id ]['format'] = 'email';
						}
					}

					$properties = array_merge( $default_properties, $group_rest_properties );

					if ( count( $properties ) > 0 ) {
						$default_schema['properties'] = $properties;
					}
				}

				$rest_args['schema']      = $this->array_merge( $default_schema, $rest_args['schema'] );
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
				 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types
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
		 * Retrieve the JSON Schema for the settings endpoint.
		 *
		 * Builds and caches a JSON Schema definition for all registered
		 * settings, conforming to JSON Schema draft-04.
		 *
		 * @return array<string, mixed> The JSON Schema array.
		 *
		 * @see get_registered_options()
		 * @see add_additional_fields_schema()
		 * @see https://json-schema.org/draft-04/schema
		 *
		 * @since 1.0.0
		 */
		public function get_item_schema(): array {
			if ( is_array( $this->schema ) && $this->is_empty_array( $this->schema ) ) {
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
		 * Custom sanitize callback to allow null values.
		 *
		 * By default, JSON Schema validation throws an error if a value is set to
		 * `null` as it's not valid for types like "string". This wrapper allows
		 * null values to pass through without validation errors.
		 *
		 * @param mixed            $value   The value for the setting.
		 * @param \WP_REST_Request $request The request object.
		 * @param string           $param   The parameter name.
		 *
		 * @return mixed|\WP_Error The sanitized value or WP_Error on failure.
		 *
		 * @see rest_parse_request_arg()
		 *
		 * @since 1.0.0
		 */
		public function sanitize_callback( $value, \WP_REST_Request $request, string $param ) {
			if ( is_null( $value ) ) {
				return $value;
			}

			return rest_parse_request_arg( $value, $request, $param );
		}
	}
}
