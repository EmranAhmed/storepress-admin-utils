<?php
	/**
	 * Rollback Template File.
	 *
	 * @package StorePress/AdminUtils
	 * @var \StorePress\AdminUtils\Rollback $this - Settings Class Instance.
	 * @since 1.0.0
	 * @version 1.0.0
	 */

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	check_admin_referer( $this->menu_slug() );

	$storepress_rtl_class = is_rtl() ? 'has-rtl' : '';

	$plugin_info = $this->get_plugin_info();
	$strings     = $this->get_localize_strings();

	unset( $plugin_info['versions']['trunk'] );
?>

<div class="wrap storepress-rollback-wrapper <?php echo esc_attr( $storepress_rtl_class ); ?>">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="#" method="post">
		<div class="plugin-details">
			<div class="plugin-header">
			<div class="plugin-banner">
				<img alt="<?php echo esc_attr( $plugin_info['name'] ); ?>" src="<?php echo esc_url( $plugin_info['banners']['low'] ); ?>" />
			</div>
			<div class="plugin-data">
				<div class="plugin-info">
					<div class="icon-wrapper">
						<img alt="<?php echo esc_attr( $plugin_info['name'] ); ?>" src="<?php echo esc_url( $plugin_info['icons']['svg'] ); ?>" />
					</div>
					<div class="data-wrapper">
						<h2>
							<a target="_blank" href="<?php echo esc_url( $plugin_info['homepage'] ); ?>"><?php echo esc_html( $plugin_info['name'] ); ?></a>
							<span class="dashicon dashicons dashicons-external"></span>
						</h2>
						<div class="current-version"><?php echo esc_html( $strings['rollback_current_version'] ); ?> <span><?php echo esc_html( $plugin_info['version'] ); ?></span></div>
					</div>
				</div>
				<div class="plugin-meta">
					<button id="show-changelog" type="button" class="plugin-changelog button button-secondary"><?php echo esc_html( $strings['rollback_view_changelog'] ); ?></button>
					<div class="last-updated"><?php echo wp_kses_post( sprintf( $strings['rollback_last_updated'], '<span class="dashicon dashicons dashicons-clock"></span>' . human_time_diff( strtotime( $plugin_info['last_updated'] ) ) ) ); ?></div>

					<dialog id="changelog-contents">
						<div class="changelog-wrapper">
							<?php echo wp_kses_post( $plugin_info['sections']['changelog'] ); ?>
						</div>
					</dialog>
				</div>
			</div>
			</div>

			<div class="plugin-body">
				<hr class="wp-header-end" />

				<ul class="version-list">

				<?php
				foreach ( $plugin_info['versions'] as $version => $package ) :
					$is_current_version = version_compare( $plugin_info['version'], $version, '==' );
					?>
				<li><label>
						<input class="available-versions" <?php checked( $is_current_version ); ?> name="version" type="radio" value="<?php echo esc_attr( $version ); ?>"> <?php echo esc_html( $version ); ?>
					<?php if ( $is_current_version ) : ?>
						<span title="Current version" class="dashicons dashicons-yes"></span>
						<span><?php echo esc_html( $strings['rollback_current_version'] ); ?></span>
					<?php endif; ?>
					</label></li>
				<?php endforeach; ?>
			</ul>
			</div>

			<div class="plugin-footer">
			<!--<span class="spinner is-active">button-disabled</span>-->
			<button id="rollback-action" type="button" data-rollback_text="<?php echo esc_attr( $strings['rollback_action_button'] ); ?>"  data-rolling_back_text="<?php echo esc_attr( $strings['rollback_action_running'] ); ?>" class="plugin-rollback button button-primary">
				<span class="spinner"></span>
				<span class="button-text"><?php echo esc_html( $strings['rollback_action_button'] ); ?></span>
			</button>
			<a href="<?php echo esc_url( self_admin_url( 'plugins.php' ) ); ?>" id="rollback-cancel" class="plugin-rollback-cancel button button-secondary"><?php echo esc_html( $strings['rollback_cancel_button'] ); ?></a>
			</div>
		</div>
	</form>

</div>
