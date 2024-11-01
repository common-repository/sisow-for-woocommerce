<?php


class Sisow_Gateway_Klarna extends Sisow_Gateway_Abstract
{
    public static function getCode()
    {
        return "klarna";
    }

    public static function getName()
    {
        return __("Klarna Pay Later", 'woocommerce-sisow');
    }

    public function getIcon()
    {
        return 'https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg';
    }

    public static function canRefund()
    {
        return true;
    }

    public static function useCreditRefund()
    {
        return true;
    }
}