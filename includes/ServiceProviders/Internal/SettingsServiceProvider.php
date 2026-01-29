<?php
	/**
	 * Settings Service Provider File.
	 *
	 * This file contains the SettingsServiceProvider class which handles
	 * the registration and bootstrapping of settings-related services
	 * for the admin settings framework.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      2.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\ServiceProviders\Internal;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractServiceProvider;
	use StorePress\AdminUtils\Abstracts\AbstractSettings;
	use StorePress\AdminUtils\Interfaces\ContainerInterface;
	use StorePress\AdminUtils\Services\Internal\Settings\API;
	use StorePress\AdminUtils\Services\Internal\Settings\Field;
	use StorePress\AdminUtils\Services\Internal\Settings\Fields;
	use StorePress\AdminUtils\Services\Internal\Settings\Section;
	use StorePress\AdminUtils\Traits\Internal\InternalServiceProviderCommonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\ServiceProviders\Internal\SettingsServiceProvider' ) ) {

	/**
	 * Settings Service Provider Class.
	 *
	 * Registers and bootstraps settings-related services including Field, Fields,
	 * and Section services for rendering admin settings pages. This provider is
	 * used internally by AbstractSettings implementations to manage the dependency
	 * injection container for settings-related components.
	 *
	 * The provider registers factory callbacks for each service, allowing lazy
	 * instantiation with the correct caller context (AbstractSettings instance).
	 *
	 * @name SettingsServiceProvider
	 *
	 * @since 2.0.0
	 *
	 * @method AbstractSettings get_caller() Returns the AbstractSettings instance that owns this provider.
	 *
	 * @see AbstractServiceProvider For the base service provider implementation.
	 * @see AbstractSettings For the settings class that uses this provider.
	 * @see Field For the individual field rendering service.
	 * @see Fields For the field collection rendering service.
	 * @see Section For the section rendering service.
	 * @see InternalServiceProviderCommonTrait For shared internal provider functionality.
	 *
	 * @example Usage within AbstractSettings:
	 *          ```php
	 *          $provider = new SettingsServiceProvider( $container, $settings );
	 *          $provider->register();
	 *          $provider->boot();
	 *          ```
	 *
	 * @example Resolving the Field service:
	 *          ```php
	 *          $field = $container->get( Field::class, array(
	 *              'id'    => 'my_field',
	 *              'type'  => 'text',
	 *              'title' => 'My Field',
	 *          ), $values );
	 *          ```
	 *
	 * @example Resolving the Section service:
	 *          ```php
	 *          $section = $container->get( Section::class, array(
	 *              'id'    => 'general',
	 *              'title' => 'General Settings',
	 *          ) );
	 *          ```
	 *
	 * @example Resolving the Fields service:
	 *          ```php
	 *          $fields = $container->get( Fields::class, $field_definitions );
	 *          ```
	 */
	class SettingsServiceProvider extends AbstractServiceProvider {

		use InternalServiceProviderCommonTrait;

		// =====================================================================
		// Service Registration Methods
		// =====================================================================

		/**
		 * Register services with the container.
		 *
		 * Registers factory callbacks for Field, Section, and Fields services.
		 * Each factory creates instances with the current settings caller context,
		 * enabling proper access to settings configuration and values.
		 *
		 * @since 2.0.0
		 *
		 * @return void
		 *
		 * @see Field For the individual field service being registered.
		 * @see Section For the section service being registered.
		 * @see Fields For the field collection service being registered.
		 * @see SettingsServiceProvider::boot() Called after registration to bootstrap services.
		 *
		 * @example The Field service can be resolved as:
		 *          ```php
		 *          $field = $container->get( Field::class, array(
		 *              'id'    => 'my_field',
		 *              'type'  => 'text',
		 *              'title' => 'My Field',
		 *          ), $values );
		 *          ```
		 *
		 * @example The Section service can be resolved as:
		 *          ```php
		 *          $section = $container->get( Section::class, array(
		 *              'id'          => 'general',
		 *              'title'       => 'General Settings',
		 *              'description' => 'Configure general options.',
		 *          ) );
		 *          ```
		 *
		 * @example The Fields service can be resolved as:
		 *          ```php
		 *          $fields = $container->get( Fields::class, array(
		 *              array( 'id' => 'field_1', 'type' => 'text' ),
		 *              array( 'id' => 'field_2', 'type' => 'checkbox' ),
		 *          ) );
		 *          ```
		 */
		public function register(): void {

			// Register Field service factory for individual field rendering.
			$this->get_container()->register(
				Field::class,
				/**
				 * Factory callback to create Field instances.
				 *
				 * Creates a new Field instance with the settings caller context,
				 * field configuration, and optional pre-populated values.
				 *
				 * @since 2.0.0
				 *
				 * @param ContainerInterface $container The dependency injection container.
				 * @param  mixed     ...$args     Field configuration array containing
				 *                                            id, type, title, and other field options.
				 *
				 * @return Field The created Field instance.
				 */

				function ( ContainerInterface $container, ...$args ): Field {

					return new Field( $this->get_caller(), ...$args );
				}
			);

			// Register Section service factory for section rendering.
			$this->get_container()->register(
				Section::class,
				/**
				 * Factory callback to create Section instances.
				 *
				 * Creates a new Section instance with the settings caller context
				 * and section configuration for grouping related fields.
				 *
				 * @param ContainerInterface $container       The dependency injection container.
				 * @param mixed     ...$args   Section configuration array containing
				 *                                            id, title, description, and other options.
				 *
				 * @return Section The created Section instance.
				 * @since 2.0.0
				 */

				function ( ContainerInterface $container, ...$args ): Section {

					return new Section( $this->get_caller(), ...$args );
				}
			);

			// Register Fields service factory for field collection rendering.
			$this->get_container()->register(
				Fields::class,
				/**
				 * Factory callback to create Fields collection instances.
				 *
				 * Creates a new Fields instance with the settings caller context
				 * and an array of field definitions for batch rendering.
				 *
				 * @param ContainerInterface               $container The dependency injection container.
				 * @param mixed ...$args    Array of field configuration arrays.
				 *
				 * @return Fields The created Fields collection instance.
				 * @since 2.0.0
				 */
				function ( ContainerInterface $container, ...$args ): Fields {

					return new Fields( $this->get_caller(), ...$args );
				}
			);

			$this->get_container()->register(
				API::class,
				/**
				 * Factory callback to create Rest Api collection instances.
				 *
				 * Creates a new Fields instance with the settings caller context
				 * and an array of field definitions for batch rendering.
				 *
				 * @return API The created Fields collection instance.
				 * @since 2.0.0
				 */
				function (): API {
					return new API( $this->get_caller() );
				}
			);
		}

		// =====================================================================
		// Service Bootstrap Methods
		// =====================================================================

		/**
		 * Bootstrap services after all providers are registered.
		 *
		 * Called after register() to perform any initialization that requires
		 * all services to be registered. Currently unused but available for
		 * future extensions.
		 *
		 * @since 2.0.0
		 *
		 * @return void
		 *
		 * @see SettingsServiceProvider::register() Called before boot to register services.
		 */
		public function boot(): void {
			// Reserved for future service bootstrapping.
		}
	}
}
