<?php


class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Smtpvalidator extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Get the default HTML for this option
        $html = parent::_getElementHtml($element);

        // Set up additional JavaScript for our validation using jQuery.

        $jquery = '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>';

        $html .=$jquery;
        $javaScript = "<script type=\"text/javascript\"> var show_warning = 0;";

        if(!Mage::helper('ddg')->isSmtpEnabled()){
            $javaScript .= "show_warning = 1;";
        }

        $javaScript .=
            "jQuery.noConflict();

            jQuery(document).ready(function() {
                // Handler for .ready() called.

                //Show sweet tooth notice
                if(show_warning == 1) jQuery('#smtp-warning').show();
            });
            </script>";

        $html .= $javaScript;
        return $html;
    }
}