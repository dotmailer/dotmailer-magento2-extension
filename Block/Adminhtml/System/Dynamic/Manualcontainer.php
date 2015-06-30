<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Manualcontainer extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return
            "<script type='text/javascript'>
                var manual_product_selector = new ConnectorProductSelectorForm('connector_dynamic_content_manual_product_search_products_push_items');
                //ajax call and handler
                getManualProductChooser = function (url) {
                    new Ajax.Request(
                        url, {
                            method: 'post',
                            onSuccess: function (b) {
                                var a = $('connector-product-chooser-container');
                                a.update(b.responseText);
                                a.scrollTo()
                            }
                        })
                };
             </script>
             <div id = 'connector-product-chooser-container'></div>";
    }
}
