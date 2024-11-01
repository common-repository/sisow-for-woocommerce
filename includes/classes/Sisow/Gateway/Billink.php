<?php

class Sisow_Gateway_Billink extends Sisow_Gateway_Abstract
{
	public static function NeedRedirect() { return false; }
	
	public static function getCode()
    {
        return "billink";
    }

    public static function getName()
    {
        return "Billink - Achteraf Betalen";
    }
	
	public static function canRefund()
	{
		return true;
	}

    public static function useCreditRefund()
    {
        return true;
    }
	
	public static function getWarning()
	{
		return array(
					'title'       => __( 'Warning', 'woocommerce-sisow' ),
					'type'        => 'title',
					'description' => __( 'An additional contract is required for this payment method, please contact <a href="mailto:sales@sisow.nl">sales@sisow.nl</a>.', 'woocommerce-sisow' ),
				);
	}
	
	public function payment_fields()
	{
		global $woocommerce;
		
		$description = $this->get_option('description');
		$description .= '<div class="woocommerce-billing-fields">';
		$description .= '<p>';
		$description .= '<label class="" for="billink_gender">' . __('Gender', 'woocommerce-sisow') . '<abbr class="required" title="verplicht">*</abbr></label>';
		$description .= '<select id="billink_gender" name="billink_gender">';
			$description .= '<option value="">-- ' . __('Please Choose', 'woocommerce-sisow') . ' --</option>';
			$description .= '<option value="m">'.__('Male', 'woocommerce-sisow').'</option>';
			$description .= '<option value="f">'.__('Female', 'woocommerce-sisow').'</option>';
		$description .= '</select>';
		$description .= '</p>';
		$description .= '<p>';
		$description .= '<label class="" for="billink_phone">' . __('Phone', 'woocommerce-sisow') . '<abbr class="required" title="verplicht">*</abbr></label>';
		$description .= '<input class="input-text" type="text" id="billink_phone" name="billink_phone"/>';
		$description .= '</p>';
		$description .= '<p>';
		$description .= '<label class="" for="billink_birthday_day">' . __('Birthdate', 'woocommerce-sisow') . '<abbr class="required" title="verplicht">*</abbr></label>';
		$description .= '<select id="billink_birthday_day" name="billink_birthday_day">';
			$description .= '<option value="">-- ' . __('Day', 'woocommerce-sisow') . ' --</option>';
			for($i = 1;$i < 32; $i++)
				$description .= '<option value="'.sprintf("%02d", $i).'">'.$i.'</option>';
		$description .= '</select>';
		$description .= '<select name="billink_birthday_month">';
			$description .= '<option value="">-- ' . __('Month', 'woocommerce-sisow') . ' --</option>';
			$description .= '<option value="01">'.__('January', 'woocommerce-sisow').'</option>';
			$description .= '<option value="02">'.__('February', 'woocommerce-sisow').'</option>';
			$description .= '<option value="03">'.__('March', 'woocommerce-sisow').'</option>';
			$description .= '<option value="04">'.__('April', 'woocommerce-sisow').'</option>';
			$description .= '<option value="05">'.__('May', 'woocommerce-sisow').'</option>';
			$description .= '<option value="06">'.__('June', 'woocommerce-sisow').'</option>';
			$description .= '<option value="07">'.__('July', 'woocommerce-sisow').'</option>';
			$description .= '<option value="08">'.__('August', 'woocommerce-sisow').'</option>';
			$description .= '<option value="09">'.__('September', 'woocommerce-sisow').'</option>';
			$description .= '<option value="10">'.__('October', 'woocommerce-sisow').'</option>';
			$description .= '<option value="11">'.__('November', 'woocommerce-sisow').'</option>';
			$description .= '<option value="12">'.__('December', 'woocommerce-sisow').'</option>';
		$description .= '</select>';
		$description .= '<select name="billink_birthday_year">';
			$description .= '<option value="">-- ' . __('Year', 'woocommerce-sisow') . ' --</option>';
			for($i = date("Y")-18;$i > date("Y") - 110; $i--)
					$description .= '<option value="'.$i.'">'.$i.'</option>';
		$description .= '</select>';
		$description .= '</p>';
		
		if($this->get_option('disableb2b') != 'yes'){
			$description .= '<p>';
			$description .= '<label class="" for="billink_coc">' . __('CoC number', 'woocommerce-sisow') . '<small> (' . __('Only for B2B', 'woocommerce-sisow') . ')</small></label>';
			$description .= '<input class="input-text" type="text" id="billink_coc" name="billink_coc"/><br/>';
			$description .= '</p>';
		}
		$description .= __('Fields with an * are required', 'woocommerce-sisow');
								
		$description .= '</div>';
		echo $description;
	}
	
	public function validate_fields() 
	{ 
		$validated = true;
		if(empty($_POST['billink_gender']))
		{
			wc_add_notice( __('Please select your gender', 'woocommerce-sisow'), 'error' );
			$validated = false; 
		}
		
		if(empty($_POST['billink_phone']))
		{
			wc_add_notice( __('Please fill in your phonenumber', 'woocommerce-sisow'), 'error' );
			$validated = false; 
		}
		
		if(empty($_POST['billink_birthday_day']) || empty($_POST['billink_birthday_month']) || empty($_POST['billink_birthday_year']))
		{
			wc_add_notice( __('Please select your birthdate', 'woocommerce-sisow'), 'error' );
			$validated = false; 
		}
		
		return $validated; 
	}
}