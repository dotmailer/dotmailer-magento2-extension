<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Config_Resetguests extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function _getElementHtml(Varien_Data_Form_Element_Abstract$element){

	    $element->setData('onchange', "resetGuests();");
	    //url to reset the guests
	    $url = Mage::helper('adminhtml')->getUrl('*/connector/ajaxresetguests');

	    $element->setData('after_element_html', "
            <script>
                function resetGuests(){
                    new Ajax.Request('{$url}', {
                        method: 'get',
                        onComplete: function(transport) {
                        }
                    });
                    return false;
                }
            </script>
        ");

	    return parent::_getElementHtml($element);


    }
}