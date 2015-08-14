<?php


class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Enterprisevalidator extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Get the default HTML for this option
        $html = parent::_getElementHtml($element);

        // Set up additional JavaScript for our validation using jQuery.

        $jquery = '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>';

        if (! Mage::helper('ddg')->isEnterprise()) {
	        $html .=$jquery;
	        $javaScript = "<script type=\"text/javascript\">
	        jQuery.noConflict();
            jQuery(document).ready(function() {
				jQuery('#connector_data_mapping_enterprise_data-head').parent().hide();

            });
            </script>";
	        $html .= $javaScript;
        }


	    return $html;
    }
}