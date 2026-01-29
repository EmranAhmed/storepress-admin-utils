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
	use StorePress\AdminUtils\Traits\CallerTrait;
	use StorePress\AdminUtils\Traits\PluginFileTrait;
	use StorePress\AdminUtils\Traits\SingletonTrait;

if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\Updater\Dialog' ) ) {
	/**
	 * Changelog Dialog Class.
	 *
	 * @name Dialog
	 * @phpstan-use CallerTrait<Rollback>
	 * @method Rollback get_caller()
	 */
	class Dialog extends AbstractDialog {

		use SingletonTrait;
		use CallerTrait;

		/**
		 * Instance.
		 *
		 * @param Rollback $caller Caller Class.
		 */
		public function __construct( Rollback $caller ) {
			$this->set_caller( $caller );
			parent::__construct();
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
			$l10 = $this->get_caller()->get_localize_strings();
			return $l10['rollback_changelog_title'];
		}

		/**
		 * Dialog Contents.
		 *
		 * @return string
		 */
		public function content(): string {
			$info = $this->get_caller()->get_plugin_info();
			return $info['sections']['changelog'];
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
