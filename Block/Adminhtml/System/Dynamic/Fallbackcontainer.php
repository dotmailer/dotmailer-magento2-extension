<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Fallbackcontainer extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return
            "<script type='text/javascript'>
                var fallback_product_selector = new ConnectorProductSelectorForm('connector_dynamic_content_fallback_products_product_list');
                //ajax call and handler
                getFallbackProductChooser = function (url) {
                    new Ajax.Request(
                        url, {
                            method: 'post',
                            onSuccess: function (b) {
                                var a = $('connector-fallback-product-chooser-container');
                                a.update(b.responseText);
                                a.scrollTo()
                            }
                        })
                };
             </script>
             <div id = 'connector-fallback-product-chooser-container'></div>";
    }
}
