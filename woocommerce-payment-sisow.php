<?php

/**
 * Plugin Name: Woocommerce Sisow Payment Methods
 * Plugin URI: https://wordpress.org/plugins/sisow-for-woocommerce/
 * Description: Sisow payment methods for woocommerce
 * Version: 5.5.8
 * Author: Sisow
 * Author URI: http://www.sisow.nl
 * Requires at least: 3.0.1
 * Tested up to: 5.7.2
 * WC requires at least: 3.0.0
 * WC tested up to: 5.3.0
 *
 * Text Domain: woocommerce-sisow
 * Domain Path: /languages/
 */
 
//Autoloader laden en registreren
require_once dirname(__FILE__) . '/includes/classes/Autoload.php';

//plugin functies inladen
require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

//textdomain inladen
load_plugin_textdomain( 'woocommerce-sisow', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );

function error_woocommerce_not_active() {
    echo '<div class="error"><p>' . __('The Sisow payment methods plugin requires woocommerce to be active', 'woocommerce-sisow') . '</p></div>';
}

function error_curl_not_installed() {
    echo '<div class="error"><p>' . __('Curl is not installed.<br />In order to use the Sisow payment methods, you must install install CURL.<br />Ask your system administrator to install php_curl', 'woocommerce-sisow') . '</p></div>';
}

function order_status_changed( $order_id, $old_status, $new_status)
{
    $order = new WC_Order( $order_id );

    $wc_gateways      = new WC_Payment_Gateways();
    $payment_gateways = $wc_gateways->get_available_payment_gateways();

    $payment_gateway = null;
    foreach( $payment_gateways as $gateway_id => $gateway ){
        if($gateway_id == $order->get_payment_method()){
            if($gateway instanceof Sisow_Gateway_Abstract){
                $payment_gateway = $gateway;
            }
            break;
        }
    }

    if (is_null($payment_gateway)) // No Sisow payment method
        return;

    $setOnStatus = $payment_gateway->get_option('invoiceonstatus');

    if ($setOnStatus == false || $setOnStatus == 'default' || $old_status == $new_status)
        return;

    $status = ( strpos ($new_status, 'wc-') == 0 ? 'wc-'.$new_status : $new_status);

    if ($status != $setOnStatus)
        return;

    $transactionId = null;
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

    if (is_null($transactionId))
        return;

    $sisow = new Sisow_Helper_Sisow(get_option('sisow_merchantid'), get_option('sisow_merchantkey'), get_option('sisow_shopid'));

    if (($ex = $sisow->InvoiceRequest($transactionId)) < 0) {
        $order->add_order_note( sprintf( __( 'Error creating invoice', 'woocommerce' ) ));
    } else {
        $order->add_order_note( sprintf( __( 'Invoice created', 'woocommerce' ) ));
    }
}

// Curl is niet geinstalleerd. foutmelding weergeven
if (!function_exists('curl_version')) {
    add_action('admin_notices', 'error_curl_not_installed');
}

//Autoloader registreren
Sisow_Autoload::register();

if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    //Gateways van Sisow aan woocommerce koppelen
    Sisow_Gateways::register();

    //Globale settings van Sisow aan woocommerce koppelen
    Sisow_Gateways::addSettings();

    //Return en Notify functies koppelen aan de woocommerce API
    Sisow_Gateways::registerApi();

    add_action( 'woocommerce_order_status_changed', 'order_status_changed', 99, 3 );
} else {
    // Woocommerce is niet actief. foutmelding weergeven
    add_action('admin_notices', 'error_woocommerce_not_active');
}

