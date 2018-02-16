<?php
/**
 * Handles additional widget tab options
 * run on __construct function
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !class_exists( 'EASY_SIDEBAR_MENU_WIDGET_NOTICES' ) ):
class EASY_SIDEBAR_MENU_WIDGET_NOTICES {
    public function  __construct() {
        if ( is_admin() ){
            add_action( 'admin_notices', array( &$this, 'admin_messages') );
        }
        add_action('wp_ajax_easy_sidebar_menu_widget_hideRating', array( &$this, 'hide_rating') );
    }

    /* Hide the rating div
     * @return json string
     *
     */
    function hide_rating(){
        update_option('easy_sidebar_menu_widget_RatingDiv','yes');
        echo json_encode(array("success")); exit;
    }

    /**
     * Admin Messages
     * @return void
     */
    function admin_messages() {
        if (!current_user_can('update_plugins'))
        return;


        $install_date   = get_option('easy_sidebar_menu_widget_installDate');
        $saved          = get_option('easy_sidebar_menu_widget_RatingDiv');
        $display_date   = date('Y-m-d h:i:s');
        $datetime1      = new DateTime($install_date);
        $datetime2      = new DateTime($display_date);
        $diff_intrval   = round(($datetime2->format('U') - $datetime1->format('U')) / (60*60*24));
        if( 'yes' != $saved && $diff_intrval >= 7 ){
        echo '<div class="easy_sidebar_menu_widget_notice updated" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
            <p>Awesome, you\'ve been using <strong>Easy Sidebar Menu Widget</strong> for more than 1 week. <br> May i ask you to give it a <strong>5-star rating</strong> on Wordpress? </br>
            This will help to spread its popularity and to make this plugin a better one.
            <br><br>Your help is much appreciated. Thank you very much,<br> ~ Jeffrey Carandang <em>(phpbits)</em>
            <ul>
                <li><a href="http://wordpress.org/plugins/easy-sidebar-menu-widget/" class="thankyou" target="_blank" title="Ok, you deserved it" style="font-weight:bold;">'. __( 'Ok, you deserved it', 'easy_sidebar_menu_widget' ) .'</a></li>
                <li><a href="javascript:void(0);" class="easy_sidebar_menu_widget_bHideRating" title="I already did" style="font-weight:bold;">'. __( 'I already did', 'easy_sidebar_menu_widget' ) .'</a></li>
                <li><a href="javascript:void(0);" class="easy_sidebar_menu_widget_bHideRating" title="No, not good enough" style="font-weight:bold;">'. __( 'No, not good enough, i do not like to rate it!', 'easy_sidebar_menu_widget' ) .'</a></li>
            </ul>
        </div>
        <script>
        jQuery( document ).ready(function( $ ) {

        jQuery(\'.easy_sidebar_menu_widget_bHideRating\').click(function(){
            var data={\'action\':\'easy_sidebar_menu_widget_hideRating\'}
                 jQuery.ajax({

            url: "'. admin_url( 'admin-ajax.php' ) .'",
            type: "post",
            data: data,
            dataType: "json",
            async: !0,
            success: function(e) {
                if (e=="success") {
                   jQuery(\'.easy_sidebar_menu_widget_notice\').slideUp(\'slow\');

                }
            }
             });
            })

        });
        </script>
        ';
        }
    }
}
new EASY_SIDEBAR_MENU_WIDGET_NOTICES();
endif;
?>
