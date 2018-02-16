<?php

/**
 * Created by MailChimp.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 2/17/16
 * Time: 12:03 PM
 */
class MailChimp_Service extends MailChimp_Woocommerce_Options
{
    protected static $pushed_orders = array();

    protected $user_email = null;
    protected $previous_email = null;
    protected $force_cart_post = false;
    protected $cart_was_submitted = false;
    protected $cart = array();
    protected $validated_cart_db = false;

    /**
     * hook fired when we know everything is booted
     */
    public function wooIsRunning()
    {
        $path = plugin_dir_path( dirname( __FILE__ ) );

        if (function_exists('WC') && (int) WC()->version >= 3) {
            require_once $path . 'includes/api/class-mailchimp-woocommerce-transform-orders-wc3.php';
        } else {
            require_once $path . 'includes/api/class-mailchimp-woocommerce-transform-orders.php';
        }

        // make sure the site option for setting the mailchimp_carts has been saved.
        $this->validated_cart_db = get_site_option('mailchimp_woocommerce_db_mailchimp_carts', false);
        $this->is_admin = current_user_can('administrator');
    }

    /**
     * @param $r
     * @param $url
     * @return mixed
     */
    public function addHttpRequestArgs( $r, $url ) {
        // not sure whether or not we need to implement something like this yet.
        //$r['headers']['Authorization'] = 'Basic ' . base64_encode('username:password');
        return $r;
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    protected function cookie($key, $default = null)
    {
        if ($this->is_admin) {
            return $default;
        }

        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * This should only fire on a web based order so we can do real campaign tracking here.
     *
     * @param $order_id
     */
    public function onNewOrder($order_id)
    {
        if ($this->hasOption('mailchimp_api_key')) {

            // register this order is already in process..
            static::$pushed_orders[$order_id] = true;

            // see if we have a session id and a campaign id, also only do this when this user is not the admin.
            $campaign_id = $this->getCampaignTrackingID();

            // grab the landing site cookie if we have one here.
            $landing_site = $this->getLandingSiteCookie();

            // expire the landing site cookie so we can rinse and repeat tracking
            $this->expireLandingSiteCookie();

            // queue up the single order to be processed.
            $handler = new MailChimp_WooCommerce_Single_Order($order_id, null, $campaign_id, $landing_site);
            wp_queue($handler, 60);
        }
    }

    /**
     * @param $order_id
     * @param bool $is_admin
     */
    public function handleOrderStatusChanged($order_id, $is_admin = false)
    {
        if ($this->hasOption('mailchimp_api_key')) {
            // register this order is already in process..
            static::$pushed_orders[$order_id] = true;
            // queue up the single order to be processed.
            $handler = new MailChimp_WooCommerce_Single_Order($order_id, null, null, null);
            $handler->is_update = true;
            $handler->is_admin_save = $is_admin;
            wp_queue($handler, 90);
        }
    }

    /**
     * @param $order_id
     */
    public function onPartiallyRefunded($order_id)
    {
        if ($this->hasOption('mailchimp_api_key')) {
            $handler = new MailChimp_WooCommerce_Single_Order($order_id, null, null, null);
            $handler->partially_refunded = true;
            wp_queue($handler);
        }
    }

    /**
     * @param null $updated
     * @return bool|null
     */
    public function handleCartUpdated($updated = null)
    {
        if ($updated === false || $this->is_admin || $this->cart_was_submitted || !$this->hasOption('mailchimp_api_key')) {
            return !is_null($updated) ? $updated : false;
        }

        if (empty($this->cart)) {
            $this->cart = $this->getCartItems();
        }

        if (($user_email = $this->getCurrentUserEmail())) {

            $previous = $this->getPreviousEmailFromSession();

            $uid = md5(trim(strtolower($user_email)));

            // delete the previous records.
            if (!empty($previous) && $previous !== $user_email) {

                if ($this->api()->deleteCartByID($this->getUniqueStoreID(), $previous_email = md5(trim(strtolower($previous))))) {
                    mailchimp_log('ac.cart_swap', "Deleted cart [$previous] :: ID [$previous_email]");
                }

                // going to delete the cart because we are switching.
                $this->deleteCart($previous_email);
            }

            if ($this->cart && !empty($this->cart)) {

                // track the cart locally so we can repopulate things for cross device compatibility.
                $this->trackCart($uid, $user_email);

                $this->cart_was_submitted = true;

                // grab the cookie data that could play important roles in the submission
                $campaign = $this->getCampaignTrackingID();

                // fire up the job handler
                $handler = new MailChimp_WooCommerce_Cart_Update($uid, $user_email, $campaign, $this->cart);
                wp_queue($handler);
            }

            return !is_null($updated) ? $updated : true;
        }

        return !is_null($updated) ? $updated : false;
    }

    /**
     * @param $post_id
     */
    public function handleNewCoupon($post_id)
    {
        $this->handleCouponSaved($post_id, new WC_Coupon($post_id));
    }

    /**
     * @param $post_id
     * @param null $coupon
     */
    public function handleCouponSaved($post_id, $coupon = null)
    {
        if ($coupon instanceof WC_Coupon) {
            wp_queue(new MailChimp_WooCommerce_SingleCoupon($post_id));
        }
    }

    /**
     * @param $post_id
     */
    public function handleCouponRestored($post_id)
    {
        $this->handleCouponSaved($post_id, new WC_Coupon($post_id));
    }

    /**
     * Save post metadata when a post is saved.
     *
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function handlePostSaved($post_id, $post, $update)
    {
        if ($post->post_status !== 'auto-draft') {
            if ('product' == $post->post_type) {
                wp_queue(new MailChimp_WooCommerce_Single_Product($post_id), 5);
            } elseif ('shop_order' == $post->post_type) {
                $this->handleOrderStatusChanged($post_id, is_admin());
            }
        }
    }

    /**
     * @param $post_id
     * @return bool
     */
    public function handlePostTrashed($post_id)
    {
        if (!$this->hasOption('mailchimp_api_key')) {
            return false;
        }

        switch (get_post_type($post_id)) {
            case 'shop_coupon':
                mailchimp_get_api()->deletePromoRule(mailchimp_get_store_id(), $post_id);
                mailchimp_log('promo_code.deleted', "deleted promo code {$post_id}");
                break;
        }
    }

    /**
     * @param $post_id
     */
    public function handlePostRestored($post_id)
    {
        switch(get_post_type($post_id)) {
            case 'shop_coupon':
                return $this->handleCouponRestored($post_id);
                break;
        }
    }

    /**
     * @param $user_id
     */
    public function handleUserRegistration($user_id)
    {
        $subscribed = (bool) isset($_POST['mailchimp_woocommerce_newsletter']) ?
            $_POST['mailchimp_woocommerce_newsletter'] : false;

        // update the user meta with the 'is_subscribed' form element
        update_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', $subscribed);

        if ($subscribed) {
            wp_queue(new MailChimp_WooCommerce_User_Submit($user_id, $subscribed));
        }
    }

    /**
     * @param $user_id
     * @param $old_user_data
     */
    function handleUserUpdated($user_id, $old_user_data)
    {
        // only update this person if they were marked as subscribed before
        $is_subscribed = get_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', true);

        // if they don't have a meta set for is_subscribed, we will get a blank string, so just ignore this.
        if ($is_subscribed === '' || $is_subscribed === null) return;

        // only send this update if the user actually has a boolean value.
        wp_queue(new MailChimp_WooCommerce_User_Submit($user_id, (bool) $is_subscribed, $old_user_data));
    }

    /**
     * Delete all the options pointing to the pages, and re-start the sync process.
     * @param bool $only_products
     * @return bool
     */
    protected function syncProducts($only_products = false)
    {
        if (!$this->isAdmin()) return false;
        $this->removePointers(true, ($only_products ? false : true));
        update_option('mailchimp-woocommerce-sync.orders.prevent', $only_products);
        MailChimp_WooCommerce_Process_Products::push();
        return true;
    }

    /**
     * Delete all the options pointing to the pages, and re-start the sync process.
     * @return bool
     */
    protected function syncOrders()
    {
        if (!$this->isAdmin()) return false;
        $this->removePointers(false, true);
        // since the products are all good, let's sync up the orders now.
        wp_queue(new MailChimp_WooCommerce_Process_Orders());
        return true;
    }

    /**
     * @return bool|string
     */
    public function getCurrentUserEmail()
    {
        if (isset($this->user_email) && !empty($this->user_email)) {
            return $this->user_email = strtolower($this->user_email);
        }

        $user = wp_get_current_user();
        $email = ($user->ID > 0 && isset($user->user_email)) ? $user->user_email : $this->getEmailFromSession();

        return $this->user_email = strtolower($email);
    }

    /**
     * @return bool|array
     */
    public function getCartItems()
    {
        if (!($this->cart = $this->getWooSession('cart', false))) {
            $this->cart = WC()->cart->get_cart();
        } else {
            $cart_session = array();
            foreach ( $this->cart as $key => $values ) {
                $cart_session[$key] = $values;
                unset($cart_session[$key]['data']); // Unset product object
            }
            return $this->cart = $cart_session;
        }

        return is_array($this->cart) ? $this->cart : false;
    }

    /**
     * Set the cookie of the mailchimp campaigns if we have one.
     */
    public function handleCampaignTracking()
    {
        // set the landing site cookie if we don't have one.
        $this->setLandingSiteCookie();

        $cookie_duration = $this->getCookieDuration();

        // if we have a query string of the mc_cart_id in the URL, that means we are sending a campaign from MC
        if (isset($_GET['mc_cart_id']) && !isset($_GET['removed_item'])) {

            // try to pull the cart from the database.
            if (($cart = $this->getCart($_GET['mc_cart_id'])) && !empty($cart)) {

                // set the current user email
                $this->user_email = trim(str_replace(' ','+', $cart->email));

                if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                    $this->previous_email = $current_email;
                    @setcookie('mailchimp_user_previous_email',$this->user_email, $cookie_duration, '/' );
                }

                // cookie the current email
                @setcookie('mailchimp_user_email', $this->user_email, $cookie_duration, '/' );

                // set the cart data.
                $this->setWooSession('cart', unserialize($cart->cart));
            }
        }

        if (isset($_REQUEST['mc_cid'])) {
            $this->setCampaignTrackingID($_REQUEST['mc_cid'], $cookie_duration);
        }

        if (isset($_REQUEST['mc_eid'])) {
            @setcookie('mailchimp_email_id', trim($_REQUEST['mc_eid']), $cookie_duration, '/' );
        }
    }

    /**
     * @return mixed|null
     */
    public function getCampaignTrackingID()
    {
        $cookie = $this->cookie('mailchimp_campaign_id', false);
        if (empty($cookie)) {
            $cookie = $this->getWooSession('mailchimp_tracking_id', false);
        }

        return $cookie;
    }

    /**
     * @param $id
     * @param $cookie_duration
     * @return $this
     */
    public function setCampaignTrackingID($id, $cookie_duration)
    {
        $cid = trim($id);

        @setcookie('mailchimp_campaign_id', $cid, $cookie_duration, '/' );
        $this->setWooSession('mailchimp_campaign_id', $cid);

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getLandingSiteCookie()
    {
        $cookie = $this->cookie('mailchimp_landing_site', false);

        if (empty($cookie)) {
            $cookie = $this->getWooSession('mailchimp_landing_site', false);
        }

        return $cookie;
    }

    /**
     * @return $this
     */
    public function setLandingSiteCookie()
    {
        if (isset($_GET['expire_landing_site'])) $this->expireLandingSiteCookie();

        // if we already have a cookie here, we need to skip it.
        if ($this->getLandingSiteCookie() != false) return $this;

        $http_referer = $this->getReferer();

        if (!empty($http_referer)) {

            // grab the current landing url since it's a referral.
            $landing_site = home_url() . wp_unslash($_SERVER['REQUEST_URI']);

            $compare_refer = str_replace(array('http://', 'https://'), '', $http_referer);
            $compare_local = str_replace(array('http://', 'https://'), '', $landing_site);

            if (strpos($compare_local, $compare_refer) === 0) return $this;

            // set the cookie
            @setcookie('mailchimp_landing_site', $landing_site, $this->getCookieDuration(), '/' );

            $this->setWooSession('mailchimp_landing_site', $landing_site);
        }

        return $this;
    }

    /**
     * @return array|bool|string
     */
    public function getReferer()
    {
        if (!empty($_REQUEST['_wp_http_referer'])) {
            return wp_unslash($_REQUEST['_wp_http_referer']);
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            return wp_unslash( $_SERVER['HTTP_REFERER']);
        }
        return false;
    }

    /**
     * @return $this
     */
    public function expireLandingSiteCookie()
    {
        @setcookie('mailchimp_landing_site', false, $this->getCookieDuration(), '/' );
        $this->setWooSession('mailchimp_landing_site', false);

        return $this;
    }

    /**
     * @return bool
     */
    protected function getEmailFromSession()
    {
        return $this->cookie('mailchimp_user_email', false);
    }

    /**
     * @return bool
     */
    protected function getPreviousEmailFromSession()
    {
        if ($this->previous_email) {
            return $this->previous_email = strtolower($this->previous_email);
        }
        $email = $this->cookie('mailchimp_user_previous_email', false);
        return $email ? strtolower($email) : false;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getWooSession($key, $default = null)
    {
        if (!($woo = WC()) || empty($woo->session)) {
            return $default;
        }
        return $woo->session->get($key, $default);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setWooSession($key, $value)
    {
        if (!($woo = WC()) || empty($woo->session)) {
            return $this;
        }

        $woo->session->set($key, $value);

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function removeWooSession($key)
    {
        if (!($woo = WC()) || empty($woo->session)) {
            return $this;
        }

        $woo->session->__unset($key);
        return $this;
    }

    /**
     *
     */
    public function get_user_by_hash()
    {
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_GET['hash'])) {
            if (($cart = $this->getCart($_GET['hash']))) {
                $this->respondJSON(array('success' => true, 'email' => $cart->email));
            }
        }

        $this->respondJSON(array('success' => false, 'email' => false));
    }

    /**
     *
     */
    public function set_user_by_email()
    {
        if ($this->is_admin) {
            $this->respondJSON(array('success' => false));
        }

        if (defined('DOING_AJAX') && DOING_AJAX && isset($_GET['email'])) {

            $cookie_duration = $this->getCookieDuration();

            $this->user_email = trim(str_replace(' ','+', $_GET['email']));

            if (($current_email = $this->getEmailFromSession()) && $current_email !== $this->user_email) {
                $this->previous_email = $current_email;
                $this->force_cart_post = true;
                @setcookie('mailchimp_user_previous_email',$this->user_email, $cookie_duration, '/' );
            }

            @setcookie('mailchimp_user_email', $this->user_email, $cookie_duration, '/' );

            $this->getCartItems();

            $this->handleCartUpdated();

            $this->respondJSON(array(
                'success' => true,
                'email' => $this->user_email,
                'previous' => $this->previous_email,
                'cart' => $this->cart,
            ));
        }

        $this->respondJSON(array('success' => false, 'email' => false));
    }


    /**
     * @param string $time
     * @return int
     */
    protected function getCookieDuration($time = 'thirty_days')
    {
        $durations = array(
            'one_day' => 86400, 'seven_days' => 604800, 'fourteen_days' => 1209600, 'thirty_days' => 2419200,
        );

        if (!array_key_exists($time, $durations)) {
            $time = 'thirty_days';
        }

        return time() + $durations[$time];
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     */
    protected function get($key, $default = false)
    {
        if (!isset($_REQUEST['mailchimp-woocommerce']) || !isset($_REQUEST['mailchimp-woocommerce'][$key])) {
            return $default;
        }
        return $_REQUEST['mailchimp-woocommerce'][$key];
    }

    /**
     * @param $uid
     * @return array|bool|null|object|void
     */
    protected function getCart($uid)
    {
        if (!$this->validated_cart_db) return false;

        global $wpdb;

        $table = "{$wpdb->prefix}mailchimp_carts";
        $statement = "SELECT * FROM $table WHERE id = %s";
        $sql = $wpdb->prepare($statement, $uid);

        if (($saved_cart = $wpdb->get_row($sql)) && !empty($saved_cart)) {
            return $saved_cart;
        }

        return false;
    }

    /**
     * @param $uid
     * @return true
     */
    protected function deleteCart($uid)
    {
        if (!$this->validated_cart_db) return false;

        global $wpdb;
        $table = "{$wpdb->prefix}mailchimp_carts";
        $sql = $wpdb->prepare("DELETE FROM $table WHERE id = %s", $uid);
        $wpdb->query($sql);

        return true;
    }

    /**
     * @param $uid
     * @param $email
     * @return bool
     */
    protected function trackCart($uid, $email)
    {
        if (!$this->validated_cart_db) return false;

        global $wpdb;

        $table = "{$wpdb->prefix}mailchimp_carts";

        $statement = "SELECT * FROM $table WHERE id = %s";
        $sql = $wpdb->prepare($statement, $uid);

        $user_id = get_current_user_id();

        if (($saved_cart = $wpdb->get_row($sql)) && is_object($saved_cart)) {
            $statement = "UPDATE {$table} SET `cart` = '%s', `email` = '%s', `user_id` = %s WHERE `id` = '%s'";
            $sql = $wpdb->prepare($statement, array(maybe_serialize($this->cart), $email, $user_id, $uid));
            $wpdb->query($sql);
        } else {
            $wpdb->insert("{$wpdb->prefix}mailchimp_carts", array(
                'id' => $uid,
                'email' => $email,
                'user_id' => (int) $user_id,
                'cart'  => maybe_serialize($this->cart),
                'created_at'   => gmdate('Y-m-d H:i:s', time()),
            ));
        }

        return true;
    }

    /**
     * @param $data
     */
    protected function respondJSON($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

}
