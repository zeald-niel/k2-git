<?php
/**
 * Dashboard template: Login form
 *
 * This template is displayed when no API key was found.
 * Once the user logged into the WPMUDEV account this template is not used
 * anymore (until the user loggs out again).
 *
 * Following variables are passed into the template:
 *   $key_valid
 *   $connection_error
 *   $urls (urls of all dashboard menu items)
 *
 * @since  4.0.0
 * @package WPMUDEV_Dashboard
 */

$register_url = 'https://premium.wpmudev.org/#trial';
$reset_url = 'https://premium.wpmudev.org/wp-login.php?action=lostpassword';
$account_url = 'https://premium.wpmudev.org/hub/account/';
$trial_info_url = 'https://premium.wpmudev.org/manuals/how-free-trials-work/';
$websites_url = 'https://premium.wpmudev.org/hub/my-websites/';
$security_info_url = 'https://premium.wpmudev.org/manuals/hub-security/';

$login_url = $urls->dashboard_url;
if ( ! empty( $_GET['pid'] ) ) {
	$login_url = add_query_arg( 'pid', $_GET['pid'], $login_url );
}

$last_user = WPMUDEV_Dashboard::$site->get_option( 'auth_user' );

// Check for errors.
$errors = array();
if ( isset( $_GET['api_error'] ) ) {

	if ( 1 == $_GET['api_error'] || 'auth' == $_GET['api_error'] ) { //invalid creds

		$errors[] = sprintf(
			'<i aria-hidden="true" class="wpmudui-fi wpmudui-fi-circle-warning"></i> %s <a class="wpmud-link" href="%s" target="_blank">%s</a>',
			__( 'Invalid Username or Password. Please try again.', 'wpmudev' ),
			$reset_url,
			__( 'Forgot your password?', 'wpmudev' )
		);

	} else if ( 'in_trial' == $_GET['api_error'] ) { //trial members can only login to first time domains

		if ( WPMUDEV_Dashboard::$site->is_localhost() ) {
			$errors[] = sprintf(
				'%s<br><a href="%s" target="_blank">%s</a> <a href="%s" target="_blank" class="right">%s</a>',
				sprintf( __( 'This local development site url has previously been registered with WPMU DEV by the user %s. To use this site url, connect with the original user or upgrade your trial to a full membership. Alternatively, try a more uniquely named development site url. Contact support if you need further assistance.', 'wpmudev' ), esc_html( $_GET['display_name'] ) ),
				$account_url,
				__( 'Upgrade membership', 'wpmudev' ),
				$trial_info_url,
				__( 'More information &raquo;', 'wpmudev' )
			);
		} else {
			$errors[] = sprintf(
				'%s<br><a href="%s" target="_blank">%s</a> <a href="%s" target="_blank" class="right">%s</a>',
				sprintf( __( 'This domain has previously been registered with WPMU DEV by the user %s. To use the dashboard plugin on this domain, you can either connect with the original account (and upgrade it if necessary), or upgrade your trial to a full WPMU DEV membership.', 'wpmudev' ), esc_html( $_GET['display_name'] ) ),
				$account_url,
				__( 'Upgrade membership', 'wpmudev' ),
				$trial_info_url,
				__( 'More information &raquo;', 'wpmudev' )
			);
		}

	} else if ( 'already_registered' == $_GET['api_error'] ) { //IMPORTANT for security we make sure this site has been logged out of before another user can take it over

		if ( WPMUDEV_Dashboard::$site->is_localhost() ) {
			$errors[] = sprintf(
				'%s<br><a href="%s" target="_blank">%s</a> <a href="%s" target="_blank" class="right">%s</a>',
				sprintf( __( 'This local development site url is currently registered to %s. For security reasons they will need to go to the WPMU DEV Hub and remove this domain before you can connect. If that account is not yours, then make your local development site url more unique. Contact support if you need further assistance.', 'wpmudev' ), esc_html( $_GET['display_name'] ) ),
				$websites_url,
				__( 'Remove site', 'wpmudev' ),
				$security_info_url,
				__( 'More information &raquo;', 'wpmudev' )
			);
		} else {
			$errors[] = sprintf(
				'%s<br><a href="%s" target="_blank">%s</a> <a href="%s" target="_blank" class="right">%s</a>',
				sprintf( __( 'This site is currently registered to %s. For security reasons they will need to go to the WPMU DEV Hub and remove this domain before you can connect. If you do not have access to that account, and have no way of contacting that user, please contact support for assistance.', 'wpmudev' ), esc_html( $_GET['display_name'] ) ),
				$websites_url,
				__( 'Remove site', 'wpmudev' ),
				$security_info_url,
				__( 'More information &raquo;', 'wpmudev' )
			);
		}

	} else { //this in case we add new error types in the future

		$errors[] = __( 'Unknown error. Please update the WPMU DEV Dashboard plugin and try again.', 'wpmudev' );

	}
} elseif ( $connection_error ) {
	// Variable `$connection_error` is set by the UI function `render_dashboard`.
	$errors[] = sprintf(
		'%s<br><br>%s<br><br><em>%s</em>',
		sprintf(
			__( 'Your server had a problem connecting to WPMU DEV: "%s". Please try again.', 'wpmudev' ),
			WPMUDEV_Dashboard::$api->api_error
		),
		__( 'If this problem continues, please contact your host with this error message and ask:', 'wpmudev' ),
		sprintf(
			__( '"Is php on my server properly configured to be able to contact %s with a POST HTTP request via fsockopen or CURL?"', 'wpmudev' ),
			WPMUDEV_Dashboard::$api->rest_url( '' )
		)
	);
} elseif ( ! $key_valid ) {
	// Variable `$key_valod` is set by the UI function `render_dashboard`.
	$errors[] = __( 'Your API Key was invalid. Please try again.', 'wpmudev' );
}

