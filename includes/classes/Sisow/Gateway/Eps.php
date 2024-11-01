<?php

class Sisow_Gateway_Eps extends Sisow_Gateway_Abstract
{
	public static function getCode()
    {
        return "eps";
    }

    public static function getName()
    {
        return "EPS";
    }
	
	public static function canRefund()
	{
		return false;
	}
	
	public static function addScript()
	{
		wp_enqueue_script( "sisow-eps-script", plugins_url( 'JS/eps.min.js', dirname(__FILE__) ), array('jquery'));
		wp_enqueue_style( "sisow-eps-css", plugins_url( 'CSS/eps.min.css', dirname(__FILE__) ));
	}
	
	public function payment_fields()
    {
		$description = '';
		
		$description_text = $this->get_option('description');
		if(!empty($description_text))
			$description .= '<p>' . $description_text . '</p>';
		
		$description .= '<p>';
		$description .= 'Mit eps Online-&Uuml;berweisung zahlen Sie einfach, schnell und sicher im Online-Banking Ihrer Bank. Im n&auml;chsten Schritt werden Sie direkt zum Online-Banking Ihrer Bank weitergeleitet, wo Sie die Zahlung durch Eingabe von PIN und TAN freigeben.';
		$description .= '</p>';	
		$description .= '<p>';
		$description .= __('Bankcode', 'woocommerce-sisow') . '<br/>';
		$description .= '<input id="eps_widget" name="eps_bic" type="text" autocomplete="off" />';
		$description .= '</p>';	
		
		$description .= "<script>var j = jQuery.noConflict();
        j(document).ready(function() {
          j('#eps_widget').eps_widget({'return': 'bic'});
        });</script>";
				
        echo $description;
    }
	
	public function validate_fields() 
	{ 
		if(empty($_POST['eps_bic']))
		{
			wc_add_notice( __('Please insert a bank code', 'woocommerce-sisow'), 'error' );
			return false; 
		}
		return true; 
	}
}