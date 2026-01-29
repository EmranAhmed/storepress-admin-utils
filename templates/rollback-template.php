<?php
	/**
	 * Rollback Template File.
	 *
	 * @package StorePress/AdminUtils
	 * @var Rollback $this - Rollback Instance.
	 * @since 1.0.0
	 * @version 1.0.0
	 */

	use StorePress\AdminUtils\Services\Internal\Updater\Rollback;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	$storepress_rtl_class = is_rtl() ? 'has-rtl' : '';

	$storepress_plugin_info = $this->get_plugin_info();
	$storepress_strings     = $this->get_localize_strings();

	unset( $storepress_plugin_info['versions']['trunk'] );
?>

<div class="wrap storepress-rollback-wrapper <?php echo esc_attr( $storepress_rtl_class ); ?>">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="#" method="post">
		<div class="plugin-details">
			<div class="plugin-header">
			<div class="plugin-banner">
				<img alt="<?php echo esc_attr( $storepress_plugin_info['name'] ); ?>" src="<?php echo esc_url( $this->get_image_url( $storepress_plugin_info['banners'] ) ); ?>" />
			</div>
			<div class="plugin-data">
				<div class="plugin-info">
					<div class="icon-wrapper">
						<img alt="<?php echo esc_attr( $storepress_plugin_info['name'] ); ?>" src="<?php echo esc_url( $this->get_image_url( $storepress_plugin_info['icons'] ) ); ?>" />
					</div>
					<div class="data-wrapper">
						<h2>
							<a target="_blank" href="<?php echo esc_url( $storepress_plugin_info['homepage'] ); ?>"><?php echo esc_html( $storepress_plugin_info['name'] ); ?></a>
							<span class="dashicon dashicons dashicons-external"></span>
						</h2>
						<div class="current-version"><?php echo esc_html( $storepress_strings['rollback_current_version'] ); ?> <span id="rollback-current-version"><?php echo esc_html( $storepress_plugin_info['version'] ); ?></span></div>
					</div>
				</div>
				<div class="plugin-meta">
					<button id="show-changelog" data-request-storepress-dialog="#changelog-dialog" type="button" class="plugin-changelog button button-secondary"><?php echo esc_html( $storepress_strings['rollback_view_changelog'] ); ?></button>
					<div class="last-updated"><?php echo wp_kses_post( sprintf( $storepress_strings['rollback_last_updated'], '<span class="dashicon dashicons dashicons-clock"></span>' . human_time_diff( strtotime( $storepress_plugin_info['last_updated'] ) ) ) ); ?></div>
				</div>
			</div>
			</div>

			<div class="plugin-body">
				<hr class="wp-header-end" />

				<ul class="version-list">

				<?php
				foreach ( $storepress_plugin_info['versions'] as $storepress_version => $storepress_package ) :

					$storepress_is_current_version = version_compare( $storepress_plugin_info['version'], $storepress_version, '==' );
					$storepress_version_id         = str_replace( '.', '-', $storepress_version );
					?>
				<li><label id="version-id-<?php echo esc_attr( $storepress_version_id ); ?>">
						<input class="available-versions" <?php checked( $storepress_is_current_version ); ?> name="version" type="radio" value="<?php echo esc_attr( $storepress_version ); ?>"> <?php echo esc_html( $storepress_version ); ?>
					<?php if ( $storepress_is_current_version ) : ?>
						<div id="version-list-current-mark">
						<span title="<?php echo esc_attr( $storepress_strings['rollback_current_version'] ); ?>" class="dashicons dashicons-yes"></span>
						<span><?php echo esc_html( $storepress_strings['rollback_current_version'] ); ?></span>
						</div>
					<?php endif; ?>
					</label></li>
				<?php endforeach; ?>
			</ul>
			</div>

			<div class="plugin-footer">
			<!--<span class="spinner is-active">button-disabled</span>-->
			<button id="rollback-action" type="button" data-rollback_text="<?php echo esc_attr( $storepress_strings['rollback_action_button'] ); ?>"  data-rolling_back_text="<?php echo esc_attr( $storepress_strings['rollback_action_running'] ); ?>" class="plugin-rollback button button-primary">
				<span class="spinner"></span>
				<span class="button-text"><?php echo esc_html( $storepress_strings['rollback_action_button'] ); ?></span>
			</button>
			<a href="<?php echo esc_url( self_admin_url( 'plugins.php' ) ); ?>" id="rollback-cancel" class="plugin-rollback-cancel button button-secondary"><?php echo esc_html( $storepress_strings['rollback_cancel_button'] ); ?></a>
			</div>
		</div>
	</form>

</div>
