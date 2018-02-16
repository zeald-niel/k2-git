<?php
require_once('admin.php');

if (isset($_POST['upload_plugin']) || isset($_POST['upload_theme']))
{
    $err_title = __('WordPress Failure Notice');
    $err_redirect = '<p><a href="' . remove_query_arg(wp_get_referer()) . '">' . __('Please try again.') . '</a></p>';
    if (isset($_POST['upload_theme']) && check_admin_referer('upload-theme'))
    {
        if ( !current_user_can('edit_themes') )
            wp_die('<p>'.__('You do not have sufficient permissions to upload templates for this blog.').'</p>');
        $target_dir = 'themes';
        $location = 'themes.php';
    } elseif (isset($_POST['upload_plugin']) && check_admin_referer('upload-plugin'))
    {
        if ( !current_user_can('edit_plugins') )
            wp_die('<p>'.__('You do not have sufficient permissions to upload plugins for this blog.').'</p>');
        $target_dir = 'plugins';
        $location = 'plugins.php';
    } else {
        wp_die('<p>' . __('Error: Uploading failed. Not enough parameters.') . '</p>'.$err_redirect, $err_title);
    }

    $afile = wp_handle_upload($_FILES['package'], array('test_form' => false));
    $dest_path = ABSPATH.'/wp-content/' . $target_dir;

    require_once('pclzip.lib.php');
    $archive = new PclZip($afile['file']);
    if ($archive->extract(PCLZIP_OPT_PATH, $dest_path) == 0) {
        unlink($afile['file']);
        wp_die(__("Can't unpack zip-file: ").$archive->errorInfo(true) . $err_redirect, $err_title);
    } else {
        unlink($afile['file']);
        wp_redirect($location);
    }
}
?>
