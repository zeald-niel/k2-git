<?php
/**
 * Dashboard template: Dashboard overview
 *
 * This template is used for the main overview, when the user clicks on the
 * main-menu item or the Dashboard sub-menu.
 *
 * Following variables are passed into the template:
 *   $data (membership data)
 *   $member (user profile data)
 *   $urls (urls of all dashboard menu items)
 *   $type [full|single|free]
 *   $my_project (only needed for type == single)
 *   $projects (keys: free|paid; list of projects, only for type free/single)
 *
 * @since  4.0.0
 * @package WPMUDEV_Dashboard
 */

$profile = $member['profile'];
$points = $member['points'];
$history = $points['history'];
$level = $points['rep_level'];


if ( ! is_array( $history ) ) { $history = array(); }

// URL for the edit my profile functin.
$url_profile = $urls->remote_site . 'hub/profile/';

// Upgrade membership URL.
$url_upgrade = $urls->remote_site . 'hub/account/';

// Details on "Earn points".
$url_points = $urls->remote_site . 'earn-your-wpmudev-membership/';

// URLs for the Quick-link section.
$quick_1 = $urls->plugins_url;
$quick_2 = $urls->remote_site . 'manuals/';
$quick_3 = $urls->remote_site . 'forums/#question';
$quick_4 = $urls->remote_site . 'forums/';

// Find the 5 most popular plugins, that are not installed yet.
$popular = array();
$count = 0;
foreach ( $data['projects'] as $item ) {
	// Skip themes.
	if ( 'plugin' != $item['type'] ) { continue; }

	$plugin = WPMUDEV_Dashboard::$site->get_project_infos( $item['id'] );

	// Skip plugin if it's already installed.
	if ( $plugin->is_installed ) { continue; }

	// Skip plugins that are not compatible with current site.
	if ( ! $plugin->is_compatible ) { continue; }

	// Skip hidden/deprecated projects.
	if ( $plugin->is_hidden ) { continue; }

	$popular[] = $item;
	$count++;

	if ( $count >= 5 ) break;
}

// Find the 3 Upfront Themes, that are not installed yet.
$themes = array();
$theme_count = 0;
foreach ( $data['projects'] as $item ) {

	// Skip plugins.
	if ( 'theme' != $item['type'] ) { continue; }

	$theme = WPMUDEV_Dashboard::$site->get_project_infos( $item['id'] );

	// Skip theme if it's already installed.
	if ( $theme->is_installed ) { continue; }

	// Skip theme that are not compatible with current site.
	if ( ! $theme->is_compatible ) { continue; }

	// Skip hidden/deprecated projects.
	if ( $theme->is_hidden ) { continue; }

	$themes[] = $item;
	$theme_count++;

	if ( $theme_count >= 3 ) break;
}

// Render the page header section.
//$page_title = sprintf( __( 'Welcome, %s', 'wpmudev' ), $profile['name'] );
//$this->render_header( $page_title );

// New variables dashboard page
$url_support = $urls->real_support_url;
$url_logout = $urls->dashboard_url . '&clear_key=1';
$hub_url = $urls->remote_site . 'hub/';
$real_support_url = $urls->remote_site . 'support/';
$community_url = $urls->remote_site . 'hub/community/';
$learn_url = $urls->remote_site . 'academy/';
$upfront_builder_info_modal = $urls->plugins_url . '#pid=1107287';

