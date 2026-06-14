<?php
	/**
	 * Changelog Dialog Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\Updater;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractDialog;

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Updater\Dialog' ) ) {
	/**
	 * Changelog Dialog Class.
	 *
	 * Renders a modal dialog displaying a plugin's changelog, used by the Rollback service.
	 *
	 * @name Dialog
	 *
	 * @since 1.0.0
	 *
	 * @see Rollback For the rollback service that owns this dialog.
	 * @see AbstractDialog For the base dialog implementation.
	 */
	class Dialog extends AbstractDialog {

		/**
		 * Rollback service instance that owns this dialog.
		 *
		 * @since 1.0.0
		 *
		 * @var Rollback
		 */
		protected Rollback $rollback;

		/**
		 * Construct Dialog instance.
		 *
		 * @since 1.0.0
		 *
		 * @param Rollback $rollback The rollback service instance.
		 */
		public function __construct( Rollback $rollback ) {

			$this->rollback = $rollback;
			parent::__construct();
		}

		/**
		 * Capability.
		 *
		 * @return bool
		 */
		public function has_capability(): bool {
			return $this->rollback->has_capability();
		}

		/**
		 * Get the rollback service instance.
		 *
		 * @since 1.0.0
		 *
		 * @return Rollback
		 */
		public function get_rollback(): Rollback {
			return $this->rollback;
		}

		/**
		 * Get the plugin file path from the rollback service.
		 *
		 * @since 1.0.0
		 *
		 * @return string Plugin file path (e.g. 'my-plugin/my-plugin.php').
		 *
		 * @see Rollback::get_plugin_file()
		 */
		public function plugin_file(): string {
			return $this->get_rollback()->get_plugin_file();
		}

		/**
		 * Dialog ID.
		 *
		 * @return string
		 */
		public function id(): string {
			return 'changelog-dialog';
		}

		/**
		 * Dialog Title.
		 *
		 * @return string
		 * @throws \WP_Exception Throw Exception If used method not overridden in subclass.
		 */
		public function title(): string {
			$l10 = $this->get_rollback()->get_localize_strings();
			return $l10['rollback_changelog_title'];
		}

		/**
		 * Dialog Contents.
		 *
		 * @return string
		 */
		public function content(): string {
			$info     = $this->get_rollback()->get_plugin_info();
			$sections = isset( $info['sections'] ) ? (array) $info['sections'] : array();
			return isset( $sections['changelog'] ) && is_string( $sections['changelog'] ) ? $sections['changelog'] : '';
		}

		/**
		 * Has form.
		 *
		 * @return bool
		 */
		public function use_form(): bool {
			return false;
		}

		/**
		 * Action buttons.
		 *
		 * @return array<int, mixed>
		 */
		public function get_buttons(): array {
			return array();
		}
	}
}
