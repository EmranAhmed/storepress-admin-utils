<?php
	/**
	 * Abstract Dialog Class File.
	 *
	 * @package    StorePress/AdminUtils
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\AdminUtils;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

if ( ! class_exists( '\StorePress\AdminUtils\Dialog' ) ) {
	/**
	 * Abstract Dialog Class.
	 *
	 * @name Dialog
	 */
	abstract class Dialog {

		use Common;
		use Package;

		/**
		 * Dialog Scripts Init.
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Init Hook.
		 *
		 * @return void
		 */
		public function init(): void {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
			add_action( 'admin_footer', array( $this, 'markup' ), 20 );
		}

		/**
		 * Set Dialog Unique ID.
		 *
		 * @return string
		 */
		abstract public function id(): string;

		/**
		 * Get Dialog ID.
		 *
		 * @return string
		 */
		public function get_id(): string {
			return $this->id();
		}

		/**
		 * Set Dialog Title.
		 *
		 * @return string
		 */
		abstract public function title(): string;

		/**
		 * Get Dialog Title.
		 *
		 * @return string
		 */
		public function get_title(): string {
			return $this->title();
		}

		/**
		 * Get Dialog Sub Title.
		 *
		 * @return string
		 */
		public function get_sub_title(): string {
			return '';
		}

		/**
		 * Get Dialog Width.
		 *
		 * @return string
		 */
		public function width(): string {
			return '';
		}

		/**
		 * Has Capability ti Load Template.
		 *
		 * @return bool
		 */
		public function has_capability(): bool {
			return true;
		}

		/**
		 * Dialog Contents.
		 *
		 * @return string
		 */
		abstract public function contents(): string;

		/**
		 * Get Dialog Title.
		 *
		 * @return string
		 */
		public function get_contents(): string {
			return $this->contents();
		}

		/**
		 * Get Dialog Button.
		 *
		 * @return array<int, mixed>
		 * @throws \WP_Exception Method should be overridden in subclass.
		 * @example
		 *             array(
		 *             array(
		 *             'type' => 'link',
		 *             'label'      => __( 'Buy Now' ),
		 *             'attributes' => array(
		 *             'href'  => '#',
		 *             // 'data-action' => 'submit',
		 *             'class' => array( 'button', 'button-primary' ),
		 *             ),
		 *             ),
		 *
		 *     array(
		 *         'type'       => 'link',
		 *         'label'      => __( 'Documentation' ),
		 *         'attributes' => array(
		 *             'href'  => '#',
		 *             // 'data-action' => 'close',
		 *             'class' => array( 'button', 'button-secondary' ),
		 *          ),
		 *       ),
		 * );
		 */
		public function get_buttons(): array {

			/* translators: %s: Method name. */
			$message = sprintf( esc_html__( "Method '%s' not implemented. Must be overridden in subclass." ), __METHOD__ );
			wp_trigger_error( __METHOD__, $message );

			return array(
				array(
					'type'       => 'button', // button | link.
					'label'      => __( 'Save' ),
					'attributes' => array(
						'type'            => 'submit',
						'data-action'     => 'submit',
						'data-label'      => __( 'Save' ),
						'data-processing' => __( 'Saving...' ),
						'class'           => array( 'button', 'button-primary' ),
					),
					'spinner'    => true,
				),
				array(
					'type'       => 'button', // button | link.
					'label'      => __( 'Close' ),
					'attributes' => array(
						'type'        => 'button',
						'data-action' => 'close',
						'class'       => array( 'button', 'button-secondary' ),
					),
				),
			);
		}

		/**
		 * Button Markup.
		 *
		 * @return string
		 */
		public function generate_button_markup(): string {

			$buttons = $this->has_buttons() ? $this->get_buttons() : array();
			$html    = array();
			foreach ( $buttons as $button ) {
				$is_button   = 'button' === $button['type'];
				$label       = $button['label'];
				$has_spinner = $is_button && isset( $button['spinner'] ) && $button['spinner'];
				$attributes  = $button['attributes'];

				$html[] = $is_button ? sprintf( '<button %s>', $this->get_html_attributes( $attributes ) ) : sprintf( '<a %s>', $this->get_html_attributes( $attributes ) );
				$html[] = $has_spinner ? '<span class="spinner"></span>' : '';
				$html[] = sprintf( '<span class="button-text">%s</span>', $label );
				$html[] = $is_button ? '</button>' : '</a>';
			}

			return implode( '', $html );
		}

		/**
		 * Has form.
		 *
		 * @return bool
		 */
		public function use_form(): bool {
			return true;
		}

		/**
		 * Dialog wrapper start.
		 *
		 * @return string
		 */
		public function wrapper_start(): string {

			if ( ! $this->use_form() && ! $this->has_buttons() ) {
				return '';
			}

			$attrs = array(
				'method' => 'dialog',
			);

			return sprintf( '<form %s>', $this->get_html_attributes( $attrs ) );
		}

		/**
		 * Dialog wrapper end.
		 *
		 * @return string
		 */
		public function wrapper_end(): string {
			if ( ! $this->use_form() && ! $this->has_buttons() ) {
				return '';
			}

			return '</form>';
		}

		/**
		 * Has Buttons.
		 *
		 * @return bool
		 */
		public function has_buttons(): bool {
			return ! $this->is_empty_array( $this->get_buttons() );
		}

		/**
		 * Enqueue script.
		 *
		 * @return void
		 */
		public function enqueue_scripts(): void {
			if ( ! $this->has_capability() ) {
				return;
			}
			$this->register_package_admin_utils_script();
			$this->register_package_scripts( 'dialog' );
			$this->enqueue_package_scripts( 'dialog' );
		}

		/**
		 * Load dialog html file.
		 *
		 * @return void
		 */
		public function markup(): void {
			if ( ! $this->has_capability() ) {
				return;
			}
			include __DIR__ . '/../templates/dialog-box.php';
		}
	}
}
