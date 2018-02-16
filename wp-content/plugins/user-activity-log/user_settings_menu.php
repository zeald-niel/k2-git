<?php
/*
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings panel
 */
if (!function_exists('ualSettingsPanel')) {

    function ualSettingsPanel() {
        ?>
        <div class="wrap">
            <h1><?php _e('Settings', 'user-activity-log'); ?></h1>
            <div class="tab_parent_parent ualParentTabs">
                <h2 class="nav-tab-wrapper nav-tab-wrapper">
                    <a class="nav-tab nav-tab-active ualGeneralSettings" data-href="ualGeneralSettings" href="javascript:void(0)" >
                        <?php _e('General Settings', 'user-activity-log'); ?>
                    </a>
                    <a class="nav-tab ualUserSettings" data-href="ualUserSettings" href="javascript:void(0)">
                        <?php _e('User Settings', 'user-activity-log'); ?>
                    </a>
                    <a class="nav-tab ualEmailSettings" data-href="ualEmailSettings" href="javascript:void(0)">
                        <?php _e('Email Notification', 'user-activity-log'); ?>
                    </a>
                    <a class="nav-tab ual-pro-feature" href="javascript:void(0)">
                        <?php _e('Hook Settings', 'user-activity-log'); ?>
                    </a>
                    <a class="nav-tab ual-pro-feature" href="javascript:void(0)">
                        <?php _e('Password Settings', 'user-activity-log'); ?>
                    </a>
                    <a class="nav-tab ual-pro-feature" href="javascript:void(0)">
                        <?php _e('Role Manager', 'user-activity-log'); ?>
                    </a>
                    <a class="nav-tab ual-pro-feature" href="javascript:void(0)">
                        <?php _e('Custom Event Settings', 'user-activity-log'); ?>
                    </a>
                </h2>
            </div>
            <div class="ualTabContentWrap">
                <div id="ualGeneralSettings" style="display: none" class="ualpContentDiv"><?php ual_general_settings(); ?></div>
                <div id="ualUserSettings" style="display: none" class="ualpContentDiv"><?php ual_user_activity_setting_function(); ?></div>
                <div id="ualEmailSettings" style="display: none" class="ualpContentDiv"><?php ual_email_settings(); ?></div>
            </div>
        </div>
        <?php
    }

}

/**
 * User activity Settings
 */
