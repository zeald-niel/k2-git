<?php

function pmui_pmxi_before_post_import( $import_id )
{
	$import = new PMXI_Import_Record();
	$import->getById( $import_id );
	if ( ! $import->isEmpty() and $import->options['custom_type'] == 'import_users' and ! empty($import->options['do_not_send_password_notification']))
	{
		add_filter('send_password_change_email', 'pmui_do_not_send_password_notification', 10, 3);
		add_filter('send_email_change_email', 'pmui_send_email_change_email', 10, 3);
	}
}