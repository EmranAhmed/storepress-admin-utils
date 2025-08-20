<?php
	/**
	 * Changelog Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare(strict_types=1);

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Changelog_Dialog' ) ) {
	/**
	 * Changelog Dialog Class.
	 *
	 * @name Dialog
	 */
	class Changelog_Dialog extends Dialog {

		use Singleton;

		/**
		 * DI Rollback
		 *
		 * @var Rollback
		 */
		private Rollback $api;

		/**
		 * Instance.
		 *
		 * @param Rollback $api Parent Class.
		 */
		public function __construct( Rollback $api ) {
			$this->api = $api;
			parent::__construct();
		}

		/**
		 * Get DI Class.
		 *
		 * @return Rollback
		 */
		public function get_api(): Rollback {
			return $this->api;
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
		 */
		public function title(): string {
			$l10 = $this->get_api()->get_localize_strings();
			return $l10['rollback_changelog_title'];
		}

		/**
		 * Dialog Contents.
		 *
		 * @return string
		 */
		public function contents(): string {
			$info = $this->get_api()->get_plugin_info();
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

		/**
		 * Get plugin file absolute or relative path.
		 *
		 * @return string
		 */
		public function plugin_file(): string {
			return $this->get_api()->get_plugin_file();
		}
	}
}
