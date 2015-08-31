<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Fallbackcontainer extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
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
