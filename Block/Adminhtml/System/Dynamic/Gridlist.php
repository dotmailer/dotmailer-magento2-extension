<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Gridlist extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
	 * Dynamic contaent dysplay type.
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 *
	 * @return string
	 */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Get the default HTML for this option
        $html = parent::_getElementHtml($element);


        $jQuery = '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>';

        $jQuery .=
           '<script type="text/javascript">
            jQuery.noConflict();
            jQuery(document).ready(function() {
                      var gridOptions = {
                        "4" : "4",
                        "8" : "8",
                        "12" : "12",
                        "16" : "16",
                        "20" : "20",
                        "24" : "24",
                        "28" : "28",
                        "32" : "32"

                      };
                      var listOptions = {
                        "2" : "2",
                        "4" : "4",
                        "6" : "6",
                        "8" : "8"
                      }

                jQuery("#connector_dynamic_content_products_related_display_type").change(function(){
                    var display_type = jQuery(this).closest("tr").next().find("select");
                    var display_mode = jQuery(this).val();
                    changeOptions(display_type, display_mode);
                });

                jQuery("#connector_dynamic_content_products_upsell_display_type").change(function(){
                    var display_type = jQuery(this).closest("tr").next().find("select");
                    var display_mode = jQuery(this).val();
                    changeOptions(display_type, display_mode);

                });
                jQuery("#connector_dynamic_content_products_crosssell_display_type").change(function(){
                    var display_type = jQuery(this).closest("tr").next().find("select");
                    var display_mode = jQuery(this).val();
                    changeOptions(display_type, display_mode);
                });
                jQuery("#connector_dynamic_content_products_bestsellers_display_type").change(function(){
                    var display_type = jQuery(this).closest("tr").next().find("select");
                    var display_mode = jQuery(this).val();
                    changeOptions(display_type, display_mode);
                });
                jQuery("#connector_dynamic_content_products_most_viewed_display_type").change(function(){
                    var display_type = jQuery(this).closest("tr").next().find("select");
                    var display_mode = jQuery(this).val();
                    changeOptions(display_type, display_mode);
                });
                jQuery("#connector_dynamic_content_products_recently_viewed_display_type").change(function(){
                    var display_type = jQuery(this).closest("tr").next().find("select");
                    var display_mode = jQuery(this).val();
                    changeOptions(display_type, display_mode);
                });
                jQuery("#connector_dynamic_content_manual_product_search_display_type").change(function(){
                    var display_type = jQuery(this).closest("tr").next().find("select");
                    var display_mode = jQuery(this).val();
                    changeOptions(display_type, display_mode);
                });
                function changeOptions(display_type, display_mode){
                    if(display_mode == "list"){
                        display_type.empty();

                        jQuery.each(listOptions, function(key, value) {
                          display_type.append(jQuery("<option></option>")
                             .attr("value", value).text(key));
                        });

                      }
                    if(display_mode == "grid"){
                        display_type.empty();
                        jQuery.each(gridOptions, function(key, value) {
                            display_type.append(jQuery("<option></option>")
                         .attr("value", value).text(key));
                        });
                    }
                }
            });
            </script>';

        $html .= $jQuery;

        return $html;
    }


}