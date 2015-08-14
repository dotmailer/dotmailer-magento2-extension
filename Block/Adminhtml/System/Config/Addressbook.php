<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Addressbook extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
	 * Ajax Create the addressbooks.
	 * @param Varien_Data_Form_Element_Abstract $element
	 *
	 * @return string
	 */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $url = Mage::helper('adminhtml')->getUrl('*/connector/createnewaddressbook');
        $website = Mage::app()->getRequest()->getParam('website', 0);

        $element->setData('after_element_html',
            "<script>
                function createAddressbook(form, element) {
                    var name       = $('connector_sync_settings_dynamic_addressbook_addressbook_name').value;
                    var visibility = $('connector_sync_settings_dynamic_addressbook_visibility').value;
                    var reloadurl  = '{$url}';
                    if(name && visibility){
                        new Ajax.Request(reloadurl, {
                            method: 'post',
                            parameters: {'name' : name, 'visibility' : visibility, 'website': '$website'},
                            onComplete: function(transport) {
                                window.location.reload();
                            }
                        });
                    }
                    return false;
                }
            </script>"
        );

        return parent::_getElementHtml($element);
    }
}
