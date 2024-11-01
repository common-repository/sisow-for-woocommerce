<?php
class Sisow_Gateway_Abstract extends WC_Payment_Gateway
{
	public static function NeedRedirect() { return true; }
	
	public static function getCode()
    {
        throw new Exception('Please implement the getCode method');
    }

    public static function getName()
    {
        throw new Exception('Please implement the getName method');
    }
	
	public static function getMerchantId()
    {
        return get_option('sisow_merchantid');
    }

    public static function getMerchantKey()
    {
        return get_option('sisow_merchantkey');
    }
	
	public static function getShopId()
    {
        return get_option('sisow_shopid');
    }
	
	public static function getWarning()
	{
		return null;
	}
	
	public static function canRefund()
	{
		return true;
	}

    public static function useCreditRefund()
    {
        return false;
    }
		
	public static function addScript(){}
	
	public function getIcon()
    {
		$code = $this->getCode();
		if($code == 'afterpayb2b')
			$code = 'afterpay';
		
		return plugins_url( 'Images/'.$code.'.png', dirname(__FILE__) );
    }
	
	public function __construct()
    {

        $this->id = $this->getCode();
        $this->icon = $this->getIcon();
        $this->has_fields = true;
        $this->method_title = 'Sisow - ' . $this->getName();
        $this->method_description = sprintf(__('Activate this module to accept %s transactions', 'woocommerce-sisow'), $this->getName());
		
		if($this->canRefund())
			$this->supports = array('products', 'refunds');
		else
			$this->supports = array('products');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
		
		if($this->get_option('enabled') == 'yes' && !is_admin())
			$this->addScript();
		
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }
	
	public function is_available() {
		if(!parent::is_available()) {
            return false;
        }
		
		$floatPattern = "/^[0-9]*.[0-9]*$/";
		
		$minAmount = $this->get_option('min_amount', 0);
		$maxAmount = $this->get_option('max_amount', 0);
		
		// replace , with .
		$minAmount = str_replace(',', '.', $minAmount);
		$maxAmount = str_replace(',', '.', $maxAmount);
		
		// validate if given strings are number
		if(!preg_match($floatPattern, $minAmount) || !preg_match($floatPattern, $maxAmount))
			return true;
		
		// both amounts zero or max amount lower than min amount, return true
		if($minAmount == 0 && $maxAmount == 0)
			return true;
		else if($minAmount > 0 && $maxAmount > 0) {
            return $minAmount <= $this->get_order_total() && $maxAmount >= $this->get_order_total();
        }
		else if($minAmount > 0){
            return $minAmount <= $this->get_order_total();
        }
		else if($maxAmount > 0){
            return $maxAmount >= $this->get_order_total();
        }
		else {
		    // should never reach this
		    return true;
        }
	}
	
