<?php

class Sisow_Gateway_ideal extends Sisow_Gateway_Abstract
{
    public static function getCode()
    {
        return "ideal";
    }

    public static function getName()
    {
        return "iDEAL";
    }

    public function payment_fields()
    {
        $sisow = new Sisow_Helper_Sisow(get_option('sisow_merchantid'), get_option('sisow_merchantkey'),
            get_option('sisow_shopid'));
        $banks = array();

        $testmode = get_option('sisow_general_test') == 'yes'; // get default test mode

        if (!$testmode) {
            $testmode = $this->get_option('testmode') == 'yes';
        }

        $sisow->DirectoryRequest($banks, false, $testmode);

        $description = '';

        $description_text = $this->get_option('description');
        if (!empty($description_text)) {
            $description .= '<p>' . $description_text . '</p>';
        }

        $description .= __('Choose your bank', 'woocommerce-sisow') . '<br/>';

        natcasesort($banks);

        if ($this->get_option('list') == 'yes') {
            foreach ($banks as $k => $v) {
                $description .= '<label style="display: block; margin-bottom: 10px;">
                                    <input type="radio" name="ideal_issuer" value="' . $k . '"/>
                                    <div style="display: inline-block; height: 25px; vertical-align: middle; margin-left: 5px;">
                                        <img style="height: 20px; float: "";" alt="' . $v . '" src="' . plugins_url('Images/banks/' . $k . '.png', dirname(__FILE__)) . '"/>
                                    </div>
                                </label>';
            }
        } else {
            $description .= '<select id="ideal_issuer" name="ideal_issuer" class="required-entry">';
            $description .= '<option value="">' . __('Please choose...', 'woocommerce-sisow') . '</option>';
            foreach ($banks as $k => $v) {
                $description .= '<option value="' . $k . '">' . $v . '</option>';
            }
            $description .= '</select>';
            $description .= '</p>';
        }
        echo $description;
    }

    public function validate_fields()
    {
        if (empty($_POST['ideal_issuer'])) {
            wc_add_notice(__('Please select your bank', 'woocommerce-sisow'), 'error');
            return false;
        }
        return true;
    }
}