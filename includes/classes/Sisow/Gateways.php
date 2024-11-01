<?php
class Sisow_Gateways
{
	public static function _getGateways($arrDefault)
    {

        $paymentOptions = array(
            'Sisow_Gateway_Afterpay',
            'Sisow_Gateway_Afterpayb2b',
            'Sisow_Gateway_Belfius',
            'Sisow_Gateway_Capayable',
            'Sisow_Gateway_Ebill',
			'Sisow_Gateway_Eps',
			//'Sisow_Gateway_Focum',
			'Sisow_Gateway_Giropay',
			'Sisow_Gateway_Homepay',
			'Sisow_Gateway_Ideal',
			'Sisow_Gateway_Idealqr',
			'Sisow_Gateway_Maestro',
			'Sisow_Gateway_Mastercard',
			'Sisow_Gateway_Mistercash',
			'Sisow_Gateway_Overboeking',
			'Sisow_Gateway_Paypalec',
			'Sisow_Gateway_Sofort',
			'Sisow_Gateway_Visa',
			'Sisow_Gateway_Vpay',
			'Sisow_Gateway_Vvv',
			'Sisow_Gateway_Webshopgiftcard',
			'Sisow_Gateway_Bunq',
			'Sisow_Gateway_Cbc',
			'Sisow_Gateway_Billink',
			'Sisow_Gateway_Kbc',
			'Sisow_Gateway_Spraypay',
			'Sisow_Gateway_Klarna',
        );

        $arrDefault = array_merge($arrDefault, $paymentOptions);

        return $arrDefault;
    }
	
	/**
     * This function registers the Sisow Payment Gateways
     */
    public static function register()
    {
        add_filter('woocommerce_payment_gateways', array(__CLASS__, '_getGateways'));
    }
	
	/**
     * This function adds the Sisow Global Settings to the woocommerce payment method settings
     */
    public static function addSettings()
    {
        add_filter('woocommerce_payment_gateways_settings', array(__CLASS__, '_addGlobalSettings'));
    }
	
	/**
     * Register the API's to catch the return and exchange
     */
    public static function registerApi()
    {
        add_action('woocommerce_api_wc_sisow_gateway_return', array(__CLASS__, '_sisowReturn'));
        add_action('woocommerce_api_wc_sisow_gateway_notify', array(__CLASS__, '_sisowNotify'));
		
		add_filter('woocommerce_cancel_unpaid_order', array(__CLASS__, '_getStatus'), 1, 2);
    }
	