// Get the login URL.
$form_action = WPMUDEV_Dashboard::$api->rest_url( 'authenticate' );

?>
<div class="wpmud-system-info"><a href="<?php echo esc_url(add_query_arg( 'view', 'system', $urls->dashboard_url )); ?>" class="wpmudui-btn is-sm is-ghost"><?php esc_html_e( 'System Info', 'wpmudev' ); ?></a></div>
<section id="wpmud-login" class="wpmud-login">
	<div id="wpmud-login-box" class="wpmudui-box is-sm is-center is-login">
		<section class="wpmudui-box__main">
			<h2 class="wpmudui-brand-title"><?php esc_html_e( 'Connect with WPMU DEV', 'wpmudev' ); ?></h2>
			<p><?php esc_html_e( 'Log in using your WPMU DEV account email and password.', 'wpmudev' ); ?></p>
			<form action="<?php echo esc_url( $form_action ); ?>" method="post" id="wpmudui-login-form" class="wpmudui-form wpmudui-form--login">

				<div class="wpmudui-form-field">
					<label for="user_name" class="wpmudui-form-field__label"><?php esc_html_e( 'Account email', 'wpmudev' ); ?></label>
					<input type="text" class="wpmudui-form-field__input" name="username" id="user_name" autocomplete="off" placeholder="<?php echo esc_attr__( 'Your email address', 'wpmudev' ); ?>" value="<?php echo esc_attr( $last_user ); ?>">
				</div><!-- end wpmudui-form-field -->

				<div class="wpmudui-form-field is-last">
					<label for="password" class="wpmudui-form-field__label"><?php esc_html_e( 'Account password', 'wpmudev' ); ?></label>
					<input type="password" class="wpmudui-form-field__input is-password" name="password" id="password" autocomplete="off" placeholder="<?php echo esc_attr__( 'Your password', 'wpmudev' ); ?>">
				</div><!-- end wpmudui-form-field -->

				<?php
				// Display the errors.
				if ( count( $errors ) ) {
					?><div class="wpmudui-form-errors">
					<?php
					foreach ( $errors as $message ) {
						?>
						<div class="wpmudui-alert is-error is-standalone">
						<p><?php
						// @codingStandardsIgnoreStart: Message contains HTML, no escaping!
						echo $message;
						// @codingStandardsIgnoreEnd
						?></p></div>
						<?php
					}
					?>
					</div><!-- end wpmudui-form-errors --><?php
				} ?>

				<div class="wpmudui-form-cta-fields">
					<div class="wpmudui-form-cta__item is-left">
						<p class="wpmudui-form-note">
							<?php
							printf(
								esc_html__( 'Don’t have an account? %sSign up%s today!', 'wpmudev' ),
								'<a href="' . esc_url( $register_url ) . '" target="_blank">',
								'</a>'
							);
							?>
						</p>
					</div>
					<div class="wpmudui-form-cta__item is-right">
						<button type="submit" class="wpmudui-btn is-brand one-click">
							<?php esc_html_e( 'Connect', 'wpmudev' ); ?>
						</button>
					</div>
				</div><!-- end wpmudui-form-cta-fields -->

				<input type="hidden" name="redirect_url" value="<?php echo esc_url( $login_url ); ?>">
				<input type="hidden" name="domain" value="<?php echo esc_url( network_site_url() ); ?>">

			</form><!-- end wpmudui-login-form -->
		</section>
	</div><!-- end wpmudui-login-box -->
</section><!-- end wpmud-login -->