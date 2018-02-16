<?php
/**
 * Dashboard template: Support Functions
 *
 * Manage support tickets, grant support-staff access and view System
 * configuration.
 *
 * Following variables are passed into the template:
 *   $data (membership data)
 *   $profile (user profile data)
 *   $urls (urls of all dashboard menu items)
 *   $staff_login (remote access status/details)
 *   $notes (notes for support staff)
 *   $access_logs (list of all support-staff logins)
 *
 * @since  4.0.0
 * @package WPMUDEV_Dashboard
 */

// Render the page header section.
$page_title = __( 'Support', 'wpmudev' );
$page_title .= sprintf(
	' <a href="%s&view=system" class="wpmudui-btn is-ghost">%s</a>',
	$urls->support_url,
	__( 'System Info', 'wpmudev' )
);
$this->render_header( $page_title );

$url_grant = wp_nonce_url( add_query_arg( 'action', 'remote-grant', $urls->support_url ), 'remote-grant', 'hash' );
$url_revoke = wp_nonce_url( add_query_arg( 'action', 'remote-revoke', $urls->support_url ), 'remote-revoke', 'hash' );
$url_extend = wp_nonce_url( add_query_arg( 'action', 'remote-extend', $urls->support_url ), 'remote-extend', 'hash' );
$url_all_tickets = $urls->remote_site . 'hub/support';
$url_search = $urls->remote_site . 'forums/search.php';
$url_open_ticket = $urls->remote_site . 'forums/forum/support/#question-modal';

if ( $notes && ! empty( $_COOKIE['wpmudev_is_staff'] ) || ! empty( $_GET['staff'] ) ) {
	$notes_class = 'active';
} else {
	$notes_class = '';
}

$threads = $profile['forum']['support_threads'];
$open_threads = array();
foreach ( $threads as $thread ) {
	if ( empty( $thread['title'] ) ) { continue; }
	if ( empty( $thread['status'] ) ) { continue; }
	if ( 'resolved' == $thread['status'] ) { continue; }

	$open_threads[] = $thread;
}

$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );
?>

<div class="row row-space">
	<form id="support-search" action="<?php echo esc_url( $url_search ); ?>" target="_blank" method="GET">
		<label for="support-search-id" class="wpdui-sr-only"><?php esc_attr_e( 'Search WPMU DEV support resources', 'wpmudev' ); ?></label>
		<input
			name="q"
			id="support-search-id"
			type="search"
			data-no-empty-msg="true"
			placeholder="<?php esc_attr_e( 'Search WPMU DEV support resources', 'wpmudev' ); ?>" />
	</form>
</div>

<div class="row">
<div class="col-half">
	<section class="dev-box my-tickets">
		<div class="box-title">
			<?php if ( ! empty( $open_threads ) ) : ?>
			<span class="buttons">
				<a href="<?php echo esc_url( $url_all_tickets ); ?>" class="wpmudui-btn is-sm is-brand is-ghost" target="_blank">
					<?php esc_html_e( 'View all', 'wpmudev' ); ?>
				</a>
			</span>
			<?php endif; ?>
			<h3><?php esc_html_e( 'My Tickets', 'wpmudev' ); ?></h3>
		</div>
		<div class="box-content">
			<?php if ( empty( $open_threads ) ) : ?>
			<div class="tc">
			<span aria-hidden="true" class="icon icon-big icon-support"></span>
			<h4>
				<?php esc_html_e( 'You have no support tickets, woop!', 'wpmudev' ); ?>
			</h4>
			<p aria-hidden="true" class="space-b">
				<?php esc_html_e( 'When you ask a support question, it will appear here. You can also access this in the WPMU DEV Hub.', 'wpmudev' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $url_open_ticket ); ?>" target="_blank" class="wpmudui-btn is-brand">
				<?php esc_html_e( 'Open new ticket', 'wpmudev' ); ?>
				</a>
			</p>
			</div>
			<?php else : ?>
			<ul class="dev-list top nowrap">
				<li class="list-header">
				<div>
					<span class="list-label">
						<?php esc_html_e( 'Topic', 'wpmudev' ); ?>
					</span>
					<span class="list-detail">
						<?php esc_html_e( 'Replies', 'wpmudev' ); ?>
					</span>
				</div>
				</li>
				<?php foreach ( $open_threads as $item ) : ?>
				<li>
				<div>
					<span class="list-label">
						<a href="<?php echo esc_url( $item['link'] ); ?>" target="_blank">
						<?php echo esc_html( $item['title'] ); ?>
						</a>
					</span>
					<span class="list-detail">
						<span class="count reply <?php if ( $item['unread'] ) { echo 'notification'; } ?>">
						<?php echo esc_html( $item['posts'] ); ?>
						</span>
					</span>
				</div>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</section>
</div>

