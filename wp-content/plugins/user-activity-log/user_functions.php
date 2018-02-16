<?php
/*
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Create table "wp_user_activity" when activate plugin
 */
if (!function_exists('ual_user_activity_table_create')) {

    function ual_user_activity_table_create() {
        global $wpdb;
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/user-activity-log/user_activity_log.php', $markup = true, $translate = true);
        $current_version = $plugin_data['Version'];
        $old_table_name = $wpdb->prefix . "user_activity";
        $table_name = $wpdb->prefix . "ualp_user_activity";
        //table is not created. you may create the table here.
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $old_table_exist = ual_checkif_table_rename();
            if (!$old_table_exist) {
                $create_table_query = "CREATE TABLE $table_name (uactid bigint(20) unsigned NOT NULL auto_increment,post_id int(20) unsigned NOT NULL,post_title varchar(250) NOT NULL,user_id bigint(20) unsigned NOT NULL default '0',user_name varchar(50) NOT NULL,user_role varchar(50) NOT NULL,user_email varchar(50) NOT NULL,ip_address varchar(50) NOT NULL,modified_date datetime NOT NULL default '0000-00-00 00:00:00',object_type varchar(50) NOT NULL default 'post',action varchar(50) NOT NULL,PRIMARY KEY (uactid))";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta($create_table_query);
            } else {
                $wpdb->query("RENAME TABLE " . $old_table_name . " TO " . $table_name);
            }
        }
        update_option('ual_version', $current_version);
    }

}
add_action('activate_plugin', 'ual_user_activity_table_create');

/*
 * Rename table "user_activity" to "ualp"
 */
if (!function_exists('ual_checkif_table_rename')) {

    function ual_checkif_table_rename() {
        global $wpdb;
        $old_table_name = $wpdb->prefix . "user_activity";
        $table_name = $wpdb->prefix . "ualp_user_activity";
        if ($wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") == $old_table_name) {
            if ($wpdb->get_var("SHOW COLUMNS FROM " . $old_table_name . " LIKE 'uactid'") == 'uactid') {
                return true;
            }
            return false;
        }
        return false;
    }

}

/*
 * Run database updater when plugin updated
 */
if (!function_exists('ual_database_upgrade')) {

    function ual_database_upgrade() {
        global $wpdb;
        $old_table_name = $wpdb->prefix . "user_activity";
        $table_name = $wpdb->prefix . "ualp_user_activity";
        $old_table_exist = ual_checkif_table_rename();
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/user-activity-log/user_activity_log.php', $markup = true, $translate = true);
        $current_version = $plugin_data['Version'];
        if ($old_table_exist) {
            ?>
            <div class="updated">
                <p>
                    <strong>
                        <?php _e('User Activity Log Data Update', 'user-activity-log'); ?>
                    </strong> &#8211; <?php _e('We need to update your database to the latest version.', 'user-activity-log'); ?>
                </p>
                <p class="submit">
                    <a href="<?php echo esc_url(add_query_arg('do_update_ual', 'do', admin_url('admin.php?page=general_settings_menu'))); ?>" class="ual-update-now button-primary">
                        <?php _e('Run the updater', 'user-activity-log'); ?>
                    </a>
                </p>
            </div>
            <?php
            if (isset($_GET['do_update_ual']) && $_GET['do_update_ual'] == 'do') {
                update_option('ual_version', $current_version);
                $wpdb->query("RENAME TABLE " . $old_table_name . " TO " . $table_name);
            }
            ?>
            <script type="text/javascript">
                jQuery('.ual-update-now').click('click', function () {
                    return window.confirm('<?php echo esc_js(__('It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'user-activity-log')); ?>');
                });
            </script>
            <?php
        }
    }

}
add_action('admin_notices', 'ual_database_upgrade');

/*
 * Insert record into wp_user_activity table
 *
 * @param int $post_id Post ID.
 * @param string $post_title Post Title.
 * @param string $obj_type Object Type (Plugin, Post, User etc.).
 * @param int $current_user_id current user id.
 * @param string $current_user current user name.
 * @param string $user_role current user Role.
 * @param string $user_mail current user Email address.
 * @param datetime $modified_date current user's modified time.
 * @param string $ip current user's IP address.
 * @param string $action current user's activity name.
 *
 */
if (!function_exists('ual_user_activity_add')) {

    function ual_user_activity_add($post_id, $post_title, $obj_type, $current_user_id, $current_user, $user_role, $user_mail, $modified_date, $ip, $action) {
        global $wpdb;
        $table_name = $wpdb->prefix . "ualp_user_activity";
        $post_title = addslashes($post_title);
        $insert_query = $wpdb->query("INSERT INTO $table_name (post_id,post_title,user_id, user_name, user_role, user_email, ip_address, modified_date, object_type, action) VALUES ('$post_id','$post_title','$current_user_id', '$current_user', '$user_role','$user_mail', '$ip', '$modified_date', '$obj_type', '$action')");
    }

}

/*
 * Get activity
 *
 * @param string $action current user's activity name.
 * @param string $obj_type Object Type (Plugin, Post, User etc.).
 * @param int $post_id Post ID.
 * @param string $post_title Post Title.
 *
 */
if (!function_exists('ual_get_activity_function')) {

    function ual_get_activity_function($action, $obj_type, $post_id, $post_title) {
        $current_user_id = $current_user_display_name = $user_mail = $user_role = $modified_date = '';
        $modified_date = current_time('mysql');
        $ip = $_SERVER['REMOTE_ADDR'];
        $current_user_id = get_current_user_id();
        $user = new WP_User($current_user_id);
        $user_mail = $user->user_email;
        global $wp_roles;
        $role_name = array();
        if (!empty($user->roles) && is_array($user->roles)) {
            foreach ($user->roles as $user_r) {
                $role_name[] = $wp_roles->role_names[$user_r];
            }
            $user_role = implode(', ', $role_name);
        }

        $current_user_display_name = $user->display_name;
        ual_user_activity_add($post_id, $post_title, $obj_type, $current_user_id, $current_user_display_name, $user_role, $user_mail, $modified_date, $ip, $action);
    }

}

/*
 * Add activity for the current user when login
 *
 * @param string $user_login current user's login name.
 *
 */
