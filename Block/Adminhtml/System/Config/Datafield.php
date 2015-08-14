<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Datafield extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
	 * Ajax Create the datafields.
	 * @param Varien_Data_Form_Element_Abstract $element
	 *
	 * @return string
	 */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $url = Mage::helper('adminhtml')->getUrl('*/connector/createnewdatafield');
        $website = Mage::app()->getRequest()->getParam('website', 0);

        $element->setData('after_element_html',
            "<script>
                function createDatafield(form, element) {
                    var datafield_name  	= $('connector_data_mapping_dynamic_datafield_datafield_name').value;
                    var datafield_type  	= $('connector_data_mapping_dynamic_datafield_datafield_type').value;
                    var datafield_default  	= $('connector_data_mapping_dynamic_datafield_datafield_default').value;
                    var datafield_access    = $('connector_data_mapping_dynamic_datafield_datafield_access').value;

                    var reloadurl  = '{$url}';

                    if(datafield_name && datafield_type && datafield_access){
                        new Ajax.Request(reloadurl, {
                            method: 'post',
                            parameters: {'name' : datafield_name, 'type' : datafield_type, 'default' : datafield_default, 'access' : datafield_access, 'website': '$website'},
                            onComplete: function(transport) {
                                window.location.reload();
                            }
                        });
                    }
                    return false;}</script>"
        );

        return parent::_getElementHtml($element);
    }
}