	public static function _addGlobalSettings($settings)
    {
        $updatedSettings = array();

        $addedSettings = array();
        $addedSettings[] = array(
            'title' => __('Sisow settings', 'woocommerce-sisow'),
            'type' => 'title',
            'desc' => '<p>' . __('The following options are required to use the Sisow Gateway and are used by all Sisow Payment Methods', 'woocommerce-sisow') . '</p>',
            'id' => 'sisow_general_settings',
        );
        $addedSettings[] = array(
            'name' => __('Merchant ID', 'woocommerce-sisow'),
            'type' => 'text',
            'desc' => __('The Merchant ID from Sisow, you can find it in your Sisow account', 'woocommerce-sisow'),
            'id' => 'sisow_merchantid',
        );
		$addedSettings[] = array(
            'name' => __('Merchant Key', 'woocommerce-sisow'),
            'type' => 'text',
            'desc' => __('The Merchant Key from Sisow, you can find it in your Sisow account', 'woocommerce-sisow'),
            'id' => 'sisow_merchantkey',
        );
		$addedSettings[] = array(
            'name' => __('Shop ID', 'woocommerce-sisow'),
            'type' => 'text',
            'desc' => __('The Shop ID from Sisow, you can find it in your Sisow account', 'woocommerce-sisow'),
            'id' => 'sisow_shopid',
        );
		$addedSettings[] = array(
            'name' => __('Set completed', 'woocommerce-sisow'),
            'type' => 'checkbox',
            'desc' => __('Mark the order direct as completed', 'woocommerce-sisow'),
            'id' => 'sisow_completed',
        );
		$addedSettings[] = array( 
			'name' => __('Don\'t cancel', 'woocommerce-sisow'),
			'type' => 'checkbox',
			'desc' => __('Don\'t cancel the order after a failed payment', 'woocommerce-sisow'),
			'id' => 'sisow_nocancel',
		);
		$addedSettings[] = array(
            'name' => __('Add utm_nooverride=1', 'woocommerce-sisow'),
            'type' => 'checkbox',
            'desc' => __('Add utm_nooverride=1 recommended if you use Google Analytics', 'woocommerce-sisow'),
            'id' => 'sisow_utm_nooverride',
        );
		$addedSettings[] = array(
			'name' => __('Testmode', 'woocommerce-sisow'),
			'type' => 'checkbox',
			'desc' => __('Enable testmode for all payment methods', 'woocommerce-sisow'),
			'id' => 'sisow_general_test',
		);
        $addedSettings[] = array(
            'name' => __('Hide IBAN', 'woocommerce-sisow'),
            'type' => 'checkbox',
            'desc' => __('Hide customer IBAN in order notes', 'woocommerce-sisow'),
            'default' => 'no',
            'id' => 'iban_hide'
        );
		$addedSettings[] = array(
            'name' => __('Description', 'woocommerce-sisow'),
            'type' => 'text',
            'desc' => __('Description on the bank account, can be overwritten at every payment method. Use {orderid} to include order number.', 'woocommerce-sisow'),
            'id' => 'sisow_general_description',
        );
        $addedSettings[] = array(
            'name' => __('House number field (billing)', 'woocommerce-sisow'),
            'type' => 'text',
            'desc' => __('If the house number is saved as order note, enter the key name.', 'woocommerce-sisow'),
            'id' => 'sisow_general_housenumber',
        );
        $addedSettings[] = array(
            'name' => __('House number field (shipping)', 'woocommerce-sisow'),
            'type' => 'text',
            'desc' => __('If the house number is saved as order note, enter the key name.', 'woocommerce-sisow'),
            'id' => 'sisow_general_housenumber_shipping',
        );
        $addedSettings[] = array(
            'type' => 'sectionend',
            'id' => 'sisow_general_settings',
        );
        foreach ($settings as $setting)
        {
            if (isset($setting['id']) && $setting['id'] == 'payment_gateways_options' && $setting['type'] != 'sectionend')
            {
                $updatedSettings = array_merge($updatedSettings, $addedSettings);
            }
            $updatedSettings[] = $setting;
        }


        return $updatedSettings;
    }

	public static function _sisowReturn()
	{
		global $woocommerce;

        $utm_nooverride = array_key_exists('utm_nooverride', $_GET);

        $successPageStates = ['Success', 'Reservation', 'Pending', 'Open'];
        $currentStatus = $_GET['status'];

		if(in_array($currentStatus, $successPageStates))
		{
			$order = new WC_Order($_GET['ec']);
			$return_url = $order->get_checkout_order_received_url();

			wp_redirect($return_url . ($utm_nooverride ? '&utm_nooverride=1' : ''));
		}
		else {
            wp_redirect($woocommerce->cart->get_checkout_url() . ($utm_nooverride ? '&utm_nooverride=1' : ''));
        }
	}
	
	public static function _sisowNotify($return = false)
    {
		global $woocommerce;
		
		if(sha1($_GET['trxid'] . $_GET['ec'] . $_GET['status'] . get_option('sisow_merchantid') . get_option('sisow_merchantkey')) != $_GET['sha1'])
			exit('Invalid Notify');
		
		$order = new WC_Order($_GET['ec']);

		// do we need to update the order?
        add_filter( 'sisow_before_update_orderstatus', 'Sisow_Gateways::sisow_update_status', 1, 2 );

		Sisow_Gateways::updateOrderStatus($order);

		if(!$return)
			exit;
		else
			return;
	}

	public static function sisow_update_status($default, $order){
        if(empty($order)){
            return $default;
        }

        return true;//$order->get_status() == 'pending' || $order->get_status() == 'on-hold';
    }