if (!function_exists('ual_shook_wp_login')):

    function ual_shook_wp_login($user_login, $user) {
        global $wpdb;
        $action = "logged in";
        $obj_type = "user";
        $user_mail = $user->user_email;
        $current_user_id = $user->ID;
        $user = new WP_User($current_user_id);
        if (!empty($user->roles) && is_array($user->roles)) {
            foreach ($user->roles as $role)
                $user_role = $role;
        }
        $post_id = $current_user_id;
        $post_title = $user_login;
        $modified_date = current_time('mysql');
        $ip = $_SERVER['REMOTE_ADDR'];
        $current_user_display_name = $user->display_name;
        ual_user_activity_add($post_id, $post_title, $obj_type, $current_user_id, $current_user_display_name, $user_role, $user_mail, $modified_date, $ip, $action);
    }

endif;

/*
 * Get activity for the current user when logout
 */
if (!function_exists('ual_shook_wp_logout')):

    function ual_shook_wp_logout() {
        $action = "logged out";
        $obj_type = "user";
        $post_id = get_current_user_id();
        $user_nm = get_user_by('id', $post_id);
        $post_title = $user_nm->user_login;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the delete user
 *
 * @param int $user Post ID
 *
 */
if (!function_exists('ual_shook_delete_user')):

    function ual_shook_delete_user($user) {
        $action = "delete user";
        $obj_type = "user";
        $post_id = $user;
        $user_nm = get_user_by('id', $post_id);
        $post_title = $user_nm->user_login;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the registered user
 *
 * @param int $user Post ID
 *
 */
if (!function_exists('ual_shook_user_register')):

    function ual_shook_user_register($user) {
        $action = "user register";
        $obj_type = "user";
        $post_id = $user;
        $user_nm = get_user_by('id', $post_id);
        $post_title = $user_nm->user_login;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - update profile
 *
 * @param int $user Post ID
 *
 */
if (!function_exists('ual_shook_profile_update')):

    function ual_shook_profile_update($user) {
        $action = "profile update";
        $obj_type = "user";
        $post_id = $user;
        $user_nm = get_user_by('id', $post_id);
        $post_title = $user_nm->user_login;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - add attach media file
 *
 * @param int $attach Post ID
 *
 */
if (!function_exists('ual_shook_add_attachment')):

    function ual_shook_add_attachment($attach) {
        $action = "added attachment";
        $obj_type = "attachment";
        $post_id = $attach;
        $post_title = get_the_title($post_id);
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - edit attach media file
 *
 * @param int $attach Post ID
 *
 */
if (!function_exists('ual_shook_edit_attachment')):

    function ual_shook_edit_attachment($attach) {
        $post_id = $attach;
        $post_title = get_the_title($post_id);
        $action = "updated attachment";
        $obj_type = "attachment";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - delete attach media file
 *
 * @param int $attach Post ID
 *
 */
if (!function_exists('ual_shook_delete_attachment')):

    function ual_shook_delete_attachment($attach) {
        $post_id = $attach;
        $post_title = get_the_title($post_id);
        $action = "deleted attachment";
        $obj_type = "attachment";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Insert Comment
 *
 * @param int $comment Comment ID
 *
 */
if (!function_exists('ual_shook_wp_insert_comment')):

    function ual_shook_wp_insert_comment($comment) {
        $action = "insert comment";
        $obj_type = "comment";
        $comment_id = $comment;
        $com = get_comment($comment_id);
        $post_id = $com->comment_post_ID;
        $post_link = get_edit_post_link($post_id);
        $post_title = "<a target='blank' href='$post_link'>" . get_the_title($post_id) . "</a>";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Edit Comment
 *
 * @param int $comment Comment ID
 *
 */
if (!function_exists('ual_shook_edit_comment')):

    function ual_shook_edit_comment($comment) {
        $action = "update comment";
        $obj_type = "comment";
        $comment_id = $comment;
        $com = get_comment($comment_id);
        $post_id = $com->comment_post_ID;
        $post_link = get_edit_post_link($post_id);
        $post_title = "<a target='blank' href='$post_link'>" . get_the_title($post_id) . "</a>";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Trash Comment
 *
 * @param int $comment Comment ID
 *
 */
if (!function_exists('ual_shook_trash_comment')):

    function ual_shook_trash_comment($comment) {
        $action = "trash comment";
        $obj_type = "comment";
        $comment_id = $comment;
        $com = get_comment($comment_id);
        $post_id = $com->comment_post_ID;
        $post_title = get_the_title($post_id);
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Spam Comment
 *
 * @param int $comment Comment ID
 *
 */
if (!function_exists('ual_shook_spam_comment')):

    function ual_shook_spam_comment($comment) {
        $action = "spam comment";
        $obj_type = "comment";
        $comment_id = $comment;
        $com = get_comment($comment_id);
        $post_id = $com->comment_post_ID;
        $post_title = get_the_title($post_id);
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Unspam Comment
 *
 * @param int $comment Comment ID
 *
 */
if (!function_exists('ual_shook_unspam_comment')):

    function ual_shook_unspam_comment($comment) {
        $action = "unspam comment";
        $obj_type = "comment";
        $comment_id = $comment;
        $com = get_comment($comment_id);
        $post_id = $com->comment_post_ID;
        $post_title = get_the_title($post_id);
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Delete Comment
 *
 * @param int $comment Comment ID
 *
 */
if (!function_exists('ual_shook_delete_comment')):

    function ual_shook_delete_comment($comment) {
        $action = "delete comment";
        $obj_type = "comment";
        $comment_id = $comment;
        $com = get_comment($comment_id);
        $post_id = $com->comment_post_ID;
        $post_title = get_the_title($post_id);
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Create Terms
 *
 * @param int $term Post ID
 * @param string $taxonomy taxonomy name
 *
 */
if (!function_exists('ual_shook_created_term')):

    function ual_shook_created_term($term, $taxonomy) {

        if ('nav_menu' === $taxonomy)
            return $term;
        global $wpdb;
        $post_id = '';
        $action = "created term";
        $obj_type = "term";
        $taxonomy_details = get_taxonomy($taxonomy);
        $post_title = $taxonomy_details->label . " - " . $term;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
        return $term;
    }

endif;

/*
 * Get activity for the user - Edit Terms
 *
 * @param int $term Post ID
 * @param string $taxonomy taxonomy name
 *
 */
if (!function_exists('ual_shook_edited_term')):

    function ual_shook_edited_term($term, $ttid, $taxonomy) {
        $action = "Updated term";
        $obj_type = "term";
        if ('nav_menu' === $taxonomy)
            return;
        global $wpdb;
        $post_id = $term;
        $termName = get_term_by("id", $term, $taxonomy);
        $taxonomy_details = get_taxonomy($taxonomy);
        $post_title = $taxonomy_details->label . " - " . $termName->name;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Delete Terms
 *
 * @param int $term Term ID
 * @param string $taxonomy taxonomy name
 *
 */
if (!function_exists('ual_shook_delete_term')):

    function ual_shook_delete_term($term, $taxonomy) {
        if ('nav_menu' === $taxonomy)
            return;

        if ($taxonomy && !is_wp_error($taxonomy)) {
            $action = 'delete term';
            $obj_type = 'Term';
            $termName = get_term_by("id", $term, $taxonomy);
            $taxonomy_details = get_taxonomy($taxonomy);
            $post_title = $taxonomy_details->label . " - " . $termName->name;
            ual_get_activity_function($action, $obj_type, $term, $post_title);
        }
    }

endif;

/*
 * Get activity for the user - Update navigation menu
 *
 * @param int $menu Post ID
 *
 */
if (!function_exists('ual_shook_wp_update_nav_menu')):

    function ual_shook_wp_update_nav_menu($menu) {
        $action = "update nav menu";
        $obj_type = "menu";
        $post_id = $menu;
        $menu_object = wp_get_nav_menu_object($post_id);
        $post_title = $menu_object->name;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Create navigation menu
 *
 * @param int $menu Post ID
 *
 */
if (!function_exists('ual_shook_wp_create_nav_menu')):

    function ual_shook_wp_create_nav_menu($menu) {
        $action = "create nav menu";
        $obj_type = "menu";
        $post_id = $menu;
        $menu_object = wp_get_nav_menu_object($post_id);
        $post_title = $menu_object->name;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Delete navigation menu
 *
 * @param int $tt_id Post ID
 * @param string $deleted_term Post Title
 *
 */
if (!function_exists('ual_shook_delete_nav_menu')):

    function ual_shook_delete_nav_menu($tt_id, $deleted_term) {
        $action = "delete nav menu";
        $obj_type = "menu";
        $post_id = $tt_id;
        $post_title = $deleted_term->name;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Switch Theme
 *
 * @param string $theme Post Title
 *
 */
if (!function_exists('ual_shook_switch_theme')):

    function ual_shook_switch_theme($theme) {
        $action = "switch theme";
        $obj_type = "theme";
        $post_id = "";
        $post_title = $theme;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Update Theme
 *
 */
if (!function_exists('shook_delete_site_transient_update_themes')):

    function shook_delete_site_transient_update_themes() {
        $action = "delete_site_transient_update_themes";
        $obj_type = "theme";
        $post_id = "";
        $post_title = $theme;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Customize Theme
 *
 */
if (!function_exists('ual_shook_customize_save')):

    function ual_shook_customize_save() {
        $action = "customize save";
        $obj_type = "theme";
        $post_id = "";
        $post_title = "Theme Customizer";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Activate Plugin
 *
 * @param string $plugin Post Title
 *
 */
if (!function_exists('ual_shook_activated_plugin')):

    function ual_shook_activated_plugin($plugin) {

        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, true, false);
        $post_title = $plugin_data['Name'];
        $action = "activated plugin";
        $obj_type = "plugin";
        $post_id = "";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Activate Plugin
 *
 * @param string $new_status new posts status
 * @param string $old_status old posts status
 * @param object $post posts
 *
 */
if (!function_exists('ual_shook_transition_post_status')):

    function ual_shook_transition_post_status($post_id, $post) {

        $action = '';
        $obj_type = $post->post_type;
        $post_id = $post->ID;
        $post_title = $post->post_title;
        $newstatus = $post->post_status;

        if ("nav_menu_item" == get_post_type($post) || "wpcf7_contact_form" == get_post_type($post) || wp_is_post_revision($post) || $obj_type == 'customize_changeset') {
            return;
        }

        global $old_post_data;

        $old_post_data_detail = $old_post_data['post_data'];
        if (isset($old_post_data_detail) && $old_post_data_detail != '') {
            $oldstatus = $old_post_data_detail->post_status;
        }

        $old_status = isset($oldstatus) ? $oldstatus : '';
        $new_status = isset($newstatus) ? $newstatus : '';

        if ( wp_is_post_revision( $post->ID ) )
            return;
        
        if ('auto-draft' === $new_status || ( 'new' === $old_status && 'inherit' === $new_status )) {
            return;
        } elseif ('auto-draft' === $old_status && $new_status === 'draft') {
            $action = $obj_type . ' drafted';
        } elseif ('draft' === $old_status && $new_status === 'publish' && $old_post_data['post_data']->post_date_gmt === '0000-00-00 00:00:00') {
            $action = $obj_type . ' created';
        } elseif ('trash' === $new_status) {
            $action = $obj_type . ' trashed';
        } elseif ('trash' === $old_status) {
            $action = $obj_type . ' restored';
        } else {
            $action = $obj_type . ' updated';
        }
        
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Deactivate Plugin
 *
 * @param string $plugin Post Title
 *
 */
if (!function_exists('ual_shook_deactivated_plugin')):

    function ual_shook_deactivated_plugin($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, true, false);
        $post_title = $plugin_data['Name'];
        $action = "deactivated plugin";
        $obj_type = "plugin";
        $post_id = "";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Core file updated successfully
 *
 */
if (!function_exists('shook_core_updated_successfully')):

    function shook_core_updated_successfully() {
        $action = "core updated successfully";
        $obj_type = "update";
        $post_id = "";
        $post_title = $obj_type;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Export wordpress data
 *
 */
if (!function_exists('ual_shook_export_wp')):

    function ual_shook_export_wp($args) {
        $action = "Export wp";
        $obj_type = "Export";
        $post_id = "";
        $post_title = $obj_type . ' : ' . isset($args['content']) ? $args['content'] : 'all';
        ;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Upgrader process complete
 *
 */
if (!function_exists('shook_upgrader_process_complete')):

    function shook_upgrader_process_complete() {
        $action = "upgrade process complete";
        $obj_type = "upgrade";
        $post_id = "";
        $post_title = $obj_type;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

/*
 * Get activity for the user - Delete theme
 *
 */
if (!function_exists('ual_shook_theme_deleted')):

    function ual_shook_theme_deleted() {
        $backtrace_history = debug_backtrace();
        $delete_theme_call = null;
        foreach ($backtrace_history as $call) {
            if (isset($call['function']) && 'delete_theme' === $call['function']) {
                $delete_theme_call = $call;
                break;
            }
        }
        if (empty($delete_theme_call))
            return;
        $name = $delete_theme_call['args'][0];
        $action = 'Theme deleted';
        $obj_type = 'Theme';
        $post_title = $name;
        $post_id = "";
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
    }

endif;

add_action('wp_login', 'ual_shook_wp_login', 20, 2);
add_action('wp_logout', 'ual_shook_wp_logout');
add_action('delete_user', 'ual_shook_delete_user');
add_action('user_register', 'ual_shook_user_register');
add_action('profile_update', 'ual_shook_profile_update');
add_action('add_attachment', 'ual_shook_add_attachment');
add_action('edit_attachment', 'ual_shook_edit_attachment');
add_action('delete_attachment', 'ual_shook_delete_attachment');
add_action('wp_insert_comment', 'ual_shook_wp_insert_comment');
add_action('edit_comment', 'ual_shook_edit_comment');
add_action('trash_comment', 'ual_shook_trash_comment');
add_action('spam_comment', 'ual_shook_spam_comment');
add_action('unspam_comment', 'ual_shook_unspam_comment');
add_action('delete_comment', 'ual_shook_delete_comment');
add_action('wp_update_nav_menu', 'ual_shook_wp_update_nav_menu');
add_action('wp_create_nav_menu', 'ual_shook_wp_create_nav_menu');
add_action('delete_nav_menu', 'ual_shook_delete_nav_menu', 10, 2);
add_action('activated_plugin', 'ual_shook_activated_plugin');
add_action('deactivated_plugin', 'ual_shook_deactivated_plugin');
//add_action('created_term', 'ual_shook_created_term', 10, 2);
add_filter('pre_insert_term', 'ual_shook_created_term', 10, 2);
add_action('edited_term', 'ual_shook_edited_term', 10, 3);
add_action('pre_delete_term', 'ual_shook_delete_term', 10, 2);
add_action('switch_theme', 'ual_shook_switch_theme');
add_action('customize_save', 'ual_shook_customize_save');
add_action('export_wp', 'ual_shook_export_wp', 1);
add_action('save_post', 'ual_shook_transition_post_status', 100, 2);
add_action('delete_site_transient_update_themes', 'ual_shook_theme_deleted');

/*
 * Get activity for the user - Login fail
 *
 * @param string $user username
 */
if (!function_exists('ual_shook_wp_login_failed')):

    function ual_shook_wp_login_failed($user) {
        $current_user_id = $post_id = $user_mail = $current_user_display_name = $user_role = "";
        $action = "login failed";
        $obj_type = "user";
        $post_title = $user;
        $modified_date = current_time('mysql');
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_detail = get_user_by('login', $user);
        if (isset($user_detail) && $user_detail != '') {
            $current_user_id = $user_detail->ID;
            $user_mail = $user_detail->user_email;
            $current_user_display_name = $user_detail->display_name;
            if (!empty($user_detail->roles) && is_array($user_detail->roles)) {
                foreach ($user_detail->roles as $role)
                    $user_role = $role;
            }
        }
        ual_user_activity_add($post_id, $post_title, $obj_type, $current_user_id, $current_user_display_name, $user_role, $user_mail, $modified_date, $ip, $action);
    }

endif;
add_action('wp_login_failed', 'ual_shook_wp_login_failed');

/*
 * Get activity for the user - Widget update
 *
 * @param string $widget widget data
 */
if (!function_exists('ual_shook_widget_update_callback')):

    function ual_shook_widget_update_callback($instance, $new_instance, $old_instance, $widget_instance) {

        if (empty($old_instance)) {
            return $instance;
        }
        $action = "widget updated";
        $obj_type = "widget";
        $post_id = $sidebar = $sidebar_name = '';
        $sidebar_id = $_POST["sidebar"];
        $sidebars = isset($GLOBALS['wp_registered_sidebars']) ? $GLOBALS['wp_registered_sidebars'] : false;

        if ($sidebars) {
            if (isset($sidebars[$sidebar_id])) {
                $sidebar = $sidebars[$sidebar_id];
            }
            $sidebar_name = $sidebar["name"];
        }
        $post_title = $sidebar_name . ' : ' . $widget_instance->name;
        ual_get_activity_function($action, $obj_type, $post_id, $post_title);
        return $instance;
    }

endif;
add_filter('widget_update_callback', 'ual_shook_widget_update_callback', 8, 4);


/*
 * Get activity for the user - Widget Add Delete
 */
if (!function_exists('ual_shook_widget_added_deleted')) {

    function ual_shook_widget_added_deleted() {
        if ((isset($_POST["add_new"]) && !empty($_POST["add_new"]) && isset($_POST["sidebar"]) && isset($_POST["id_base"])) || isset($_POST["delete_widget"])) {

            $sidebar = $widget = $post_id = $post_title = '';
            $obj_type = "Widget";
            $sidebar_id = $_POST["sidebar"];
            $widget_id_base = $_POST["id_base"];
            $sidebars = isset($GLOBALS['wp_registered_sidebars']) ? $GLOBALS['wp_registered_sidebars'] : false;
            $widget_factory = isset($GLOBALS["wp_widget_factory"]) ? $GLOBALS["wp_widget_factory"] : false;

            if ($widget_factory) {
                foreach ($widget_factory->widgets as $one_widget) {
                    if ($one_widget->id_base == $widget_id_base) {
                        $widget = $one_widget;
                    }
                }
            }
            if ($sidebars) {
                if (isset($sidebars[$sidebar_id])) {
                    $sidebar = $sidebars[$sidebar_id];
                }
                $sidebar_name = $sidebar["name"];
            }
            if ($widget) {
                $post_title = $widget->name;
            }
            if (isset($_POST["delete_widget"])) {
                $action = "Widget deleted";
            } else {
                $action = "Widget added";
            }
            $post_title = $sidebar_name . ' : ' . $post_title;
            ual_get_activity_function($action, $obj_type, $post_id, $post_title);
        }
    }

}
add_action("sidebar_admin_setup", 'ual_shook_widget_added_deleted');


/*
 * Input validation function
 *
 * @param string $data input data
 */
if (!function_exists('ual_test_input')) {

    function ual_test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

}

if (!function_exists('admin_notice_message')) {

    /**
     * Display success or error message
     *
     * @param string $class
     * @param string $message
     * @return string message with HTML
     */
    function admin_notice_message($class, $message) {
        ?>
        <div class="<?php _e($class, 'user-activity-log'); ?> is-dismissible notice settings-error">
            <p><?php _e($message, 'user-activity-log'); ?></p>
        </div>
        <?php
    }

}

/**
 *
 * @param $actions for take a action for redirection setting
 * @param $plugin_file give path of plugin file
 * @return action for setting link
 */
if (!function_exists('ual_settings_link')) {

    function ual_settings_link($actions, $plugin_file) {
        static $plugin;
        if (empty($plugin))
            $plugin = dirname(plugin_basename(__FILE__)) . '/user_activity_log.php';
        if ($plugin_file == $plugin) {
            $settings_link = '<a href="' . admin_url('admin.php?page=general_settings_menu') . '">' . __('Settings', 'user-activity-log') . '</a>';
            array_unshift($actions, $settings_link);
        }
        return $actions;
    }

}
add_filter('plugin_action_links', 'ual_settings_link', 10, 2);

if (!function_exists('ual_plugin_upgrade_notice_screen')) {

    function ual_plugin_upgrade_notice_screen() {
        $screen = get_current_screen();
        if (isset($_GET['page']) && ($_GET['page'] == 'user_action_log' || $_GET['page'] == 'general_settings_menu')) {
            add_action('admin_notices', 'ual_plugin_upgrade_notice');
        }
    }

}
add_action('current_screen', 'ual_plugin_upgrade_notice_screen');

/*
 * add notice at admin side
 * @global object $current_user
 */
if (!function_exists('ual_plugin_upgrade_notice')) {

    function ual_plugin_upgrade_notice() {
        global $current_user;
        $user_id = $current_user->ID;
        /* Check that the user hasn't already clicked to ignore the message */
        if (!get_user_meta($user_id, 'ual_plugin_upgrade_notice') && current_user_can('manage_options')) {
            ?>
            <div class="updated notice is-dismissible"><?php
                $genre_url = add_query_arg('ual_plugin_upgrade_notice', 0, get_permalink());
                ?>
                <p><?php _e('User Activity Log Plugin', 'user-activity-log'); ?> : 
                    <a href="https://www.solwininfotech.com/documents/wordpress/user-activity-log-lite/" target="_blank" style="text-decoration: underline">
                        <strong><?php _e('Live Documentation', 'user-activity-log'); ?></strong>
                    </a>
                </p>
                <p>
                    <?php _e('Want more user activity log features?', 'user-activity-log'); ?>
                    <a href="https://codecanyon.net/item/user-activity-log-pro-for-wordpress/18201203?ref=solwin" target="_blank" style="text-decoration: underline">
                        <strong><?php _e('Upgrade to PRO', 'user-activity-log'); ?></strong>
                    </a>
                </p>
                <p>
                    <a href="http://useractivitylog.solwininfotech.com/" target="_blank"><strong><?php _e('Live Preview', 'user-activity-log'); ?></strong></a> |
                    <a href="<?php echo $genre_url; ?>"><strong><?php _e('Dismiss This Notice', 'user-activity-log'); ?></strong></a>
                </p>
            </div>
            <?php
        }
    }

}

/**
 * add user meta for ignore notice
 * @global object $current_user
 */
if (!function_exists('ual_ignore_upgrade_notice')) {

    function ual_ignore_upgrade_notice() {
        global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if (isset($_GET['ual_plugin_upgrade_notice']) && '0' == $_GET['ual_plugin_upgrade_notice']) {
            add_user_meta($user_id, 'ual_plugin_upgrade_notice', 'true', true);
        }
    }

}
add_action('admin_init', 'ual_ignore_upgrade_notice');

add_action('init', 'ual_filter_user_role');
/**
 * Filter user Roles
 *
 */
if (!function_exists('ual_filter_user_role')):

    function ual_filter_user_role() {
        $paged = 1;
        $admin_url = admin_url('admin.php?page=general_settings_menu');
        $display = '';
        $search = '';
        if (isset($_POST['user_role'])) {
            $display = $_POST['user_role'];
        }
        if (isset($_POST['btn_filter_user_role'])) {
            $display = $_POST['user_role'];
            $header_uri = $admin_url . "&paged=$paged&display=$display&txtsearch=$search";
            header("Location: " . $header_uri, true);
            exit();
        }
        if (isset($_POST['btnSearch_user_role'])) {
            $search = ual_test_input($_POST['txtSearchinput']);
            $header_uri = $admin_url . "&paged=$paged&display=$display&txtsearch=$search";
            header("Location: " . $header_uri, true);
            exit();
        }
    }

endif;

/**
 * user cancel subscribe
 */
if (!function_exists('wp_ajax_close_tab')) {

    function wp_ajax_close_tab() {
        update_option('is_user_subscribed_cancled', 'yes');
        exit();
    }

}
add_action('wp_ajax_close_tab', 'wp_ajax_close_tab');

/**
 * admin scripts
 */
if (!function_exists('ual_admin_scripts')) {

    function ual_admin_scripts() {
        $screen = get_current_screen();
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/user-activity-log/user_activity_log.php', $markup = true, $translate = true);
        $current_version = $plugin_data['Version'];
        $old_version = get_option('ual_version');
        if ($old_version != $current_version) {
            update_option('is_user_subscribed_cancled', '');
            update_option('ual_version', $current_version);
        }

        if (get_option('is_user_subscribed') != 'yes' && get_option('is_user_subscribed_cancled') != 'yes') {
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
        }

        wp_register_style('ual-style-css', plugins_url('css/style.css', __FILE__));
        wp_enqueue_style('ual-style-css');
        wp_enqueue_style('wp-jquery-ui-dialog');

        wp_register_script('custom_wp_admin_js', plugins_url('js/admin_script.js', __FILE__), array('jquery-ui-dialog'));
        wp_enqueue_script('custom_wp_admin_js');
    }

}
add_action('admin_enqueue_scripts', 'ual_admin_scripts');

/**
 * start session if not
 */
if (!function_exists('ual_session_start')) {

    function ual_session_start() {
        if (session_id() == '') {
            session_start();
        }
    }

}
add_action('init', 'ual_session_start');

/**
 * subscribe email form
 */
if (!function_exists('ual_subscribe_mail')) {

    function ual_subscribe_mail() {
        $customer_email = get_option('admin_email');
        $current_user = wp_get_current_user();
        $f_name = $current_user->user_firstname;
        $l_name = $current_user->user_lastname;
        if (isset($_POST['sbtEmail'])) {
            $_SESSION['success_msg'] = 'Thank you for your subscription.';
            //Email To Admin
            update_option('is_user_subscribed', 'yes');
            $customer_email = trim($_POST['txtEmail']);
            $customer_name = trim($_POST['txtName']);
            $to = 'plugins@solwininfotech.com';
            $from = get_option('admin_email');
            $headers = "MIME-Version: 1.0;\r\n";
            $headers .= "From: " . strip_tags($from) . "\r\n";
            $headers .= "Content-Type: text/html; charset: utf-8;\r\n";
            $headers .= "X-Priority: 3\r\n";
            $headers .= "X-Mailer: PHP" . phpversion() . "\r\n";
            $subject = __('New user subscribed from Plugin - User Activity Log', 'user-activity-log');

            $body = '';

            ob_start();
            ?>
            <div style="background: #F5F5F5; border-width: 1px; border-style: solid; padding-bottom: 20px; margin: 0px auto; width: 750px; height: auto; border-radius: 3px 3px 3px 3px; border-color: #5C5C5C;">
                <div style="border: #FFF 1px solid; background-color: #ffffff !important; margin: 20px 20px 0;
                     height: auto; -moz-border-radius: 3px; padding-top: 15px;">
                    <div style="padding: 20px 20px 20px 20px; font-family: Arial, Helvetica, sans-serif;
                         height: auto; color: #333333; font-size: 13px;">
                        <div style="width: 100%;">
                            <strong>Dear Admin (User Activity Log plugin developer)</strong>,
                            <br />
                            <br />
                            Thank you for developing useful plugin.
                            <br />
                            <br />
                            I <?php echo $customer_name; ?> want to notify you that I have installed plugin on my <a href="<?php echo home_url(); ?>">website</a>. Also I want to subscribe to your newsletter, and I do allow you to enroll me to your free newsletter subscription to get update with new products, news, offers and updates.
                            <br />
                            <br />
                            I hope this will motivate you to develop more good plugins and expecting good support form your side.
                            <br />
                            <br />
                            Following is details for newsletter subscription.
                            <br />
                            <br />
                            <div>
                                <table border='0' cellpadding='5' cellspacing='0' style="font-family: Arial, Helvetica, sans-serif; font-size: 13px;color: #333333;width: 100%;">
                                    <?php if ($customer_name != '') {
                                        ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <th style="padding: 8px 5px; text-align: left;width: 120px;">
                                                Name<span style="float:right">:</span>
                                            </th>
                                            <td style="padding: 8px 5px;">
                                                <?php echo $customer_name; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    } else {
                                        ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <th style="padding: 8px 5px; text-align: left;width: 120px;">
                                                Name<span style="float:right">:</span>
                                            </th>
                                            <td style="padding: 8px 5px;">
                                                <?php echo home_url(); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <th style="padding: 8px 5px; text-align: left;width: 120px;">
                                            Email<span style="float:right">:</span>
                                        </th>
                                        <td style="padding: 8px 5px;">
                                            <?php echo $customer_email; ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <th style="padding: 8px 5px; text-align: left;width: 120px;">
                                            Website<span style="float:right">:</span>
                                        </th>
                                        <td style="padding: 8px 5px;">
                                            <?php echo home_url(); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <th style="padding: 8px 5px; text-align: left; width: 120px;">
                                            Date<span style="float:right">:</span>
                                        </th>
                                        <td style="padding: 8px 5px;">
                                            <?php echo date('d-M-Y  h:i  A'); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <th style="padding: 8px 5px; text-align: left; width: 120px;">
                                            Plugin<span style="float:right">:</span>
                                        </th>
                                        <td style="padding: 8px 5px;">
                                            <?php echo 'User Activity Log'; ?>
                                        </td>
                                    </tr>
                                </table>
                                <br /><br />
                                Again Thanks you
                                <br />
                                <br />
                                Regards
                                <br />
                                <?php echo $customer_name; ?>
                                <br />
                                <?php echo home_url(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $body = ob_get_clean();
            wp_mail($to, $subject, $body, $headers);
        }
        if (get_option('is_user_subscribed') != 'yes' && get_option('is_user_subscribed_cancled') != 'yes') {
            ?>
            <div id="subscribe_widget_ual" style="display:none;">
                <div class="subscribe_widget">
                    <h3>Notify to plugin developer and subscribe.</h3>
                    <form class='sub_form' name="frmSubscribe" method="post" action="<?php echo admin_url('admin.php').'?page=general_settings_menu'; ?>">
                        <div class="sub_row"><label>Your Name: </label><input placeholder="Your Name" name="txtName" type="text" value="<?php echo $f_name . ' ' . $l_name; ?>" /></div>
                        <div class="sub_row"><label>Email Address: </label><input placeholder="Email Address" required name="txtEmail" type="email" value="<?php echo $customer_email; ?>" /></div>
                        <input class="button button-primary" type="submit" name="sbtEmail" value="Notify & Subscribe" />
                    </form>
                </div>
            </div>
            <?php
        }
        if (isset($_GET['page'])) {
            if (get_option('is_user_subscribed') != 'yes' && get_option('is_user_subscribed_cancled') != 'yes' && ($_GET['page'] == 'general_settings_menu' || $_GET['page'] == 'user_action_log' || $_GET['page'] == 'user_settings_menu' || $_GET['page'] == 'email_settings_menu')) {
                ?>
                <a style="display:none" href="#TB_inline?width=400&height=210&inlineId=subscribe_widget_ual" class="thickbox" id="subscribe_thickbox"></a>
                <?php
            }
        }
        ?>
        <div id="sol_deactivation_widget_cover_ual" style="display:none;">
            <div class="sol_deactivation_widget">
                <h3><?php _e('If you have a moment, please let us know why you are deactivating.', 'user-activity-log'); ?></h3>
                <form id="frmDeactivationual" name="frmDeactivation" method="post" action="">
                    <ul class="sol_deactivation_reasons_ul">
                        <?php $i = 1; ?>                        
                        <li>
                            <input class="sol_deactivation_reasons" checked name="sol_deactivation_reasons_ual" type="radio" value="<?php echo $i; ?>" id="ual_reason_<?php echo $i; ?>">
                            <label for="ual_reason_<?php echo $i; ?>"><?php _e('I am going to upgrade to PRO version', 'user-activity-log'); ?></label>
                        </li>
                        <?php $i++; ?>
                        <li>
                            <input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ual" type="radio" value="<?php echo $i; ?>" id="ual_reason_<?php echo $i; ?>">
                            <label for="ual_reason_<?php echo $i; ?>"><?php _e('The plugin suddenly stopped working', 'user-activity-log'); ?></label>
                        </li>
                        <?php $i++; ?>
                        <li>
                            <input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ual" type="radio" value="<?php echo $i; ?>" id="ual_reason_<?php echo $i; ?>">
                            <label for="ual_reason_<?php echo $i; ?>"><?php _e('The plugin was not working', 'user-activity-log'); ?></label>
                        </li>
                        <?php $i++; ?>
                        <li>
                            <input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ual" type="radio" value="<?php echo $i; ?>" id="ual_reason_<?php echo $i; ?>">
                            <label for="ual_reason_<?php echo $i; ?>"><?php _e("Found other better plugin than this plugin", 'user-activity-log'); ?></label>
                        </li>
                        <?php $i++; ?>
                        <li>
                            <input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ual" type="radio" value="<?php echo $i; ?>" id="ual_reason_<?php echo $i; ?>">
                            <label for="ual_reason_<?php echo $i; ?>"><?php _e('The plugin broke my site completely', 'user-activity-log'); ?></label>
                        </li>
                        <?php $i++; ?>
                        <li>
                            <input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ual" type="radio" value="<?php echo $i; ?>" id="ual_reason_<?php echo $i; ?>">
                            <label for="ual_reason_<?php echo $i; ?>"><?php _e('No any reason', 'user-activity-log'); ?></label>
                        </li>
                        <?php $i++; ?>
                        <li>
                            <input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ual" type="radio" value="<?php echo $i; ?>" id="ual_reason_<?php echo $i; ?>">
                            <label for="ual_reason_<?php echo $i; ?>"><?php _e('Other', 'user-activity-log'); ?></label><br/>
                            <input style="display:none;width: 90%" value="" type="text" name="sol_deactivation_reason_other_ual" class="sol_deactivation_reason_other_ual" />
                        </li>
                    </ul>
                    <input type="submit" name="sbtDeactivationForm" id="sbtDeactivationFormual" class="button button-secondary" value="submit & deactivate" />
                    <input type="submit" name="sbtDeactivationFormClose" id="sbtDeactivationFormCloseual" class="button button-primary" value="cancel" />
                </form>
            </div>
        </div>
        <a style="display:none" href="#TB_inline?height=500&inlineId=sol_deactivation_widget_cover_ual" class="thickbox" id="deactivation_thickbox_ual"></a>
        <?php
    }

}
add_action('admin_head', 'ual_subscribe_mail', 11);


/**
 * Get rating star and total downloads of current plugin
 */
$wp_version = get_bloginfo('version');
if ($wp_version > 3.8) {
    if (!function_exists('wp_custom_star_rating_user_activity_log')) {

        function wp_custom_star_rating_user_activity_log($args = array()) {
            $plugins = $response = "";
            $args = array(
                'author' => 'solwininfotech',
                'fields' => array(
                    'downloaded' => true,
                    'downloadlink' => true
                )
            );
            // Make request and extract plug-in object. Action is query_plugins
            $response = wp_remote_post(
                    'http://api.wordpress.org/plugins/info/1.0/', array(
                'body' => array(
                    'action' => 'query_plugins',
                    'request' => serialize((object) $args)
                )
                    )
            );
            if (!is_wp_error($response)) {
                $returned_object = unserialize(wp_remote_retrieve_body($response));
                $plugins = $returned_object->plugins;
            }
            $current_slug = 'user-activity-log';
            if ($plugins) {
                foreach ($plugins as $plugin) {
                    if ($current_slug == $plugin->slug) {
                        $rating = $plugin->rating * 5 / 100;
                        if ($rating > 0) {
                            $args = array(
                                'rating' => $rating,
                                'type' => 'rating',
                                'number' => $plugin->num_ratings,
                            );
                            wp_star_rating($args);
                        }
                    }
                }
            }
        }

    }
}


/**
 * Get total downloads of current plugin
 */
if (!function_exists('get_total_downloads_user_activity_log_plugin')) {

    function get_total_downloads_user_activity_log_plugin() {
        // Set the arguments. For brevity of code, I will set only a few fields.
        $plugins = $response = "";
        $args = array(
            'author' => 'solwininfotech',
            'fields' => array(
                'downloaded' => true,
                'downloadlink' => true
            )
        );
        // Make request and extract plug-in object. Action is query_plugins
        $response = wp_remote_post(
                'http://api.wordpress.org/plugins/info/1.0/', array(
            'body' => array(
                'action' => 'query_plugins',
                'request' => serialize((object) $args)
            )
                )
        );
        if (!is_wp_error($response)) {
            $returned_object = unserialize(wp_remote_retrieve_body($response));
            $plugins = $returned_object->plugins;
        } else {
            
        }
        $current_slug = 'user-activity-log';
        if ($plugins) {
            foreach ($plugins as $plugin) {
                if ($current_slug == $plugin->slug) {
                    if ($plugin->downloaded) {
                        ?>
                        <span class="total-downloads">
                            <span class="download-number"><?php echo $plugin->downloaded; ?></span>
                        </span>
                        <?php
                    }
                }
            }
        }
    }

}

add_action('user_register', 'ual_enable_user_notification_at_login');
/**
 * Enable user notification of email at login
 *
 * @param int $user_id user ID
 */
if (!function_exists('ual_enable_user_notification_at_login')) {

    function ual_enable_user_notification_at_login($user_id) {
        $user_info = get_userdata($user_id);
        $user_role = $user_info->roles[0];
        $user_role_enable = get_option('enable_role_list');
        $user_enabled = get_option('enable_user_list');
        for ($i = 0; $i < count($user_role_enable); $i++) {
            if ($user_role_enable[$i] == $user_role) {
                array_push($user_enabled, $user_info->user_login);
                update_option('enable_user_list', $user_enabled);
            }
        }
    }

}

/**
 * Add Admin Dashboard Widget - News from Solwin Infotech
 */
add_action('plugins_loaded', 'ualpLatestActivityLogs');
if (!function_exists('ualpLatestActivityLogs')) {

    function ualpLatestActivityLogs() {
        // Register the new dashboard widget with the 'wp_dashboard_setup' action
        add_action('wp_dashboard_setup', 'ualpLatestLogs');
        if (!function_exists('ualpLatestLogs')) {

            function ualpLatestLogs() {
                add_screen_option('layout_columns', array('max' => 3, 'default' => 2));
                add_meta_box('ual_dashboard_widget', __('Latest User Activity Logs', 'user-activity-log'), 'ualDashboardWidget', 'dashboard', 'normal', 'high');
            }

        }
        if (!function_exists('ualDashboardWidget')) {

            function ualDashboardWidget() {
                ?>
                <ul class="ual_dashboard_log_table">
                    <li>
                        <span class="ual-dashboard-column-date"><b><?php _e('Date', 'user-activity-log'); ?></b></span>
                        <span class="ual-dashboard-column-author"><b><?php _e('Author', 'user-activity-log'); ?></b></span>
                        <span class="ual-dashboard-column-activity"><b><?php _e('Activity', 'user-activity-log'); ?></b></span>
                    </li>
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . "ualp_user_activity";
                    $select_query = "SELECT * from " . $table_name . " ORDER BY modified_date desc LIMIT 5";
                    $get_data = $wpdb->get_results($select_query);
                    if ($get_data) {
                        $srno = 1;
                        foreach ($get_data as $data) {
                            ?>
                            <li>
                                <span class="ual-dashboard-column-date"><?php
                                    $modified_date = strtotime($data->modified_date);
                                    $date_format = get_option('date_format');
                                    $time_format = get_option('time_format');
                                    $date = date($date_format, $modified_date);
                                    $time = date($time_format, $modified_date);
                                    echo $date . "<br/>" . $time;
                                    ?>
                                </span>
                                <span class="ual-dashboard-column-author column-author"><?php
                                    global $wp_roles;
                                    if (!empty($data->user_id) && 0 !== (int) $data->user_id) {
                                        $user = get_user_by('id', $data->user_id);
                                        if ($user instanceof WP_User && 0 !== $user->ID) {
                                            ?>
                                            <a href="<?php echo get_edit_user_link($data->user_id); ?>">
                                                <?php echo get_avatar($data->user_id, 40); ?>
                                                <span><?php echo ucfirst($data->user_name); ?></span>
                                            </a><br/>
                                            <small><?php echo ucfirst($data->user_role); ?></small><br/><?php
                                            echo $data->user_email;
                                        }
                                    }
                                    ?>
                                </span>
                                <span class="ual-dashboard-column-activity">
                                    <?php
                                    echo ucfirst($data->action);
                                    echo " : ";
                                    echo ucfirst($data->post_title);
                                    ?>
                                </span>
                            </li>
                            <?php
                        }
                    } else {
                        echo '<li>';
                        echo '<div>' . __('No Log found.', 'user-activity-log') . '</div>';
                        echo '</li>';
                    }
                    ?>

                </ul>
                <?php
            }

        }
    }

}

/* submit deactivation reason form */
if (!function_exists('ual_sbt_deactivation_form')) {

    function ual_sbt_deactivation_form() {
        //Email To Admin
        $to = 'pluginsd@solwininfotech.com';
        $from = get_option('admin_email');
        $reason_id = $_POST['deactivation_option'];
        $reason_text = $_POST['deactivation_option_text'];
        if ($reason_id == 7) {
            $reason_text = $_POST['deactivation_option_other'];
        }
        $headers = "MIME-Version: 1.0;\r\n";
        $headers .= "From: " . strip_tags($from) . "\r\n";
        $headers .= "Content-Type: text/html; charset: utf-8;\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP" . phpversion() . "\r\n";
        $subject = 'User has deactivated Plugin - User Activity Log';
        $body = '';
        ob_start();
        ?>
        <div style="background: #F5F5F5; border-width: 1px; border-style: solid; padding-bottom: 20px; margin: 0px auto; width: 750px; height: auto; border-radius: 3px 3px 3px 3px; border-color: #5C5C5C;">
            <div style="border: #FFF 1px solid; background-color: #ffffff !important; margin: 20px 20px 0;
                 height: auto; -moz-border-radius: 3px; padding-top: 15px;">
                <div style="padding: 20px 20px 20px 20px; font-family: Arial, Helvetica, sans-serif;
                     height: auto; color: #333333; font-size: 13px;">
                    <div style="width: 100%;">
                        <strong>Dear Admin (User Activity Log plugin developer)</strong>,
                        <br />
                        <br />
                        Thank you for developing useful plugin.
                        <br />
                        <br />
                        I have deactivated plugin because of following reason.
                        <br />
                        <br />
                        <div>
                            <table border='0' cellpadding='5' cellspacing='0' style="font-family: Arial, Helvetica, sans-serif; font-size: 13px;color: #333333;width: 100%;">
                                <tr style="border-bottom: 1px solid #eee;">
                                    <th style="padding: 8px 5px; text-align: left; width: 120px;">
                                        Reason ID<span style="float:right">:</span>
                                    </th>
                                    <td style="padding: 8px 5px;">
                                        <?php echo $reason_id; ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <th style="padding: 8px 5px; text-align: left; width: 120px;">
                                        Reason<span style="float:right">:</span>
                                    </th>
                                    <td style="padding: 8px 5px;">
                                        <?php echo $reason_text; ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <th style="padding: 8px 5px; text-align: left;width: 120px;">
                                        Website<span style="float:right">:</span>
                                    </th>
                                    <td style="padding: 8px 5px;">
                                        <?php echo home_url(); ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <th style="padding: 8px 5px; text-align: left;width: 120px;">
                                        Email<span style="float:right">:</span>
                                    </th>
                                    <td style="padding: 8px 5px;">
                                        <?php echo $from; ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <th style="padding: 8px 5px; text-align: left; width: 120px;">
                                        Date<span style="float:right">:</span>
                                    </th>
                                    <td style="padding: 8px 5px;">
                                        <?php echo date('d-M-Y  h:i  A'); ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <th style="padding: 8px 5px; text-align: left; width: 120px;">
                                        Plugin<span style="float:right">:</span>
                                    </th>
                                    <td style="padding: 8px 5px;">
                                        <?php echo 'User Activity Log'; ?>
                                    </td>
                                </tr>
                            </table>
                            <br /><br />
                            Again Thanks you
                            <br />
                            <br />
                            Regards
                            <br />
                            <?php echo home_url(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $body = ob_get_clean();
        echo $body;
        wp_mail($to, $subject, $body, $headers);
        exit();
    }

}