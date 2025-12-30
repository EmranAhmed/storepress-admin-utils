<?php
	/**
	 * DialogBox Template File.
	 *
	 * @package StorePress/AdminUtils
	 * @var \StorePress\AdminUtils\Dialog $this - Settings Class Instance.
	 * @since 1.0.0
	 * @version 1.0.0
	 */

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	$storepress_style = $this->get_inline_styles(
		array(
			'--storepress-admin-utils-dialog-box-width' => $this->width(),
		)
	);
	?>

<dialog style="<?php echo esc_attr( $storepress_style ); ?>" class="storepress-admin-utils-dialog-box" id="<?php echo esc_attr( $this->get_id() ); ?>">
	<?php echo wp_kses( $this->wrapper_start(), $this->get_kses_allowed_dialog_html() ); ?>
		<div class="storepress-admin-utils-dialog-box_wrapper">
			<div class="storepress-admin-utils-dialog-box_header">
				<h1><?php echo esc_html( $this->get_title() ); ?></h1>
				<button class="close" data-action="close">&times;
					<span class="screen-reader-text"><?php esc_html_e( 'Close' ); ?></span>
				</button>
			</div>
			<div class="storepress-admin-utils-dialog-box_contents">
				<?php if ( ! $this->is_empty_string( $this->get_sub_title() ) ) { ?>
					<h2><?php echo esc_html( $this->get_sub_title() ); ?></h2>
				<?php } ?>

				<?php echo wp_kses( $this->get_contents(), $this->get_kses_allowed_dialog_html() ); ?>
			</div>
			<?php if ( $this->has_buttons() ) { ?>
			<div class="storepress-admin-utils-dialog-box_footer"> <?php echo wp_kses( $this->generate_button_markup(), $this->get_kses_allowed_dialog_html() ); ?> </div>
			<?php } ?>
		</div>
	<?php echo wp_kses( $this->wrapper_end(), $this->get_kses_allowed_dialog_html() ); ?>
</dialog>