?>
<?php
$page_title = __( 'Overview', 'wpmudev' );
$this->render_header( $page_title );
?>
<div class="wpmudui-row">
	<div class="wpmudui-col is-half-md">

		<div id="wpmud-dash-tools-box" class="wpmudui-box">
			<header class="wpmudui-box__header has-actions">
				<h2><?php esc_html_e( 'Tools', 'wpmudev' ); ?></h2>
				<div class="wpmudui-box__header__actions">
					<a href="<?php echo esc_url( $hub_url ); ?>" class="wpmudui-btn is-sm is-ghost"><?php esc_html_e( 'Go to my Hub', 'wpmudev' ); ?></a>
				</div>
			</header>
			<section class="wpmudui-box__main no-pad">
				<div class="wpmudui-box-padded-content">
					<p><?php esc_html_e( 'We don’t just build plugins &amp; themes… take advantage of our great services included with your membership.', 'wpmudev' ); ?></p>
				</div>
				<ul class="wpmudui-products-list is-tools">
					<li class="wpmudui-product-list__item">
						<div class="wpmudui-product-list__info">
							<div aria-hidden="true" class="wpmudui-product-list__avatar" style="background-image: url('<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url ); ?>image/tools/the-hub.png');"></div>
							<div class="wpmudui-product-list__details">
								<h4><?php esc_html_e( 'The Hub', 'wpmudev' ); ?></h4>
								<p><?php esc_html_e( 'Manage all your websites updates &amp; more in one place.', 'wpmudev' ); ?></p>
							</div>
						</div>
						<div class="wpmudui-product-list__cta">
							<a href="<?php echo esc_url( $hub_url ); ?>" class="wpmudui-product-list__btn is-external" tooltip="<?php esc_html_e( 'Go to The Hub', 'wpmudev' ); ?>"><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-arrow-right"></i></a>
						</div>
					</li>
					<li class="wpmudui-product-list__item">
						<div class="wpmudui-product-list__info">
							<div aria-hidden="true" class="wpmudui-product-list__avatar" style="background-image: url('<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url ); ?>image/tools/support.png');"></div>
							<div class="wpmudui-product-list__details">
								<h4><?php esc_html_e( 'Support', 'wpmudev' ); ?></h4>
								<p><?php esc_html_e( 'Get 24/7 expert WordPress support for any issue.', 'wpmudev' ); ?></p>
							</div>
						</div>
						<div class="wpmudui-product-list__cta">
							<a href="<?php echo esc_url( $real_support_url ); ?>" class="wpmudui-product-list__btn is-external" tooltip="<?php esc_html_e( 'Get Support', 'wpmudev' ); ?>"><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-arrow-right"></i></a>
						</div>
					</li>
					<li class="wpmudui-product-list__item">
						<div class="wpmudui-product-list__info">
							<div aria-hidden="true" class="wpmudui-product-list__avatar" style="background-image: url('<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url ); ?>image/tools/community.png');"></div>
							<div class="wpmudui-product-list__details">
								<h4><?php esc_html_e( 'Community', 'wpmudev' ); ?></h4>
								<p><?php esc_html_e( 'Discuss your favorite topics with other developers.', 'wpmudev' ); ?></p>
							</div>
						</div>
						<div class="wpmudui-product-list__cta">
							<a href="<?php echo esc_url( $community_url ); ?>" class="wpmudui-product-list__btn is-external" tooltip="<?php esc_html_e( 'View Forums', 'wpmudev' ); ?>"><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-arrow-right"></i></a>
						</div>
					</li>
					<li class="wpmudui-product-list__item">
						<div class="wpmudui-product-list__info">
							<div aria-hidden="true" class="wpmudui-product-list__avatar" style="background-image: url('<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url ); ?>image/tools/learn.png');"></div>
							<div class="wpmudui-product-list__details">
								<h4><?php esc_html_e( 'Learn', 'wpmudev' ); ?></h4>
								<p><?php esc_html_e( 'Become an expert by taking an Academy course.', 'wpmudev' ); ?></p>
							</div>
						</div>
						<div class="wpmudui-product-list__cta">
							<a href="<?php echo esc_url( $learn_url ); ?>" class="wpmudui-product-list__btn is-external" tooltip="<?php esc_html_e( 'Go to The Academy', 'wpmudev' ); ?>"><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-arrow-right"></i></a>
						</div>
					</li>
				</ul>
			</section>
		</div><!-- end wpmud-dash-tools-box -->

		<div id="wpmud-dash-themes-box" class="wpmudui-box">
			<header class="wpmudui-box__header has-actions">
				<h2><?php esc_html_e( 'Themes', 'wpmudev' ); ?></h2>
				<div class="wpmudui-box__header__actions">
					<a href="<?php echo esc_url( $urls->themes_url ); ?>" class="wpmudui-btn is-sm is-ghost"><?php esc_html_e( 'View '. $projects_nr['themes'] .' Themes', 'wpmudev' ); ?></a>
				</div>
			</header>
			<section class="wpmudui-box__main no-pad">
				<div class="wpmudui-box-padded-content">
					<p><?php esc_html_e( 'Our themes are built with our drag and drop theme framework Upfront. Here’s a selection of our most popular themes!', 'wpmudev' ); ?></p>
				</div>
				<ul class="wpmudui-products-list is-themes">
				<?php foreach ( $themes as $item ) : ?>
					<li class="wpmudui-product-list__item">
						<?php
						$url = WPMUDEV_Dashboard::$ui->page_urls->themes_url;
						$url .= '#pid=' . $item['id'];
						?>
						<div class="wpmudui-product-list__info">
							<div aria-hidden="true" class="wpmudui-product-list__avatar" style="background-image: url('<?php echo esc_url( $item['thumbnail'] ); ?>');"></div>
							<div class="wpmudui-product-list__details">
								<h4><?php esc_html_e( $item['name'] ); ?></h4>
								<p><?php esc_html_e( $item['short_description'] ); ?></p>
							</div>
						</div>
						<div class="wpmudui-product-list__cta">
							<?php
                            $res = WPMUDEV_Dashboard::$site->get_project_infos( $item['id'] );
                            if ( $res->is_compatible ) { ?>
                                <a href="<?php echo esc_url( $url ); ?>" class="wpmudui-product-list__btn" tooltip="<?php esc_html_e( 'View theme info', 'wpmudev' ); ?>"><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-plus"></i></a>
                            <?php } ?>
						</div>
					</li>
				<?php endforeach; ?>
				</ul>
			</section>
			<footer class="wpmudui-box__footer is-center">
				<p class="wpmudui-note"><?php echo sprintf( __( 'Did you know you can build your own theme from scratch using the <a href="%s">%s</a> plugin? Just install it and away you go!', 'wpmudev' ),  esc_url( $upfront_builder_info_modal ),'Upfront Builder' ); ?></p>
			</footer>
		</div><!-- end wpmud-dash-themes-box -->

	</div>

	<div class="wpmudui-col is-half-md">

	<?php if ( $my_project ) : ?>
		<div id="wpmud-dash-purchased-box" class="wpmudui-box">
			<header class="wpmudui-box__header has-actions">
				<h2><?php esc_html_e( 'Purchased', 'wpmudev' ); ?></h2>
				<div class="wpmudui-box__header__actions">
					<a href="<?php echo esc_url( $url_upgrade ); ?>" class="wpmudui-btn is-sm is-cta" target="_blank"><?php esc_html_e( 'Upgrade membership', 'wpmudev' ); ?></a>
				</div>
			</header>
			<section class="wpmudui-box__main no-pad">
				<ul class="wpmudui-products-list is-standalone is-plugins">
					<li class="wpmudui-product-list__item">
						<?php
						$url = WPMUDEV_Dashboard::$ui->page_urls->plugins_url;
						$url .= '#pid=' . $my_project->pid;
						?>
						<div class="wpmudui-product-list__info">
							<div aria-hidden="true" class="wpmudui-product-list__avatar" style="background-image: url('<?php echo esc_url( $my_project->url->thumbnail ); ?>');">
							</div>
							<div class="wpmudui-product-list__details">
								<h4><?php echo esc_html( $my_project->name ); ?></h4>
								<p><?php echo esc_html( $my_project->info ); ?></p>
							</div>
						</div>
						<div class="wpmudui-product-list__cta">
							<a href="<?php echo esc_url( $url ); ?>" class="wpmudui-product-list__btn" tooltip="<?php esc_html_e( 'View plugin info', 'wpmudev' ); ?>"><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-plus"></i></a>
						</div>
					</li>
				</ul>
			</section>
		</div>
	<?php endif; ?>

	<div id="wpmud-dash-plugins-box" class="wpmudui-box">
		<header class="wpmudui-box__header">
			<h2><?php esc_html_e( 'Plugins', 'wpmudev' ); ?></h2>
		</header>
		<section class="wpmudui-box__main no-pad">
			<div class="wpmudui-box-padded-content">
				<p><?php esc_html_e( 'Your WPMU DEV membership gives you access to 100+ premium plugins. Here’s our most popular!', 'wpmudev' ); ?></p>
			</div>
			<ul class="wpmudui-products-list is-plugins">
			<?php foreach ( $popular as $item ) : ?>
				<li class="wpmudui-product-list__item">
					<?php
					$url = WPMUDEV_Dashboard::$ui->page_urls->plugins_url;
					$url .= '#pid=' . $item['id'];
					?>
					<div class="wpmudui-product-list__info">
						<div aria-hidden="true" class="wpmudui-product-list__avatar" style="background-image: url('<?php echo esc_url( $item['thumbnail'] ); ?>');">
						</div>
						<div class="wpmudui-product-list__details">
							<h4><?php esc_html_e( $item['name'] ); ?></h4>
							<p><?php esc_html_e( $item['short_description'] ); ?></p>
						</div>
					</div>
					<div class="wpmudui-product-list__cta">
						<?php
                        $res = WPMUDEV_Dashboard::$site->get_project_infos( $item['id'] );
                        if ( $res->is_compatible ) { ?>
                            <a href="<?php echo esc_url( $url ); ?>" class="wpmudui-product-list__btn" tooltip="<?php esc_html_e( 'View plugin info', 'wpmudev' ); ?>"><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-plus"></i></a>
                        <?php } ?>
					</div>
				</li>
			<?php endforeach; ?>
			</ul>
		</section>
		<footer class="wpmudui-box__footer">
			<a href="<?php echo esc_url( $urls->plugins_url ); ?>" class="wpmudui-btn is-sm is-ghost"><?php esc_html_e( 'View '. $projects_nr['plugins'] .' Plugins', 'wpmudev' ); ?></a>
		</footer>
	</div><!-- end wpmud-dash-plugins-box -->


	</div>
</div>

<?php if ( isset( $_GET['synced'] ) ) { //auto show modal after login redirect ?>
<dialog id="confirmation-modal" title="You’re connected!" class="no-close wpmudui wpmudui-modal has-bottom-hero auto-show">
	<div class="wpmudui-alert is-success">
		<p><i aria-hidden="true" class="wpmudui-fi wpmudui-fi-circle-tick"></i> <?php esc_html_e( 'Great, your website is now synced to the WPMU DEV Hub!', 'wpmudev' ); ?></p>
	</div>
	<p><?php printf( __( 'Keep this plugin installed to access Pro-only features, 24/7 support and <a href="%s" target=_blank">use the Hub</a> to manage all your websites in one handy place.', 'wpmudev' ), $hub_url ); ?></p>
	<p class="wpmdui-ctn-right">
		<button class="wpmudui-btn is-brand close"><?php esc_html_e( 'Get Started', 'wpmudev' ); ?></button>
	</p>
</dialog><!-- end confirmation-modal-->
<?php } //end modal ?>