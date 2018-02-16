<?php

add_action('plugins_loaded', 'ual_load_plugin');

if (!function_exists('ual_load_plugin')) {

    function ual_load_plugin() {

        // The promo time
        $userActivityLog['promo_time'] = get_option('ual_promo_time');
        if (empty($userActivityLog['promo_time'])) {
            $userActivityLog['promo_time'] = time();
            update_option('ual_promo_time', $userActivityLog['promo_time']);
        }

        // Are we to show the Blog Designer promo
        if (!empty($userActivityLog['promo_time']) && $userActivityLog['promo_time'] > 0 && $userActivityLog['promo_time'] < (time() - (60 * 60 * 24 * 3))) {
            add_action('admin_notices', 'ual_promo');
        }

        // Are we to disable the promo
        if (isset($_GET['ual_promo']) && (int) $_GET['ual_promo'] == 0) {
            update_option('ual_promo_time', (0 - time()));
            die('DONE');
        }
    }

}

if (!function_exists('ual_promo')) {

    // Show the promo
    function ual_promo() {
        echo '
            <script>
            jQuery(document).ready( function() {
                    (function($) {
                            $("#ual_promo .ual_promo-close").click(function(){
                                    var data;
                                    // Hide it
                                    $("#ual_promo").hide();

                                    // Save this preference
                                    $.post("' . admin_url('admin.php?ual_promo=0') . '", data, function(response) {
                                            //alert(response);
                                    });
                            });
                    })(jQuery);
            });
            </script>
            <style>/* Promotional notice css*/
                .ual_button {
                    background-color: #4CAF50; /* Green */
                    border: none;
                    color: white;
                    padding: 8px 16px;
                    text-align: center;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 16px;
                    margin: 4px 2px;
                    -webkit-transition-duration: 0.4s; /* Safari */
                    transition-duration: 0.4s;
                    cursor: pointer;
                }
                .ual_button:focus{
                    border: none;
                    color: white;
                }
                .ual_button1 {
                    color: white;
                    background-color: #4CAF50;
                    border:3px solid #4CAF50;
                }
                .ual_button1:hover {
                    box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
                    color: white;
                    border:3px solid #4CAF50;
                }
                .ual_button2 {
                    color: white;
                    background-color: #0085ba;
                }
                .ual_button2:hover {
                    box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
                    color: white;
                }
                .ual_button3 {
                    color: white;
                    background-color: #365899;
                }
                .ual_button3:hover {
                    box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
                    color: white;
                }
                .ual_button4 {
                    color: white;
                    background-color: rgb(66, 184, 221);
                }
                .ual_button4:hover {
                    box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
                    color: white;
                }
                .ual_promo-close {
                    float:right;
                    text-decoration:none;
                    margin: 5px 10px 0px 0px;
                }
                .ual_promo-close:hover {
                    color: red;
                }
                </style>
                <div class="notice notice-success" id="ual_promo" style="min-height:120px">
                        <a class="ual_promo-close" href="javascript:" aria-label="Dismiss this Notice">
                                <span class="dashicons dashicons-dismiss"></span> Dismiss
                        </a>
                        <img src="' . UAL_PLUGIN_URL . '/images/logo-200.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
                        <p style="font-size:16px">' . __("We are glad you like <strong>User Activity Log</strong> plugin and have been using it since the past few days. It is time to take the next step.", "user-activity-log") . '</p>
                        <p>
                                <a class="ual_button ual_button1" target="_blank" href="http://useractivitylog.solwininfotech.com/#ualp_versions">' . __("Upgrade to Pro", "user-activity-log") . '</a>
                                <a class="ual_button ual_button2" target="_blank" href="https://wordpress.org/support/plugin/user-activity-log/reviews/?filter=5">' . __("Rate it 5★'s", "user-activity-log") . '</a>
                                <a class="ual_button ual_button3" target="_blank" href="https://www.facebook.com/SolwinInfotech/">' . __("Like Us on Facebook", "user-activity-log") . '</a>
                                <a class="ual_button ual_button4" target="_blank" href="https://twitter.com/home?status=' . rawurlencode('I use #useractivitylog to secure my #WordPress site - http://useractivitylog.solwininfotech.com/') . '">' . __("Tweet about User Activity Log", "user-activity-log") . '</a>
                        </p>
                </div>';
    }

}