if (!function_exists('ual_user_activity_setting_function')):

    function ual_user_activity_setting_function() {
        global $wpdb;
        $class = '';
        $message = '';
        $paged = $total_pages = 1;
        $srno = 0;
        $active = $_GET['page'];
        $recordperpage = 10;
        $display = "roles";
        $search = "";
        if (isset($_GET['paged']))
            $paged = $_GET['paged'];
        $offset = ($paged - 1) * $recordperpage;
        $where = "where 1=1";
        if (isset($_GET['display'])) {
            $display = $_GET['display'];
        }
        if (isset($_GET['txtsearch'])) {
            $search = $_GET['txtsearch'];
            if ($search != "") {
                if ($display == "users")
                    $where.=" and user_login like '%$search%' or user_email like '%$search%' or display_name like '%$search%'";
            }
        }
        if (isset($_POST['saveLogin']) && isset($_POST['_wp_role_email_nonce']) && wp_verify_nonce($_POST['_wp_role_email_nonce'], '_wp_role_email_action')) {
            if ($display == "users") {
                add_option('enable_user_list');
                $enableuser = isset($_POST['usersID']) ? $_POST['usersID'] : "";
                update_option('enable_user_list', $enableuser);
            }
            if ($display == "roles") {
                $enablerole = isset($_POST['rolesID']) ? $_POST['rolesID'] : array();
                add_option('enable_role_list');
                $enable_user_login = array();
                for ($i = 0; $i < count($enablerole); $i++) {
                    $condition = "um.meta_key='" . $wpdb->prefix . "capabilities' and um.meta_value like '%" . $enablerole[$i] . "%' and u.ID = um.user_id";
                    if(function_exists('is_multisite') && is_multisite()){
                        $enable_list_user = "SELECT * FROM " . $wpdb->base_prefix . "usermeta as um, " . $wpdb->base_prefix . "users as u WHERE $condition";
                    } else {
                        $enable_list_user = "SELECT * FROM " . $wpdb->prefix . "usermeta as um, " . $wpdb->prefix . "users as u WHERE $condition";
                    }
                    
                    $get_user = $wpdb->get_results($enable_list_user);
                    foreach ($get_user as $k => $v) {
                        $enable_user_login[] = $v->user_login;
                    }
                }
                update_option('enable_role_list', $enablerole);
                update_option('enable_user_list', $enable_user_login);
            }
            $class = 'updated';
            $message = __("Settings saved successfully.", 'user-activity-log');
        }

        // query for display all the users data start
        $get_user_data = "";
        $get_data = "";
        if ($display == "users") {
            if(function_exists('is_multisite') && is_multisite()){
                $table_name = $wpdb->base_prefix . "users";
            } else {
                $table_name = $wpdb->prefix . "users";
            }
            $select_query = "SELECT * from $table_name $where LIMIT $offset,$recordperpage";
            $get_user_data = $wpdb->get_results($select_query);
            $total_items_query = "SELECT count(*) FROM $table_name $where";
            $total_items = $wpdb->get_var($total_items_query, 0, 0);
        } else {
            if(function_exists('is_multisite') && is_multisite()){
                $table_name = $wpdb->base_prefix. "usermeta as um";
            } else {
                $table_name = $wpdb->prefix . "usermeta as um";    
            }
            $where.=" and um.meta_key='" . $wpdb->prefix . "capabilities'";
            $select_query = "SELECT distinct um.meta_value from $table_name $where LIMIT $offset,$recordperpage";
            $get_data = $wpdb->get_results($select_query);
            $total_items_query = "SELECT count(distinct um.meta_value) FROM $table_name $where";
            $total_items = $wpdb->get_var($total_items_query, 0, 0);
        }

        // query for pagination
        $total_pages = ceil($total_items / $recordperpage);
        $next_page = (int) $paged + 1;
        if ($next_page > $total_pages)
            $next_page = $total_pages;
        $prev_page = (int) $paged - 1;
        if ($prev_page < 1)
            $prev_page = 1;
        ?>
        <div class="wrap">
            <?php
            if (!empty($class) && !empty($message)) {
                admin_notice_message($class, $message);
            }
            ?>
            <form class="sol-form" method="POST" action="<?php echo admin_url('admin.php').'?'. $_SERVER['QUERY_STRING']; ?>">
                <div class="sol-box-border">
                    <h3 class="sol-header-text"><?php _e('Select Users/Roles', 'user-activity-log'); ?></h3>
                    <p><?php _e('Email will be sent upon login of these selected users/roles.', 'user-activity-log'); ?></p>
                    <!-- Search Box start -->
                    <?php if ($display == 'users') {
                        ?>
                        <div class="sol-search-user-div">
                            <p class="search-box">
                                <label class="screen-reader-text" for="search-input"><?php _e('Search', 'user-activity-log'); ?> :</label>
                                <input id="user-search-input" class="sol-search-user" type="search" title="<?php _e('Search user by username,email,firstname and lastname', 'user-activity-log'); ?>" width="275px" placeholder="<?php _e('Username, Email, Firstname, Lastname', 'user-activity-log'); ?>" value="<?php echo $search; ?>" name="txtSearchinput">
                                <input id="search-submit" class="button" type="submit" value="<?php esc_attr_e('Search', 'user-activity-log'); ?>" name="btnSearch_user_role">
                            </p>
                        </div>
                    <?php }
                    ?>
                    <!-- Search Box end -->
                    <div class="tablenav top <?php if ($display == 'roles') echo 'sol-display-roles'; ?>">
                        <!-- Drop down menu for user and Role Start -->
                        <div class="alignleft actions sol-dropdown">
                            <select name="user_role">
                                <option selected value="roles"><?php _e('Role', 'user-activity-log'); ?></option>
                                <option <?php selected($display, 'users'); ?> value="users"><?php _e('User', 'user-activity-log'); ?></option>
                            </select>
                        </div>
                        <!-- Drop down menu for user and Role end -->
                        <input class="button-secondary action sol-filter-btn" type="submit" value="<?php _e('Filter', 'user-activity-log'); ?>" name="btn_filter_user_role">
                        <!-- top pagination start -->
                        <div class="tablenav-pages">
                            <?php $items = $total_items . ' ' . _n('item', 'items', $total_items, 'user-activity-log'); ?>
                            <span class="displaying-num"><?php echo $items; ?></span>
                            <div class="tablenav-pages" <?php
                            if ((int) $total_pages <= 1) {
                                echo 'style="display:none;"';
                            }
                            ?>>
                                <span class="pagination-links">
                                    <?php if ($paged == '1') { ?>
                                        <span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>
                                        <span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>
                                    <?php } else { ?>
                                        <a class="first-page <?php if ($paged == '1') echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=1&display=' . $display . '&txtsearch=' . $search; ?>" title="<?php _e('Go to the first page', 'user-activity-log'); ?>">&laquo;</a>
                                        <a class="prev-page <?php if ($paged == '1') echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=' . $prev_page . '&display=' . $display . '&txtsearch=' . $search; ?>" title="<?php _e('Go to the previous page', 'user-activity-log'); ?>">&lsaquo;</a>
                                    <?php } ?>
                                    <span class="paging-input">
                                        <input class="current-page" type="text" size="1" value="<?php echo $paged; ?>" name="paged" title="<?php _e('Current page', 'user-activity-log'); ?>"> <?php _e('of', 'user-activity-log'); ?>
                                        <span class="total-pages"><?php echo $total_pages; ?></span>
                                    </span>
                                    <a class="next-page <?php if ($paged == $total_pages) echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=' . $next_page . '&display=' . $display . '&txtsearch=' . $search; ?>" title="<?php _e('Go to the next page', 'user-activity-log'); ?>">&rsaquo;</a>
                                    <a class="last-page <?php if ($paged == $total_pages) echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=' . $total_pages . '&display=' . $display . '&txtsearch=' . $search; ?>" title="<?php _e('Go to the last page', 'user-activity-log'); ?>">&raquo;</a>
                                </span>
                            </div>
                        </div>
                        <!-- top pagination end -->
                    </div>
                    <!-- display users details start -->
                    <table class="widefat post fixed striped" cellspacing="0" style="
                    <?php
                    if ($display == "users") {
                        echo 'display:table';
                    }
                    if ($display == "roles") {
                        echo 'display:none';
                    }
                    ?>">
                        <thead>
                            <tr>
                                <th scope="col" class="check-column"><input type="checkbox" /></th>
                                <th width="50px" scope="col"><?php _e('No.', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('User', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('First name', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('Last name', 'user-activity-log'); ?></th>
                                <th scope="col" class="role-width"><?php _e('Role', 'user-activity-log'); ?></th>
                                <th scope="col" class="email-id-width"><?php _e('Email address', 'user-activity-log'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th scope="col" class="check-column"><input type="checkbox" /></th>
                                <th width="50px" scope="col"><?php _e('No.', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('User', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('First name', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('Last name', 'user-activity-log'); ?></th>
                                <th scope="col" class="role-width"><?php _e('Role', 'user-activity-log'); ?></th>
                                <th scope="col" class="email-id-width"><?php _e('Email address', 'user-activity-log'); ?></th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php
                            if ($get_user_data) {
                                $srno = 1 + $offset;
                                foreach ($get_user_data as $data) {
                                    $u_d = get_userdata($data->ID);
                                    $first_name = $u_d->user_firstname;
                                    $last_name = $u_d->user_lastname;
                                    ?>
                                    <tr>
                                        <?php
                                        $user_enable = get_option('enable_user_list');
                                        $checked = '';
                                        if ($user_enable != ""):
                                            if (in_array($data->user_login, $user_enable)) {
                                                $checked = "checked=checked";
                                            }
                                        endif;
                                        ?>
                                        <th scope="row" class="check-column"><input type="checkbox" <?php echo $checked; ?> name="usersID[]" value="<?php echo $data->user_login; ?>" /></th>
                                        <td><?php
                                            echo $srno;
                                            $srno++;
                                            ?>
                                        </td>
                                        <td><?php echo ucfirst($data->user_login); ?></td>
                                        <td><?php echo ucfirst($first_name); ?></td>
                                        <td><?php echo ucfirst($last_name); ?></td>
                                        <td><?php
                                            global $wp_roles;
                                            $role_name = array();
                                            $user = new WP_User($data->ID);
                                            if (!empty($user->roles) && is_array($user->roles)) {
                                                foreach ($user->roles as $user_r) {
                                                    $role_name[] = $wp_roles->role_names[$user_r];
                                                }
                                                $role_name = implode(', ', $role_name);
                                                echo $role_name;
                                            }
                                            ?></td>
                                        <td class="email-id-width"><?php echo $data->user_email; ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr class="no-items">';
                                echo '<td class="colspanchange" colspan="4">' . __('No record found.', 'user-activity-log') . '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <!-- display users details end -->
                    <!-- display roles details start -->
                    <table class="widefat post fixed sol-display-roles striped" cellspacing="0" style="
                    <?php
                    if ($display == "users") {
                        echo 'display:none';
                    }
                    if ($display == "roles") {
                        echo 'display:table';
                    }
                    ?>">
                        <thead>
                            <tr>
                                <th scope="col" class="check-column"><input type="checkbox" /></th>
                                <th scope="col"><?php _e('No.', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('Role', 'user-activity-log'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th scope="col" class="check-column"><input type="checkbox" /></th>
                                <th scope="col"><?php _e('No.', 'user-activity-log'); ?></th>
                                <th scope="col"><?php _e('Role', 'user-activity-log'); ?></th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php
                            if ($get_data) {
                                $srno = 1 + $offset;
                                foreach ($get_data as $data) {
                                    $final_roles = unserialize($data->meta_value);
                                    $final_roles = key($final_roles);
                                    ?>
                                    <tr>
                                        <?php
                                        $role_enable = get_option('enable_role_list');
                                        $checked = '';
                                        if ($role_enable != ""):
                                            if (in_array($final_roles, $role_enable)) {
                                                $checked = "checked=checked";
                                            }
                                        endif;
                                        ?>
                                        <th scope="row" class="check-column">
                                            <input type="checkbox" <?php echo $checked; ?> name="rolesID[]" value="<?php echo $final_roles; ?>" />
                                        </th>
                                        <td><?php
                                            echo $srno;
                                            $srno++;
                                            ?></td>
                                        <td><?php echo ucfirst($final_roles); ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr class="no-items">';
                                echo '<td class="colspanchange" colspan="3">' . __('No record found.', 'user-activity-log') . '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <!-- display roles details end -->
                    <!-- bottom pagination start -->
                    <div class="tablenav top <?php if ($display == 'roles') echo 'sol-display-roles'; ?>">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $items; ?></span>
                            <div class="tablenav-pages" <?php
                            if ((int) $total_pages <= 1) {
                                echo 'style="display:none;"';
                            }
                            ?>>
                                <span class="pagination-links">
                                    <?php if ($paged == '1') { ?>
                                        <span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>
                                        <span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>
                                    <?php } else { ?>
                                        <a class="first-page <?php if ($paged == '1') echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=1&display=' . $display . '&txtsearch=' . $search; ?>" title="Go to the first page">&laquo;</a>
                                        <a class="prev-page <?php if ($paged == '1') echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=' . $prev_page . '&display=' . $display . '&txtsearch=' . $search; ?>" title="Go to the previous page">&lsaquo;</a>
                                    <?php } ?>
                                    <span class="paging-input">
                                        <span class="current-page" title="Current page"><?php echo $paged; ?></span>
                                        <span class="total-pages"><?php echo $total_pages; ?></span>
                                    </span>
                                    <a class="next-page <?php if ($paged == $total_pages) echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=' . $next_page . '&display=' . $display . '&txtsearch=' . $search; ?>" title="Go to the next page">&rsaquo;</a>
                                    <a class="last-page <?php if ($paged == $total_pages) echo 'disabled'; ?>" href="<?php echo admin_url('admin.php?page=general_settings_menu').'&paged=' . $total_pages . '&display=' . $display . '&txtsearch=' . $search; ?>" title="Go to the last page">&raquo;</a>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- bottom pagination end -->
                    <?php
                    wp_nonce_field('_wp_role_email_action', '_wp_role_email_nonce');
                    ?>
                    <p class="submit">
                        <input id="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save Changes', 'user-activity-log'); ?>" name="saveLogin">
                    </p>
                </div>
            </form>
            <?php ual_advertisment_sidebar(); ?>
        </div>
        <?php
    }

endif;

/**
 * Email settings
 */
if (!function_exists('ual_email_settings')):

    function ual_email_settings() {
        $class = '';
        $active = $_GET['page'];
        $msg = "";
        add_option('enable_email');
        add_option('to_email');
        add_option('from_email');
        add_option('email_message');
        global $current_user;
        wp_get_current_user();
        $to_email = get_option('to_email') ? get_option('to_email') : $current_user->user_email;
        $from_email = get_option('from_email') ? get_option('from_email') : get_option('admin_email');
        $emailEnable = get_option('enable_email') ? get_option('enable_email') : 0;
        $user_details = "[user_details]";

        $mail_msgs = __('Hi ', 'user-activity-log');
        $mail_msgs .= $current_user->display_name . ',';
        $mail_msgs .= "\n\n" . __("Following user is logged in your site", 'user-activity-log') . " \n$user_details";
        $mail_msgs .= "\n\n" . __("Thanks", 'user-activity-log') . ",\n";
        $mail_msgs .= home_url();

        $mail_msg = get_option('email_message') ? get_option('email_message') : $mail_msgs;
        if (isset($_POST['btnsolEmail']) && isset($_POST['_wp_email_nonce']) && wp_verify_nonce($_POST['_wp_email_nonce'], '_wp_email_action')) {
            $to_email = sanitize_email($_POST['sol-mail-to']);
            $from_email = sanitize_email($_POST['sol-mail-from']);
            $mail_msg = ual_test_input($_POST['sol-mail-msg']);
            $emailEnable = $_POST['emailEnable'];
            update_option('enable_email', $emailEnable);
            if (isset($_POST['emailEnable'])) {
                if ($_POST['emailEnable'] == '1') {
                    if ($mail_msg == "") {
                        $msg = __("Please enter message", 'user-activity-log');
                        $class = "error";
                    } else if ($to_email == "" || $from_email == "") {
                        $msg = __("Please enter the email address", 'user-activity-log');
                        $class = "error";
                    } else if (!filter_var($to_email, FILTER_VALIDATE_EMAIL) || !filter_var($from_email, FILTER_VALIDATE_EMAIL) || !is_email($to_email) || !is_email($from_email)) {
                        $msg = __("Please enter valid email address", 'user-activity-log');
                        $class = "error";
                    } else {
                        update_option('to_email', $to_email);
                        update_option('from_email', $from_email);
                        update_option('email_message', $mail_msg);
                        $msg = __("Settings saved successfully.", 'user-activity-log');
                        $class = "updated";
                    }
                }
            }
        }
        ?>
        <div class="wrap">
            <?php
            if ($msg != "") {
                admin_notice_message($class, $msg);
            }
            ?>
            <form class="sol-form" method="POST" action="<?php echo admin_url('admin.php'). "?" . $_SERVER['QUERY_STRING']; ?>">
                <div class="sol-box-border">
                    <h3 class="sol-header-text"><?php _e('Email', 'user-activity-log'); ?></h3>
                    <p class="margin_bottom_30"><?php _e('This email will be sent upon login of selected users/roles.', 'user-activity-log'); ?></p>
                    <table class="sol-email-table" cellspacing="0">
                        <tr>
                            <th><?php _e('Enable?', 'user-activity-log'); ?></th>
                            <td>
                                <input type="radio" <?php checked($emailEnable, 1); ?> value="1" id="enableEmail" name="emailEnable" class="ui-helper-hidden-accessible">
                                <label class="ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left" for="enableEmail" role="button">
                                    <span class="ui-button-text"><?php _e('Yes', 'user-activity-log'); ?></span>
                                </label>
                                <input type="radio" <?php checked($emailEnable, 0); ?> value="0" id="disableEmail" name="emailEnable" class="ui-helper-hidden-accessible">
                                <label class="ui-button ui-widget ui-state-default ui-button-text-only ui-corner-right"for="disableEmail" role="button">
                                    <span class="ui-button-text"><?php _e('No', 'user-activity-log'); ?></span>
                                </label>
                            </td>
                        </tr>
                        <tr class="fromEmailTr">
                            <th><?php _e('From Email', 'user-activity-log'); ?></th>
                            <td>
                                <input type="email" name="sol-mail-from" value="<?php echo $from_email; ?>">
                                <p class="description"><?php _e('The source Email address', 'user-activity-log'); ?></p>
                            </td>
                        </tr>
                        <tr class="toEmailTr">
                            <th><?php _e('To Email', 'user-activity-log'); ?></th>
                            <td>
                                <input type="email" name="sol-mail-to" value="<?php echo $to_email; ?>">
                                <p class="description"><?php _e('The Email address notifications will be sent to', 'user-activity-log'); ?></p>
                            </td>
                        </tr>
                        <tr class="messageTr">
                            <th><?php _e('Message', 'user-activity-log'); ?></th>
                            <td>
                                <textarea cols="50" name="sol-mail-msg" rows="5"><?php echo $mail_msg; ?></textarea>
                                <p class="description"><?php _e('Customize the message as per your requirement', 'user-activity-log'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php
                    wp_nonce_field('_wp_email_action', '_wp_email_nonce');
                    ?>
                    <p class="submit">
                        <input class="button button-primary" type="submit" value="<?php esc_attr_e('Save Changes', 'user-activity-log'); ?>" name="btnsolEmail">
                    </p>
                </div>
            </form>
            <?php ual_advertisment_sidebar(); ?>
        </div>
        <?php
    }

endif;

add_action('wp_login', 'ual_send_email', 99);
/**
 * Send email when selected user login
 *
 * @param string $login current username when login
 */
if (!function_exists('ual_send_email')) {

    function ual_send_email($login) {
        $current_user1 = get_user_by('login', $login);
        $current_user = !empty($current_user1->user_login) ? $current_user1->user_login : "-";
        $enable_unm = get_option('enable_user_list');
        for ($i = 0; $i < count($enable_unm); $i++) {
            if ($enable_unm[$i] == $current_user) {
                $to_email = get_option('to_email');
                $from_email = get_option('from_email');
                $ip = $_SERVER['REMOTE_ADDR'];
                $firstname = ucfirst($current_user1->user_firstname);
                $lastname = ucfirst($current_user1->user_lastname);

                $user_firstnm = !empty($firstname) ? ucfirst($firstname) : "-";
                $user_lastnm = !empty($lastname) ? ucfirst($lastname) : "-";
                $user_email = !empty($current_user1->user_email) ? $current_user1->user_email : "-";

                $modified_date = current_time('mysql');
                $modified_date = strtotime($modified_date);

                $date_format = get_option('date_format');
                $time_format = get_option('time_format');

                $date = date($date_format, $modified_date);
                $time = date($time_format, $modified_date);
                $user_reg = $date;
                $user_reg .= " ";
                $user_reg .= $time;

                $current_user = ucfirst($current_user);
                $user_details = "<table cellspacing='0' border='1px solid #ccc' class='sol-msg' style='margin-top:30px'>
                                <tr>
                                    <td style='padding:5px 10px;'>" . __('Username', 'user-activity-log') . "</td>
                                    <td style='padding:5px 10px;'>" . __('Firstname', 'user-activity-log') . "</td>
                                    <td style='padding:5px 10px;'>" . __('Lastname', 'user-activity-log') . "</td>
                                    <td style='padding:5px 10px;'>" . __('Email', 'user-activity-log') . "</td>
                                    <td style='padding:5px 10px;'>" . __('Date Time', 'user-activity-log') . "</td>
                                    <td style='padding:5px 10px;'>" . __('IP address', 'user-activity-log') . "</td>
                                </tr>
                                <tr>
                                    <td style='padding:5px 10px;'>$current_user</td>
                                    <td style='padding:5px 10px;'>$user_firstnm</td>
                                    <td style='padding:5px 10px;'>$user_lastnm</td>
                                    <td style='padding:5px 10px;'>$user_email</td>
                                    <td style='padding:5px 10px;'>$user_reg</td>
                                    <td style='padding:5px 10px;'>$ip</td>
                                </tr>
                            </table><br/><br/>";

                $mail_msg = get_option('email_message');
                $mail_msg = str_replace('[user_details]', $user_details, $mail_msg);

                if ($to_email != "" && $mail_msg != "" && $from_email != "") {
                    $headers = "From: " . strip_tags($from_email) . "\r\n";
                    $headers .= "Reply-To: " . strip_tags($from_email) . "\r\n";
                    $headers .= "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                    wp_mail($to_email, __('User Login Notification', 'user-activity-log'), $mail_msg, $headers);
                }
            }
        }
    }

}


/**
 * General settings
 */
if (!function_exists('ual_general_settings')) {

    function ual_general_settings() {
        $active = $_GET['page'];
        global $wpdb;
        $table_nm = $wpdb->prefix . "ualp_user_activity";
        if (isset($_GET['db']) && $_GET['db'] == 'reset') {
            $nonce = "";
            if (isset($_REQUEST['_wpnonce'])) {
                $nonce = $_REQUEST['_wpnonce'];
            }
            if (!wp_verify_nonce($nonce, 'my-nonce')) {
                // This nonce is not valid.
                return FALSE;
            } else {
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_nm'")) {
                    $wpdb->query('TRUNCATE ' . $table_nm);
                    $class = 'updated';
                    $message = 'All activities from the database has been deleted successfully.';
                    admin_notice_message($class, $message);
                }
            }
        }
        $log_day = "30";
        if (isset($_POST['submit_display'])) {
            $time_ago = trim($_POST['logdel']);
            if (!empty($time_ago)) {
                update_option('ualpKeepLogsDay', $time_ago);
                $class = 'updated';
                $message = __("Settings saved successfully.", 'user-activity-log');
                admin_notice_message($class, $message);
            }
        }
        $log_day = get_option('ualpKeepLogsDay');
        ?>
        <div class="wrap">
            <?php if (isset($_SESSION['success_msg'])) { ?>
                <div class="success_msg"><?php
                    $class = 'updated';
                    admin_notice_message($class, $_SESSION['success_msg']);
                    unset($_SESSION['success_msg']);
                    ?></div>
            <?php } ?>
            <form class="sol-form" method="POST" action="<?php echo admin_url('admin.php'). "?" . $_SERVER['QUERY_STRING']; ?>" name="general_setting_form">
                <div class="sol-box-border">
                    <h3 class="sol-header-text"><?php _e('Display Option', 'user-activity-log'); ?></h3>
                    <p class="margin_bottom_30"><?php _e('There are some basic options for display User Action Log', 'user-activity-log'); ?></p>
                    <table class="sol-email-table">
                        <tr>
                            <th><?php _e('Keep logs for', 'user-activity-log'); ?></th>
                            <td>
                                <input type="number" step="1" min="1" placeholder="30" value="<?php echo $log_day; ?>" name="logdel">&nbsp;<?php _e('Days', 'user-activity-log'); ?>
                                <p><?php _e('Maximum number of days to keep activity log. Leave blank to keep activity log forever', 'user-activity-log'); ?> (<?php _e('not recommended', 'user-activity-log'); ?>).</p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Delete Log Activities', 'user-activity-log'); ?></th>
                            <td>
                                <?php $nonce = wp_create_nonce('my-nonce'); ?>
                                <a href="<?php echo admin_url('admin.php?page=general_settings_menu'); ?> &db=reset&_wpnonce=<?php echo $nonce; ?>" onClick="return confirm('<?php _e('Are you sure want to Reset Database?', 'user-activity-log'); ?>');"><?php _e('Reset Database', 'user-activity-log'); ?></a>
                                <p><span class="red"><?php _e('Warning', 'user-activity-log'); ?>: &nbsp;</span><?php _e('Clicking this will delete all activities from the database.', 'user-activity-log'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input id="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save Changes', 'user-activity-log'); ?>" name="submit_display">
                    </p>
                </div>
            </form>
            <?php ual_advertisment_sidebar(); ?>
        </div>
        <?php
    }

}