	public function init_form_fields()
    {
		$this->form_fields = array();
		
		$this->form_fields['docs'] = array(
			'title'       => __( 'Documentation', 'woocommerce-sisow' ),
			'type'        => 'title',
			'description' => __( '<a href="https://www.sisow.nl/Sisow/down.aspx?doc=HandleidingWooCommerce.pdf" target="_blank">Click to open documentation</a>.', 'woocommerce-sisow' )
			);
		
		$warning = $this->getWarning();
		
		if(is_array($warning))
		{
			$this->form_fields['warning'] = $warning;
		}
		$this->form_fields['enabled'] = array(
				'title' => __('Enable/Disable', 'woocommerce'),
				'type' => 'checkbox',
				'label' => sprintf(__('Enable Sisow %s', 'woocommerce-sisow'), $this->getName()),
				'default' => 'no'
			);
						
		$this->form_fields['title'] = array(
				'title' => __('Title', 'woocommerce'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
				'default' => $this->getName(),
				'desc_tip' => true,
			);
			
		$this->form_fields['description'] = array(
				'title' => __('Customer Message', 'woocommerce'),
				'type' => 'textarea',
				'default' => sprintf(__('Pay with %s', 'woocommerce-sisow'), $this->getName()),
			);
			
		$this->form_fields['description_bankaccount'] = array(
				'title' => __('Description', 'woocommerce'),
				'type' => 'textarea',
				'desc' => __('Description on the bank account', 'woocommerce-sisow'),
				'default' => get_bloginfo('name') . ': {orderid}'
			);
					
		$this->form_fields['testmode'] = array( 
				'title' => __('Testmode', 'woocommerce-sisow'),
				'type' => 'checkbox',
				'label' => __('Enable testmode', 'woocommerce-sisow'),
				'default' => 'no',
			);
			
		$this->form_fields['min_amount'] = array(
				'title' => __('Min amount', 'woocommerce-sisow'),
				'type' => 'text',
				'description' => sprintf(__('Minimum order total for %s', 'woocommerce-sisow'), $this->getName()),
			);
			
		$this->form_fields['max_amount'] = array(
				'title' => __('Max amount', 'woocommerce-sisow'),
				'type' => 'text',
				'description' => sprintf(__('Maximum order total for %s', 'woocommerce-sisow'), $this->getName()),
			);
			
		if($this->getCode() == 'ideal'){
			$this->form_fields['list'] = array( 
				'title' => __('Display banks in list', 'woocommerce-sisow'),
				'type' => 'checkbox',
				'label' => __('Banks in list', 'woocommerce-sisow'),
				'default' => 'no',
			);
		}

		if($this->getCode() == 'afterpay' || $this->getCode() == 'focum' || $this->getCode() == 'klarna')
		{
			$this->form_fields['makeinvoice'] = array(
				'title' => sprintf(__('Make %s invoice', 'woocommerce-sisow'), $this->get_option('title')),
				'type' => 'checkbox',
				'label' => sprintf(__('Create %s invoice', 'woocommerce-sisow'), $this->get_option('title')),
				'default' => 'no',
			);
		}
		else if($this->getCode() == 'billink')
		{
			$this->form_fields['disableb2b'] = array(
				'title' => __('Disable B2B', 'woocommerce-sisow'),
				'type' => 'checkbox',
				'label' => __('Disable B2B transactions', 'woocommerce-sisow'),
				'default' => 'no',
			);
		}
		else if($this->id == 'overboeking' || $this->id == 'ebill')	
		{
			$this->form_fields['days'] = array(
				'title' => __('Days', 'woocommerce-sisow'),
				'type' => 'text',
				'default' => 30,
			);
			
			$label = $this->id == 'overboeking' ? __('Include paylink', 'woocommerce-sisow') : __('Include bank account details', 'woocommerce-sisow');
			
			$this->form_fields['including'] = array(
				'title' => __('Include', 'woocommerce-sisow'),
				'type' => 'checkbox',
				'label' => $label,
				'default' => 'no',
			);
			
			$this->form_fields['mailadmin'] = array( 
				'title' => __('Mail to admin', 'woocommerce-sisow'),
				'type' => 'checkbox',
				'label' => __('Send e-mail to shop administrator instead of consumer', 'woocommerce-sisow'),
				'default' => 'no',
			);
		}
        if(function_exists('wc_get_order_statuses') && ($this->getCode() == 'afterpay' || $this->getCode() == 'klarna'))
        {
            $order_statuses = wc_get_order_statuses();
            $statuses = array('default' => __('- Directly -', 'woocommerce-sisow'));
            $statuses = array_merge($statuses, $order_statuses);

            $this->form_fields['invoiceonstatus'] = array(
                'title' => __('Create invoice at order status', 'woocommerce-sisow'),
                'type' => 'select',
                'label' => sprintf(__('Create %s invoice when WooCommerce order status is set to.', 'woocommerce-sisow'), $this->get_option('title')),
                'default' => '',
                'options' => $statuses,
                'description' => sprintf(__('Only active when "Make %s invoice" set to active', 'woocommerce-sisow'), $this->get_option('title')),
            );
        }

    }

	public function payment_fields()
    {
        $description = $this->get_option('description');
        echo $description;
    }
	
	public function process_payment($order_id)
    {
		/** @var $wpdb wpdb The database */
        global $wpdb;
        global $woocommerce;
        $order = new WC_Order($order_id);
				
		$arg = array();
		
		if(method_exists($order, 'get_shipping_first_name'))
		{
			$arg = $this->GetWooCommerce3Data($order);
		}
		else
		{
			$arg = $this->GetWooCommerce2Data($order);
		}
		
		// get testmode
		$testmode = get_option('sisow_general_test') == 'yes'; // get default test mode
		
		if(!$testmode)
			$testmode = $this->get_option('testmode') == 'yes';
		
		if($testmode)
			$arg['testmode'] = 'true';

        $setOnStatus = $this->get_option('invoiceonstatus');

		if( ($setOnStatus == false || $setOnStatus == 'default')  && $this->get_option('makeinvoice') == 'yes')
			$arg['makeinvoice'] = 'true';
		
		if($this->get_option('including') == 'yes')
			$arg['including'] = 'true';
		
		if($this->get_option('days') > 0)
			$arg['days'] = $this->get_option('days');

		if(array_key_exists($this->getCode() . '_bic', $_POST))
			$arg['bic'] = $_POST[$this->getCode() . '_bic'];
		
		if(array_key_exists($this->getCode() . '_gender', $_POST))
			$arg['gender'] = $_POST[$this->getCode() . '_gender'];
		
		if(array_key_exists($this->getCode() . '_iban', $_POST))
			$arg['iban'] = $_POST[$this->getCode() . '_iban'];
		
		if(array_key_exists($this->getCode() . '_coc', $_POST))
			$arg['billing_coc'] = $_POST[$this->getCode() . '_coc'];
				
		if(array_key_exists($this->getCode() . '_birthday_day', $_POST) && array_key_exists($this->getCode() . '_birthday_month', $_POST) && array_key_exists($this->getCode() . '_birthday_year', $_POST))
			$arg['birthdate'] = $_POST[$this->getCode() . '_birthday_day'] . $_POST[$this->getCode() . '_birthday_month'] . $_POST[$this->getCode() . '_birthday_year'];
		
		$sisow = new Sisow_Helper_Sisow(get_option('sisow_merchantid'), get_option('sisow_merchantkey'), get_option('sisow_shopid'));
        $sisow->purchaseId = ltrim($order->get_order_number(), '#');
		
		// get description
		$description = $this->get_option('description_bankaccount');
		
		if(empty($description))
			$description = get_option('sisow_general_description');
		
		if(empty($description))
			$description = get_bloginfo('name') . ' ' . $order->get_order_number();
		
		$description = str_replace('{orderid}', $order->get_order_number(), $description);
		
        $sisow->description = $description;
        $sisow->amount = $order->get_total();
		
		$code = $this->getCode();
		
		if($code == 'afterpayb2b')
			$code = 'afterpay';
        $sisow->payment = $code;
        $sisow->entranceCode = $order_id;

        $sisow->returnUrl = add_query_arg('wc-api', 'Wc_Sisow_Gateway_Return', home_url('/')) . (get_option('sisow_utm_nooverride') == 'yes' ? '&utm_nooverride=1' : '');
        $sisow->cancelUrl = $order->get_cancel_order_url() . (get_option('sisow_utm_nooverride') == 'yes' ? '&utm_nooverride=1' : '');
        $sisow->notifyUrl = add_query_arg('wc-api', 'Wc_Sisow_Gateway_Notify', home_url('/'))  . (get_option('sisow_utm_nooverride') == 'yes' ? '&utm_nooverride=1' : '');
		$sisow->callbackUrl = add_query_arg('wc-api', 'Wc_Sisow_Gateway_Notify', home_url('/'))  . (get_option('sisow_utm_nooverride') == 'yes' ? '&utm_nooverride=1' : '');
		if(array_key_exists($this->getCode() . '_issuer', $_POST))
			$sisow->issuerId = $_POST[$this->getCode() . '_issuer'];
		
		if( ($ex = $sisow->TransactionRequest($arg)) < 0 )
		{
			if($this->getCode() == 'focum')
				wc_add_notice( 'Betalen met Focum Achteraf Betalen is op dit moment niet mogelijk, betaal anders.', 'error' );
			else if($this->getCode() == 'billink')
				wc_add_notice( 'Betalen met Billink is op dit moment niet mogelijk, betaal anders.', 'error' );
			else if($this->getCode() == 'capayable')
				wc_add_notice( 'Helaas kunt u geen gebruik maken van in3. Het is natuurlijk wel mogelijk om voor een andere betaalmethode te kiezen. Mocht u toch meer informatie willen? Dan kunt u contact opnemen met de klantenservice van in3. Dit kan per e-mail via klantenservice@payin3.nl of telefonisch via 088 – 3993 333.', 'error' );
			else if($code == 'afterpay')
				wc_add_notice( 'Het spijt ons u te moeten mededelen dat uw aanvraag om uw bestelling achteraf te betalen op dit moment niet door AfterPay wordt geaccepteerd. Dit kan om diverse (tijdelijke) redenen zijn.Voor vragen over uw afwijzing kunt u contact opnemen met de Klantenservice van AfterPay. Of kijk op de website van AfterPay bij “Veel gestelde vragen” via de link http://www.afterpay.nl/page/consument-faq onder het kopje “Gegevenscontrole”. Wij adviseren u voor een andere betaalmethode te kiezen om alsnog de betaling van uw bestelling af te ronden.', 'error' );
			else
				wc_add_notice( 'Er is een fout opgetreden bij het starten van de transactie, probeer het opnieuw of kies een andere betaalmogelijkheid', 'error' );

            $order->update_status('cancelled', 'Sisow fout: ' . $ex . ' ' . $sisow->errorCode. ' ' . $sisow->errorMessage);
		}
		else
		{
            // get transaction ID's
            $trxIds = get_post_meta($order_id, '_trxid', true);

            if(empty($trxIds)){
                $trxIds = array();
            }

            $trxIds[] = $sisow->trxId;

			update_post_meta($order_id, '_trxid', $trxIds);

			if($this->getCode() == 'overboeking' || $this->getCode() == 'ebill')
				$order->update_status('on-hold', sprintf(__('%s created', 'woocommerce-sisow'), $this->getName()));
			else if($this->getCode() == 'focum' || $code == 'afterpay' || $code == 'billink'){
				$order->payment_complete($sisow->trxId);

                if(($arg['makeinvoice'] == 'true' || $code == 'billink' ) && get_option('sisow_completed') == "yes"){
					$order->update_status('completed');
				}
			}

			if($this->NeedRedirect())
				return array('result' => 'success', 'redirect' => $sisow->issuerUrl);
			else
			{
				// Return thankyou redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> $this->get_return_url( $order )
				);
			}
		}
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) 
	{
        if (!$this->canRefund()) {
            return false;
        }

		$order = wc_get_order( $order_id );
		$sisow = new Sisow_Helper_Sisow(get_option('sisow_merchantid'), get_option('sisow_merchantkey'), get_option('sisow_shopid'));

		if(is_null($amount) || $amount < 0.01){
            $order->add_order_note( sprintf( __( 'Can\'t refund amount of 0.00', 'woocommerce' ) ));
            return false;
        }

        if (!empty($order->get_transaction_id())) {
            $transactionId = $order->get_transaction_id();
        } else {
            $trxIds = get_post_meta($order_id, '_trxid', true);
            if (is_array($trxIds) && count($trxIds) > 0) {
                $transactionId = $trxIds[0];
            } else {
                $transactionId = $trxIds;
            }
        }

        $sisow->amount = $amount;

        if($this->useCreditRefund())
        {
            $posts = array();
            $posts['tax'] = 2100;
            $posts['exclusive'] = 'false';
            $posts['description'] = 'refund';

            if ($sisow->CreditInvoiceRequest($transactionId, $posts) < 0 ) {
                $order->add_order_note(sprintf(__('Error while adding Sisow refund', 'woocommerce')) . ' (' . $sisow->errorCode . ', ' . $sisow->errorMessage . ')');
                return false;
            } else {
                if (empty($sisow->invoiceNo) && empty($sisow->documentId)) {
                    $order->add_order_note(sprintf(__('Refunded %s (Sisow amount: %s)', 'woocommerce'), $amount, $sisow->amount));
                } else {
                    $order->add_order_note(sprintf(__('Refunded %s (Sisow amount: %s) - Invoice No: %s, Document ID: %s', 'woocommerce'), $amount, $sisow->amount, $sisow->invoiceNo ?: "-", $sisow->documentId ?: "-"));
                }
                return true;
            }
        }
        else {
            $refundid = $sisow->RefundRequest($transactionId);

            if ($refundid > 0) {
                $order->add_order_note(sprintf(__('Refunded %s (Sisow amount: %s) - Refund ID: %s', 'woocommerce'), $amount, $sisow->amount, $refundid));
                return true;
            } else {
                $order->add_order_note(sprintf(__('Error while adding Sisow refund', 'woocommerce')) . ' (' . $sisow->errorCode . ', ' . $sisow->errorMessage . ')');
                return false;
            }
        }
	}
	
