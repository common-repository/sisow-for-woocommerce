<?php

class Sisow_Gateway_Spraypay extends Sisow_Gateway_Abstract
{
    public static function getCode()
    {
        return "spraypay";
    }

    public static function getName()
    {
        return "Spraypay";
    }

    public static function getWarning()
    {
        return array(
            'title'       => __( 'Warning', 'woocommerce-sisow' ),
            'type'        => 'title',
            'description' => __( 'An additional contract is required for this payment method, please contact <a href="mailto:sales@sisow.nl">sales@sisow.nl</a>.', 'woocommerce-sisow' ),
        );
    }
}