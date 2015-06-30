<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Manualcontainer extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
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
