<?php


class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Validator extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
	 * Ajax Validate the duplicate selection.
	 * @param Varien_Data_Form_Element_Abstract $element
	 *
	 * @return string
	 */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Get the default HTML for this option
        $html = parent::_getElementHtml($element);

        // Set up additional JavaScript for our validation using jQuery.

        $jquery = '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>';

        $html .=$jquery;
        $javaScript = "<script type=\"text/javascript\"> var show_warning = 0;";

        if(!Mage::helper('ddg')->isSweetToothEnabled()){
            $javaScript .= "show_warning = 1;";
        }

        $javaScript .=
            "jQuery.noConflict();

            jQuery(document).ready(function() {
                // Handler for .ready() called.

                //Show sweet tooth notice
                if(show_warning == 1) jQuery('#sweet-tooth-warning').show();

                // Hide our validation block
                jQuery('#row_connector_data_mapping_customer_data_validator').hide();

                // Add listener for changing select box

                jQuery('#connector_data_mapping_customer_data select').on('change', function() {
                    var currentSelection = jQuery(this).val();
                    var currentDropdownId = jQuery(this).attr('id');
                    check(currentSelection, currentDropdownId);
                });

                jQuery('#connector_data_mapping_sweet_tooth select').on('change', function() {
                    var currentSelection = jQuery(this).val();
                    var currentDropdownId = jQuery(this).attr('id');
                    check(currentSelection, currentDropdownId);
                });

                function check(currentSelection, currentDropdownId){
                    // foreach of the select fields on our mapping page:
                    jQuery('select').each(function(){
                        var thisId = jQuery(this).attr('id');
                        if (thisId != currentDropdownId) {

                            var currentLabel = jQuery('label[for=\\'' + thisId + '\\']').text();
                            var thisVal = jQuery(this).val();

                            switch (thisVal) {
                                case '0':
                                    // ignore DO NOT MAP fields
                                    break;
                                case currentSelection:
                                    // warning, that field is already mapped somewhere else - reset that value to 'Do not map''
                                    alert('Warning! You have overwritten: '+currentLabel);
                                    jQuery(this).val(0);
                                    break;
                                default:
                                    break;
                            break;
                            }
                        }
                    });
                }
            });
        </script>";

        $html .= $javaScript;
        return $html;
    }

}