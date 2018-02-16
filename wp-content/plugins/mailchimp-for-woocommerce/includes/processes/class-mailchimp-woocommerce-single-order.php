<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/15/16
 * Time: 11:42 AM
 */
class MailChimp_WooCommerce_Single_Order extends WP_Job
{
    public $order_id;
    public $cart_session_id;
    public $campaign_id;
    public $landing_site;
    public $is_update = false;
    public $is_admin_save = false;
    public $partially_refunded = false;
    protected $woo_order_number = false;

    /**
     * MailChimp_WooCommerce_Single_Order constructor.
     * @param null $order_id
     * @param null $cart_session_id
     * @param null $campaign_id
     * @param null $landing_site
     */
    public function __construct($order_id = null, $cart_session_id = null, $campaign_id = null, $landing_site = null)
    {
        if (!empty($order_id)) $this->order_id = $order_id;
        if (!empty($cart_session_id)) $this->cart_session_id = $cart_session_id;
        if (!empty($campaign_id)) $this->campaign_id = $campaign_id;
        if (!empty($landing_site)) $this->landing_site = $landing_site;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        $this->process();
        return false;
    }

    public function process()
    {
        $options = get_option('mailchimp-woocommerce', array());
        $store_id = mailchimp_get_store_id();

        // only if we have the right parameters to do the work
        if (!empty($store_id) && is_array($options) && isset($options['mailchimp_api_key'])) {

            if (!($woo_order_number = $this->getRealOrderNumber())) {
                mailchimp_log('order_submit.failure', "There is no real order number to use.");
                return false;
            }

            $job = new MailChimp_WooCommerce_Transform_Orders();
            $api = new MailChimp_WooCommerce_MailChimpApi($options['mailchimp_api_key']);

            // set the campaign ID
            $job->campaign_id = $this->campaign_id;

            $call = ($api_response = $api->getStoreOrder($store_id, $woo_order_number)) ? 'updateStoreOrder' : 'addStoreOrder';

            if (!$this->is_admin_save && $call === 'addStoreOrder' && $this->is_update === true) {
                return false;
            }

            // if we already pushed this order into the system, we need to unset it now just in case there
            // was another campaign that had been sent and this was only an order update.
            if ($call === 'updateStoreOrder') {
                $job->campaign_id = null;
                $this->campaign_id = null;
                $this->landing_site = null;
            }

            // will either add or update the order
            try {

                if (!($order_post = get_post($this->order_id))) {
                    return false;
                }

                // transform the order
                $order = $job->transform($order_post);

                // if the order is in failed or cancelled status - and it's brand new, we shouldn't submit it.
                if ($call === 'addStoreOrder' && in_array($order->getFinancialStatus(), array('failed', 'cancelled'))) {
                    return false;
                }

                mailchimp_debug('order_submit', "#{$woo_order_number}", $order->toArray());

                // if we're overriding this we need to set it here.
                if ($this->partially_refunded) {
                    $order->setFinancialStatus('partially_refunded');
                }

                // will be the same as the customer id. an md5'd hash of a lowercased email.
                $this->cart_session_id = $order->getCustomer()->getId();

                $log = "$call :: #{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()}";

                // only do this stuff on new orders
                if ($call === 'addStoreOrder') {

                    // apply a campaign id if we have one.
                    if (!empty($this->campaign_id)) {
                        $log .= ' :: campaign id ' . $this->campaign_id;
                        $order->setCampaignId($this->campaign_id);
                    }

                    // apply the landing site if we have one.
                    if (!empty($this->landing_site)) {
                        $log .= ' :: landing site ' . $this->landing_site;
                        $order->setLandingSite($this->landing_site);
                    }

                }

                // update or create
                $api_response = $api->$call($store_id, $order, false);

                if (empty($api_response)) {
                    mailchimp_error('order_submit.failure', "$call :: #{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()} produced a blank response from MailChimp");
                    return $api_response;
                }

                // if we're adding a new order and the session id is here, we need to delete the AC cart record.
                if (!empty($this->cart_session_id)) {
                    $api->deleteCartByID($store_id, $this->cart_session_id);
                    $log .= " :: abandoned cart deleted [{$this->cart_session_id}]";
                }

                mailchimp_log('order_submit.success', $log);

                return $api_response;

            } catch (\Exception $e) {

                $message = strtolower($e->getMessage());

                mailchimp_error('order_submit.tracing_error', $e);

                if (!isset($order)) {
                    // transform the order
                    $order = $job->transform(get_post($this->order_id));
                    $this->cart_session_id = $order->getCustomer()->getId();
                }

                // this can happen when a customer changes their email.
                if (isset($order) && strpos($message, 'not be changed')) {

                    try {

                        mailchimp_log('order_submit.deleting_customer', "#{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()}");

                        // delete the customer before adding it again.
                        $api->deleteCustomer($store_id, $order->getCustomer()->getId());

                        // update or create
                        $api_response = $api->$call($store_id, $order, false);

                        $log = "Deleted Customer :: $call :: #{$order->getId()} :: email: {$order->getCustomer()->getEmailAddress()}";

                        if (!empty($job->campaign_id)) {
                            $log .= ' :: campaign id '.$job->campaign_id;
                        }

                        mailchimp_log('order_submit.success', $log);

                        // if we're adding a new order and the session id is here, we need to delete the AC cart record.
                        if (!empty($this->cart_session_id)) {
                            $api->deleteCartByID($store_id, $this->cart_session_id);
                        }

                        return $api_response;

                    } catch (\Exception $e) {
                        mailchimp_error('order_submit.error', mailchimp_error_trace($e, 'deleting-customer-re-add :: #'.$this->order_id));
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getRealOrderNumber()
    {
        try {
            if (empty($this->order_id) || !($order_post = get_post($this->order_id))) {
                return false;
            }
            $woo = new WC_Order($order_post);
            return $this->woo_order_number = $woo->get_order_number();
        } catch (\Exception $e) {
            $this->woo_order_number = false;
            mailchimp_error('order_sync.failure', mailchimp_error_trace($e, "{$this->order_id} could not be loaded"));
            return false;
        }
    }
}

