<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/14/16
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Orders extends MailChimp_WooCommerce_Abtstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_orders';
    public $items = array();

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'orders';
    }

    /**
     * @param MailChimp_WooCommerce_Order $item
     *
     * @return mixed
     */
    protected function iterate($item)
    {
        if ($item instanceof MailChimp_WooCommerce_Order) {

            // since we're syncing the customer for the first time, this is where we need to add the override
            // for subscriber status. We don't get the checkbox until this plugin is actually installed and working!
            if (!($status = $item->getCustomer()->getOptInStatus())) {
                try {
                    $subscriber = $this->mailchimp()->member(mailchimp_get_list_id(), $item->getCustomer()->getEmailAddress());
                    $status = $subscriber['status'] !== 'unsubscribed';
                } catch (\Exception $e) {
                    $status = (bool) $this->getOption('mailchimp_auto_subscribe', true);
                }
                $item->getCustomer()->setOptInStatus($status);
            }

            mailchimp_debug('order_sync', "#{$item->getId()}", $item->toArray());

            $type = $this->mailchimp()->getStoreOrder($this->store_id, $item->getId()) ? 'update' : 'create';
            $call = $type === 'create' ? 'addStoreOrder' : 'updateStoreOrder';

            try {

                // if the order is in failed or cancelled status - and it's brand new, we shouldn't submit it.
                if ($call === 'addStoreOrder' && in_array($item->getFinancialStatus(), array('failed', 'cancelled'))) {
                    return false;
                }

                // make the call
                $response = $this->mailchimp()->$call($this->store_id, $item, false);

                if (empty($response)) {
                    mailchimp_error('order_submit.failure', "$call :: #{$item->getId()} :: email: {$item->getCustomer()->getEmailAddress()} produced a blank response from MailChimp");
                    return $response;
                }

                mailchimp_log('order_submit.success', "$call :: #{$item->getId()} :: email: {$item->getCustomer()->getEmailAddress()}");

                $this->items[] = array('response' => $response, 'item' => $item);

                return $response;

            } catch (MailChimp_WooCommerce_ServerError $e) {
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "$call :: {$item->getId()}"));
                return false;
            } catch (MailChimp_WooCommerce_Error $e) {
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "$call :: {$item->getId()}"));
                return false;
            } catch (Exception $e) {
                mailchimp_error('order_submit.error', mailchimp_error_trace($e, "$call :: {$item->getId()}"));
                return false;
            }
        }

        mailchimp_debug('order_submit', 'no order found', $item);

        return false;
    }

    /**
     * After the resources have been loaded and pushed
     */
    protected function complete()
    {
        mailchimp_log('order_submit.completed', 'Done with the order sync.');

        // add a timestamp for the orders sync completion
        $this->setResourceCompleteTime();

        // this is the last thing we're doing so it's complete as of now.
        $this->flagStopSync();
    }
}
