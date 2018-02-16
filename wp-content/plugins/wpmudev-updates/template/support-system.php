<?php
/**
 * Dashboard template: Support Functions > System Info
 * This file is loaded when the URL param `&view=system` is set.
 *
 * Displays details about the current WordPress setup.
 *
 * Following variables are passed into the template:
 *   $data (membership data)
 *   $profile (user profile data)
 *   $urls (urls of all dashboard menu items)
 *
 * @since  4.0.0
 * @package WPMUDEV_Dashboard
 */

// Render the page header section.
$page_title = __( 'System Info', 'wpmudev' );
$page_title .= sprintf(
	' <a href="%s" class="wpmudui-btn is-ghost">%s</a>',
	$urls->support_url,
	__( 'Back to support', 'wpmudev' )
);
$this->render_header( $page_title );

include_once( 'part-system-info.php' );