<div class="col-half">
	<section class="dev-box">
		<div class="box-title">
			<span class="buttons">
				<?php if ( $staff_login->enabled ) : ?>
				<a href="<?php echo esc_url( $url_extend ); ?>" class="wpmudui-btn is-sm is-brand is-ghost one-click tooltip-l" tooltip="<?php esc_attr_e( 'Add another 3 days of support access', 'wpmudev' ); ?>">
					<?php esc_html_e( 'Extend', 'wpmudev' ); ?>
				</a>
				<a href="<?php echo esc_url( $url_revoke ); ?>" class="wpmudui-btn is-sm is-ghost one-click">
					<?php esc_html_e( 'Revoke', 'wpmudev' ); ?>
				</a>
				<?php endif; ?>
				<a role="button" href="#access-info" rel="dialog" tooltip="<?php esc_attr_e( 'Security details', 'wpmudev' ); ?>" class="tooltip-<?php echo is_rtl() ? 'left' : 'right' ?> tooltip-s button button-text button-small">
					<i aria-hidden="true" class="dev-icon dev-icon-info"></i>
				</a>
			</span>
			<h3><?php esc_html_e( 'Support Access', 'wpmudev' ); ?></h3>
		</div>
		<div class="box-content">
			<?php if ( ! $staff_login->enabled ) : ?>
			<p>
			<?php esc_html_e( 'Enabling support access will let our support heroes access your website admin area for 5 days to help you with an issue.', 'wpmudev' ); ?>
			</p>
			<a href="<?php echo esc_url( $url_grant ); ?>" class="wpmudui-btn is-lg block one-click">
				<?php esc_html_e( 'Grant Support Access', 'wpmudev' ); ?>
			</a>
			<?php else : ?>
			<div class="active-staff-access tc tooltip-l" tooltip="<?php echo esc_html( __( 'Expires:', 'wpmudev' ) . ' ' . date( get_option( 'date_format') . ' ' . get_option( 'time_format'), $staff_login->expires ) ); ?>">
				<i aria-hidden="true" class="dev-icon dev-icon-lock"></i>
				<?php
				printf(
					esc_html__( 'Access active for %1$s', 'wpmudev' ),
					'<strong>' . esc_html( human_time_diff( $staff_login->expires ) ) . '</strong>'
				);
				?>
			</div>
			<form class="staff-notes <?php echo esc_attr( $notes_class ); ?>" method="POST" action="<?php echo esc_url( $urls->support_url ); ?>">
				<input type="hidden" name="action" value="staff-note" />
				<?php wp_nonce_field( 'staff-note', 'hash' ) ?>
				<label for="support-staff-notes-id">
				<?php esc_html_e( 'If you think it would help, leave our support heroes a quick message to let them know the details of your issue.', 'wpmudev' ); ?>
				</label>
				<textarea id="support-staff-notes-id" name="notes"><?php echo esc_textarea( $notes ); ?></textarea>
				<button class="wpmudui-btn is-brand float-r one-click">
					<?php esc_html_e( 'Save', 'wpmudev' ); ?>
				</button>
			</form>
			<ul class="dev-list inline top">
				<li class="list-header">
					<div>
						<span class="list-label"><?php esc_html_e( 'Support logins', 'wpmudev' ); ?></span>
						<span class="list-detail"></span>
					</div>
				</li>
				<?php if ( empty( $access_logs ) ) : ?>
				<li>
					<div>
						<span class="list-label"><?php esc_html_e( 'No one from Support has logged in yet. Sit tight!', 'wpmudev' ); ?></span>
						<span class="list-detail"></span>
					</div>
				</li>
				<?php else : ?>
				<?php foreach ( $access_logs as $time => $name ) : ?>
				<?php $time = WPMUDEV_Dashboard::$site->to_localtime( $time ); ?>
				<li>
					<div>
						<span class="list-label"><?php echo esc_html( $name ); ?></span>
						<span class="list-detail">
							<?php echo esc_html( date_i18n( $date_format, $time ) ); ?>
							@ <?php echo esc_html( date_i18n( $time_format, $time ) ); ?>
						</span>
					</div>
				</li>
				<?php endforeach; ?>
				<?php endif; ?>
			</ul>
			<?php endif; ?>
		</div>
	</section>
</div>

</div>
<?php $this->load_template( 'element-last-refresh' ); ?>



<dialog id="access-info" title="<?php esc_attr_e( 'Support Access is secure', 'wpmudev' ); ?>" class="wpmudui wpmudui-modal">
<p>
	<?php
	esc_html_e( 'When you click the "Grant Access" button a random 64 character access token is generated that is only good for 96 hours (5 days) and saved in your Database. This token is sent to the WPMU DEV API over an SSL encrypted connection to prevent eavesdropping, and stored on our secure servers. This access token is in no way related to your password, and can only be used from our closed WPMU DEV API system for temporary access to this site.', 'wpmudev' );
	?>
</p>
<p>
	<?php
	echo wp_kses_post( __( '<b>Only current WPMU DEV support staff can use this token</b> to login as your user account by submitting a special form that only they have access to. This will give them 1 hour of admin access to this site before their login cookie expires. Every support staff login during the 5 day period is logged locally and you can view the details on this page.', 'wpmudev' ) );
	?>
</p>
<p>
	<?php
	echo wp_kses_post( __( '<b>You may at any time revoke this access</b> which invalidates the token and it will no longer be usable. If you have special security concerns and you would like to disable the support access tab and functionality completely and permanently for whatever reason, you may do so by adding this line to your wp-config.php file:', 'wpmudev' ) );
	?><br />
	<code>define('WPMUDEV_DISABLE_REMOTE_ACCESS', true);</code>
</p>
</dialog>