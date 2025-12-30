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

if ( ! class_exists( '\StorePress\AdminUtils\Deactivation_Dialog' ) ) {
	/**
	 * Changelog Dialog Class.
	 *
	 * @name Dialog
	 */
	class Deactivation_Dialog extends Dialog {

		/**
		 * DI Rollback
		 *
		 * @var Deactivation_Feedback
		 */
		private Deactivation_Feedback $api;

		/**
		 * Instance.
		 *
		 * @param Deactivation_Feedback $api Parent Class.
		 */
		public function __construct( Deactivation_Feedback $api ) {
			$this->api = $api;
			parent::__construct();
		}

		/**
		 * Get DI Class.
		 *
		 * @return Deactivation_Feedback
		 */
		public function get_api(): Deactivation_Feedback {
			return $this->api;
		}

		/**
		 * Dialog ID.
		 *
		 * @return string
		 */
		public function id(): string {
			return $this->get_api()->get_dialog_id();
		}

		/**
		 * Dialog Title.
		 *
		 * @return string
		 */
		public function title(): string {
			return $this->get_api()->get_title();
		}

		/**
		 * Dialog Title.
		 *
		 * @return string
		 */
		public function get_sub_title(): string {
			return $this->get_api()->sub_title();
		}

		/**
		 * Dialog Contents.
		 *
		 * @return string
		 */
		public function contents(): string {
			$reasons = $this->get_api()->get_reasons();

			$html = array();

			$html[] = '<ul class="storepress-admin-plugin-deactivation-reasons">';
			foreach ( $reasons as $reason_id => $reason ) {
				$html[] = '<li>';
				$html[] = sprintf( '<label><input class="storepress-admin-plugin-deactivation-action" name="action" value="%s" type="radio" /> <span>%s</span></label>', $reason_id, $reason['title'] );

				$condition = array(
					'inert'                             => true,
					'data-storepress-conditional-field' => array(
						'selector' => sprintf( '#%s .storepress-admin-plugin-deactivation-action', $this->id() ),
						'value'    => $reason_id,
					),
				);

				if ( isset( $reason['input'] ) || isset( $reason['message'] ) ) {
					$html[] = sprintf( '<ul %s>', $this->get_html_attributes( $condition ) );
				}

				if ( isset( $reason['input'] ) ) {

					$input = $reason['input'];

					$attrs = $this->get_html_attributes(
						array(
							'value'       => $input['value'] ?? '',
							'placeholder' => $input['placeholder'] ?? '',
							'name'        => $reason_id,
						)
					);

					$html[] = sprintf( '<li class="input-wrapper"><input class="input-field" type="text" %s /></li>', $attrs );
				}

				if ( isset( $reason['message'] ) ) {
					$html[] = sprintf( '<li class="message">%s</li>', $reason['message'] );
				}

				if ( isset( $reason['input'] ) || isset( $reason['message'] ) ) {
					$html[] = '</ul>';
				}

				$html[] = '</li>';
			}
			$html[] = '</ul>';

			return implode( '', $html );
		}

		/**
		 * Get plugin file absolute or relative path.
		 *
		 * @return string
		 */
		public function plugin_file(): string {
			return $this->get_api()->get_plugin_file();
		}

		/**
		 * Dialog buttons.
		 *
		 * @return array<int, mixed>
		 */
		public function get_buttons(): array {
			return $this->get_api()->get_buttons();
		}

		/**
		 * Check has permission.
		 *
		 * @return bool
		 */
		public function has_capability(): bool {
			return $this->get_api()->is_plugins_page();
		}

		/**
		 * Width.
		 *
		 * @return string
		 */
		public function width(): string {
			return $this->get_api()->get_dialog_width();
		}
	}
}
