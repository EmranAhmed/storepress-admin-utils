<?php
	/**
	 * Deactivation Feedback Dialog Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils\Services\Internal\DeactivationFeedback;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractDeactivationFeedback;
	use StorePress\AdminUtils\Abstracts\AbstractDialog;
	use StorePress\AdminUtils\Interfaces\DeactivationFeedbackInterface;
	use StorePress\AdminUtils\Traits\SingletonTrait;


if ( ! class_exists( '\StorePress\AdminUtils\Services\Internal\DeactivationFeedback\Dialog' ) ) {

	/**
	 * Deactivation Feedback Dialog Class.
	 *
	 * Renders the plugin deactivation feedback dialog with reasons, inputs, and action buttons.
	 *
	 * @name Dialog
	 *
	 * @phpstan-use SingletonTrait<Dialog>
	 *
	 * @since 1.0.0
	 */
	class Dialog extends AbstractDialog {

		use SingletonTrait;

		/**
		 * Deactivation feedback owner instance.
		 *
		 * @var AbstractDeactivationFeedback
		 *
		 * @since 1.0.0
		 */
		protected AbstractDeactivationFeedback $feedback;

		/**
		 * Constructor.
		 *
		 * @param AbstractDeactivationFeedback $feedback The deactivation feedback owner instance.
		 *
		 * @since 1.0.0
		 */
		public function __construct( AbstractDeactivationFeedback $feedback ) {
			$this->feedback = $feedback;
			parent::__construct();
		}

		/**
		 * Get the deactivation feedback owner instance.
		 *
		 * @return AbstractDeactivationFeedback
		 *
		 * @since 1.0.0
		 */
		public function get_feedback(): AbstractDeactivationFeedback {
			return $this->feedback;
		}

		/**
		 * Dialog ID.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function id(): string {
			return $this->get_feedback()->get_dialog_id();
		}

		/**
		 * Dialog title.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function title(): string {
			return $this->get_feedback()->get_title();
		}

		/**
		 * Dialog subtitle.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function get_sub_title(): string {
			return $this->get_feedback()->sub_title();
		}

		/**
		 * Dialog contents HTML built from deactivation reasons.
		 *
		 * @return string
		 *
		 * @throws \WP_Exception If get_reasons method not overriden in sub-class.
		 * @since 1.0.0
		 */
		public function content(): string {
			$reasons = $this->get_feedback()->get_reasons();

			$html = array();

			$html[] = '<ul class="storepress-admin-plugin-deactivation-reasons">';
			foreach ( $reasons as $reason_id => $reason ) {
				$html[] = '<li>';
				$html[] = sprintf( '<label><input class="storepress-admin-plugin-deactivation-action" name="action" value="%s" type="radio" /> <span>%s</span></label>', sanitize_key( $reason_id ), wp_kses_post( $reason['title'] ) );

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
					$html[] = sprintf( '<li class="message">%s</li>', wp_kses_post( $reason['message'] ) );
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
		 * Action buttons.
		 *
		 * @return array<int, mixed>
		 *
		 * @throws \WP_Exception If get_buttons method not overriden in sub-class.
		 * @since 1.0.0
		 */
		public function get_buttons(): array {
			return $this->get_feedback()->get_buttons();
		}

		/**
		 * Check has permission.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public function has_capability(): bool {
			return $this->get_feedback()->is_plugins_page();
		}

		/**
		 * Dialog width.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function width(): string {
			return $this->get_feedback()->get_dialog_width();
		}

		/**
		 * Get plugin file absolute or relative path.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function plugin_file(): string {
			return $this->get_feedback()->get_plugin_file();
		}
	}
}