	private function GetWooCommerce2Data($order)
	{
		$arg = array();

		$keyName = get_option('sisow_general_housenumber');
		$houseNumber = empty($keyName) ? '' : get_post_meta($order->get_id(), $keyName, true);
		
		//add Shipping Address
		$arg['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		$arg['shipping_firstname'] = $order->shipping_first_name;
		$arg['shipping_lastname'] = $order->shipping_last_name;
		$arg['shipping_mail'] = $this->get_option('mailadmin') == 'yes' ? get_bloginfo('admin_email') : $order->billing_email;
		$arg['shipping_company'] = $order->shipping_company;
		$arg['shipping_address1'] = empty($houseNumber) ? $order->shipping_address_1 : $order->shipping_address_1 . ' ' . $houseNumber;
		$arg['shipping_address2'] = $order->shipping_address_2;
		$arg['shipping_zip'] = $order->shipping_postcode;
		$arg['shipping_city'] = $order->shipping_city;
		$arg['shipping_countrycode'] = $order->shipping_country;
		$arg['shipping_phone'] = array_key_exists($this->getCode() . '_phone', $_POST) ? $_POST[$this->getCode() . '_phone'] : $order->billing_phone;
		
		//add Billing Address
		$arg['billing_firstname'] = $order->billing_first_name;
		$arg['billing_lastname'] = $order->billing_last_name;
		$arg['billing_mail'] = $this->get_option('mailadmin') == 'yes' ? get_bloginfo('admin_email') : $order->billing_email;
		$arg['billing_company'] = $order->billing_company;
		$arg['billing_address1'] = $order->billing_address_1;
		$arg['billing_address2'] = $order->billing_address_2;
		$arg['billing_zip'] = $order->billing_postcode;
		$arg['billing_city'] = $order->billing_city;
		$arg['billing_countrycode'] = $order->billing_country;
		$arg['billing_phone'] = array_key_exists($this->getCode() . '_phone', $_POST) ? $_POST[$this->getCode() . '_phone'] : $order->billing_phone;
		
		//$arg['weight'] = $order->;
		$arg['shipping'] = $order->order_shipping;
		//$arg['handling'] = $order->;
		//$arg['birthdate'] = $order->;
		$arg['tax'] = round($order->order_tax * 100.0);
		$arg['currency'] = $order->order_currency;
		
		//producten
        $item_loop = 0;
        if (sizeof($order->get_items()) > 0) : foreach ($order->get_items() as $item) :
                if ($item['qty']) :

                    $item_loop++;

                    $product = wc_get_product($item['product_id']);

                    $_tax = new WC_Tax();

                    foreach ($_tax->get_shop_base_rate($product->tax_class) as $line_tax) {
                        $tax = $line_tax['rate'];
                    }

                    $arg['product_id_' . $item_loop] = $item['product_id'];
                    $arg['product_description_' . $item_loop] = $item['name'];
                    $arg['product_quantity_' . $item_loop] = $item['qty'];
                    $arg['product_netprice_' . $item_loop] = round($product->get_price_excluding_tax(), 2) * 100;
                    $arg['product_total_' . $item_loop] = round($item['line_total'] + $item['line_tax'], 2) * 100;
                    $arg['product_nettotal_' . $item_loop] = round($item['line_total'], 2) * 100;
                    $arg['product_tax_' . $item_loop] = round($item['line_tax'], 2) * 100;
                    $arg['product_taxrate_' . $item_loop] = (!isset($tax)) ? 0 : round($tax, 2) * 100;
                    $arg['product_weight_' . $item_loop] = round($product->weight, 2) * 100;
                    $arg['product_type_'. $item_loop] = 'physical';
                endif;
            endforeach;
        endif;
		
		//verzendkosten
        if (isset($order->order_shipping)) {
            if ($order->order_shipping > 0) {
                $item_loop++;
                $arg['product_id_' . $item_loop] = 'shipping';
                $arg['product_description_' . $item_loop] = 'Verzendkosten';
                $arg['product_quantity_' . $item_loop] = '1';
                $arg['product_netprice_' . $item_loop] = round($order->order_shipping, 2) * 100;
                $arg['product_total_' . $item_loop] = round($order->order_shipping + $order->order_shipping_tax, 2) * 100;
                $arg['product_nettotal_' . $item_loop] = round($order->order_shipping, 2) * 100;
                $arg['product_tax_' . $item_loop] = round($order->order_shipping_tax, 2) * 100;
                $arg['product_taxrate_' . $item_loop] = round((($arg['product_tax_' . $item_loop] * 100.0) / $arg['product_nettotal_' . $item_loop])) * 100;
                $arg['product_type_'. $item_loop] = 'shipping_fee';
            }
        }
		
		//fees
		foreach($order->get_fees() as $fee)
		{			
			$item_loop++;
			$arg['product_id_' . $item_loop] = 'fee' . $item_loop;
			$arg['product_description_' . $item_loop] = $fee['name'];
			$arg['product_quantity_' . $item_loop] = '1';
			$arg['product_netprice_' . $item_loop] = round($fee['line_total'], 2) * 100;
			$arg['product_total_' . $item_loop] = round($fee['line_total'] + $fee['line_tax'], 2) * 100;
			$arg['product_nettotal_' . $item_loop] = round($fee['line_total'], 2) * 100;
			$arg['product_tax_' . $item_loop] = round($fee['line_tax'], 2) * 100;
			$arg['product_taxrate_' . $item_loop] = round((($arg['product_tax_' . $item_loop] * 100.0) / $arg['product_nettotal_' . $item_loop])) * 100;
            $arg['product_type_'. $item_loop] = 'surcharge';
		}
		
		return $arg;
	}	
	
	private function GetWooCommerce3Data($order)
	{
		$arg = array();

		// get house number
        $keyName = get_option('sisow_general_housenumber');
        $houseNumber = empty($keyName) ? '' : get_post_meta($order->get_id(), $keyName, true);

		//add Shipping Address
		$arg['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		$arg['shipping_firstname'] = $order->get_shipping_first_name();
		$arg['shipping_lastname'] = $order->get_shipping_last_name();
		$arg['shipping_mail'] = $this->get_option('mailadmin') == 'yes' ? get_bloginfo('admin_email') : $order->get_billing_email();
		$arg['shipping_company'] = $order->get_shipping_company();
		$arg['shipping_address1'] = empty($houseNumber) ? $order->get_shipping_address_1() : $order->get_shipping_address_1() . ' ' . $houseNumber;
		$arg['shipping_address2'] = $order->get_shipping_address_2();
		$arg['shipping_zip'] = $order->get_shipping_postcode();
		$arg['shipping_city'] = $order->get_shipping_city();
		$arg['shipping_countrycode'] = $order->get_shipping_country();
		$arg['shipping_phone'] = array_key_exists($this->getCode() . '_phone', $_POST) ? $_POST[$this->getCode() . '_phone'] : $order->get_billing_phone();

        // get house number
        $keyName = get_option('sisow_general_housenumber_shipping');
        $houseNumber = empty($keyName) ? '' : get_post_meta($order->get_id(), $keyName, true);

		//add Billing Address
		$arg['billing_firstname'] = $order->get_billing_first_name();
		$arg['billing_lastname'] = $order->get_billing_last_name();
		$arg['billing_mail'] = $this->get_option('mailadmin') == 'yes' ? get_bloginfo('admin_email') : $order->get_billing_email();
		$arg['billing_company'] = $order->get_billing_company();
		$arg['companyname'] = $order->get_billing_company();
		$arg['billing_address1'] = empty($houseNumber) ? $order->get_billing_address_1() : $order->get_billing_address_1() . ' ' . $houseNumber;
		$arg['billing_address2'] = $order->get_billing_address_2();
		$arg['billing_zip'] = $order->get_billing_postcode();
		$arg['billing_city'] = $order->get_billing_city();
		$arg['billing_countrycode'] = $order->get_billing_country();
		$arg['billing_phone'] = array_key_exists($this->getCode() . '_phone', $_POST) ? $_POST[$this->getCode() . '_phone'] : $order->get_billing_phone();
		
		//$arg['weight'] = $order->;
		$arg['shipping'] = round($order->get_shipping_total() * 100.0);
		//$arg['handling'] = $order->;
		//$arg['birthdate'] = $order->;
		$arg['tax'] = round($order->get_cart_tax() * 100.0);
		$arg['currency'] = $order->get_currency();
		
		//producten
        $item_loop = 0;
        if (sizeof($order->get_items()) > 0) : foreach ($order->get_items() as $item) :
                if ($item['qty']) :

                    $item_loop++;

                    if ($item['variation_id']) {
                        $product = wc_get_product($item['variation_id']);
                    } else {
                        $product = wc_get_product($item['product_id']);
                    }

					if($product){
						$_tax = new WC_Tax();
						foreach ($_tax->get_shop_base_rate($product->get_tax_class()) as $line_tax) {
							$tax = $line_tax['rate'];
						}
						
						$arg['product_weight_' . $item_loop] = round($product->get_weight(), 2) * 100;
					}else{
						$tax = 0;
					}
					
                    $arg['product_id_' . $item_loop] = $item['product_id'];
                    $arg['product_description_' . $item_loop] = $item['name'];
                    $arg['product_quantity_' . $item_loop] = $item['qty'];
                    $arg['product_netprice_' . $item_loop] = round($item['line_total'] / $item['qty'], 2) * 100;
                    $arg['product_total_' . $item_loop] = round($item['line_total'] + $item['line_tax'], 2) * 100;
                    $arg['product_nettotal_' . $item_loop] = round($item['line_total'], 2) * 100;
                    $arg['product_tax_' . $item_loop] = round($item['line_tax'], 2) * 100;
                    $arg['product_taxrate_' . $item_loop] = (!isset($tax)) ? 0 : round($tax, 2) * 100;
                    $arg['product_type_'. $item_loop] = $product->get_virtual() ? 'digital' : 'physical';
                endif;
            endforeach;
        endif;
		
		//verzendkosten
		$shipping = $order->get_shipping_total();
		if ($shipping > 0) {
			$item_loop++;
			$arg['product_id_' . $item_loop] = 'shipping';
			$arg['product_description_' . $item_loop] = 'Verzendkosten';
			$arg['product_quantity_' . $item_loop] = '1';
			$arg['product_netprice_' . $item_loop] = round($order->get_shipping_total(), 2) * 100;
			$arg['product_total_' . $item_loop] = round($order->get_shipping_total() + $order->get_shipping_tax(), 2) * 100;
			$arg['product_nettotal_' . $item_loop] = round($order->get_shipping_total(), 2) * 100;
			$arg['product_tax_' . $item_loop] = round($order->get_shipping_tax(), 2) * 100;
			$arg['product_taxrate_' . $item_loop] = round((($arg['product_tax_' . $item_loop] * 100.0) / $arg['product_nettotal_' . $item_loop])) * 100;
            $arg['product_type_'. $item_loop] = 'shipping_fee';
		}
		
		//fees
		foreach($order->get_fees() as $fee)
		{			
			$taxrate = 0;
			$item_loop++;
			
			$taxes = $fee->get_taxes();
			
			if(is_array($taxes) && is_array($taxes["total"]) && count($taxes["total"]) == 1)
			{
				$taxrate = ($taxes["total"]["1"] * 100.0) / $fee->get_total();
			}
			
			$arg['product_id_' . $item_loop] = 'fee' . $item_loop;
			$arg['product_description_' . $item_loop] = $fee->get_name();
			$arg['product_quantity_' . $item_loop] = $fee->get_quantity();
			$arg['product_netprice_' . $item_loop] = round($fee->get_total(), 2) * 100;
			$arg['product_total_' . $item_loop] = round($fee->get_total() + $fee->get_total_tax(), 2) * 100;
			$arg['product_nettotal_' . $item_loop] = round($fee->get_total(), 2) * 100;
			$arg['product_tax_' . $item_loop] = round($fee->get_total_tax(), 2) * 100;
			$arg['product_taxrate_' . $item_loop] = round($taxrate) * 100;
            $arg['product_type_'. $item_loop] = 'surcharge';
		}
				
		return $arg;
	}
}