	public static function _getStatus($orderCheckout, $order){
		return Sisow_Gateways::updateOrderStatus($order) == false;
	}
	
	private static function updateOrderStatus($order){
        $sisowOrder = get_post_meta($order->get_id(), '_sisow_order', true);

        if ($sisowOrder !== 'yes') {
            return true;
        }

        // validate if method uses Sisow gateway?
        $wc_gateways      = new WC_Payment_Gateways();
        $payment_gateways = $wc_gateways->get_available_payment_gateways();

        foreach( $payment_gateways as $gateway_id => $gateway ){
            if($gateway_id == $order->get_payment_method()){
                if($gateway instanceof Sisow_Gateway_Abstract == false){
                    return true;
                }
                break;
            }
        }

        // order update allowed?
        $update_orderstatus = apply_filters('sisow_before_update_orderstatus', true, $order);

        if($update_orderstatus === false){
            echo 'Order status not pending anymore';
            exit;
        }

        // get transaction ID's
        $orderTrxid = get_post_meta($order->get_id(), '_trxid', true);

        if(is_array($orderTrxid)){
            $trxIds = $orderTrxid;
        } else {
            $trxIds = [$orderTrxid];
        }

        // no trx id's cancel order, probably no Sisow order
        if(count($trxIds) == 0){
            return true;
        }

        // init Sisow class
        $sisow = new Sisow_Helper_Sisow(get_option('sisow_merchantid'), get_option('sisow_merchantkey'), get_option('sisow_shopid'));

        $trxSuccess = false;
        $trxOpen = false;
        $trxReservation = false;
        $trxPending = false;
        $consumerIban = '';
        $successTrxId = '';

        // loop transaction ID's
        foreach($trxIds as $trxId){
            // do Status Request
            if($sisow->StatusRequest($trxId) < 0)
            {
                $order->add_order_note(sprintf('Sisow StatusUpdate: StatusRequest failed (%s)', $sisow->errorCode));
                return false;
            }

            // set states
            switch($sisow->status){
                case "Success":
                    $trxSuccess = true;
                    $successTrxId = $trxId;
                    if(empty($consumerIban)){
                        $consumerIban = $sisow->consumerAccount;
                    }
                    break;
                case "Reservation":
                    $trxReservation = true;
                    break;
                case "Open":
                    $trxOpen = true;
                    break;
                case "Pending":
                    $trxPending = true;
                    break;
            }
        }

        if(!$trxSuccess && !$trxReservation && !$trxPending && $trxOpen){
            echo 'Still open payment(s)';
            exit;
        }

        if($trxSuccess) {
            // set order note
            if (!empty($consumerIban) && get_option('iban_hide') !== 'yes') {
                $order->add_order_note(sprintf(__('Status recieved from Sisow: %s, IBAN: %s', 'woocommerce-sisow'), 'Success', $consumerIban));
            } else {
                $order->add_order_note(sprintf(__('Status recieved from Sisow: %s', 'woocommerce-sisow'), 'Success'));
            }

            // mark oder as paid
            $order->payment_complete($successTrxId);

            // if necessary mark order as complete
            if (get_option('sisow_completed') == "yes") {
                $order->update_status('completed');
            }
        } else if($trxReservation) {
            $order->add_order_note(sprintf(__('Status recieved from Sisow: %s', 'woocommerce-sisow'), 'Reservation'));
            $order->payment_complete($successTrxId);

            $klarnaSettings = get_option('woocommerce_klarna_settings');

            if($klarnaSettings['makeinvoice'] == 'yes'){
                $sisow->InvoiceRequest($trxId);
            }
        } else if($trxPending || $trxOpen) {
            $order->update_status('on-hold', sprintf(__('Status recieved from Sisow: %s', 'woocommerce-sisow'), $sisow->status));
        } else {
            if (get_option('sisow_nocancel') != "yes") {
                $order->update_status('cancelled', sprintf(__('Status recieved from Sisow: %s', 'woocommerce-sisow'), $sisow->status));
            }
        }
		
		return true;
	}
}
