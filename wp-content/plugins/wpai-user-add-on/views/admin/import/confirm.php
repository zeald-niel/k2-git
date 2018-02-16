<ul style="padding-left: 35px;">
    <?php if ( $post['is_update_first_name']): ?>
        <li> <?php _e('First Name', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_last_name']): ?>
        <li> <?php _e('Last Name', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_role']): ?>
        <li> <?php _e('Role', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_nickname']): ?>
        <li> <?php _e('Nickname', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_description']): ?>
        <li> <?php _e('Description', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_login']): ?>
        <li> <?php _e('Login', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_password']): ?>
        <li> <?php _e('Password', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_nicename']): ?>
        <li> <?php _e('Nicename', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_email']): ?>
        <li> <?php _e('Email', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_registered']): ?>
        <li> <?php _e('Registered Date', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_display_name']): ?>
        <li> <?php _e('Display Name', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_url']): ?>
        <li> <?php _e('URL', 'wp_all_import_user_add_on'); ?></li>
    <?php endif; ?>
    <?php if ( ! empty($post['is_update_acf'])): ?>
        <li>
            <?php
            switch($post['update_acf_logic']){
                case 'full_update':
                    _e('All advanced custom fields', 'wp_all_import_user_add_on');
                    break;
                case 'mapped':
                    _e('Only ACF presented in import options', 'wp_all_import_user_add_on');
                    break;
                case 'only':
                    printf(__('Only these ACF : %s', 'wp_all_import_user_add_on'), $post['acf_only_list']);
                    break;
                case 'all_except':
                    printf(__('All ACF except these: %s', 'wp_all_import_user_add_on'), $post['acf_except_list']);
                    break;
            } ?>
        </li>
    <?php endif; ?>
    <?php if ( ! empty($post['is_update_custom_fields'])): ?>
        <li>
            <?php
            switch($post['update_custom_fields_logic']){
                case 'full_update':
                    _e('All custom fields', 'wp_all_import_user_add_on');
                    break;
                case 'only':
                    printf(__('Only these custom fields : %s', 'wp_all_import_user_add_on'), $post['custom_fields_only_list']);
                    break;
                case 'all_except':
                    printf(__('All custom fields except these: %s', 'wp_all_import_user_add_on'), $post['custom_fields_except_list']);
                    break;
            } ?>
        </li>
    <?php endif; ?>
</ul>