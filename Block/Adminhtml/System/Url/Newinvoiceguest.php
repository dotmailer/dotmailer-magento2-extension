<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Url_Newinvoiceguest extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/**
	 * Generate the urls.
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 *
	 * @return string
	 * @throws Mage_Core_Exception
	 */
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $website = Mage::app()->getRequest()->getParam('website', false);

        if ($website) {
            $website = Mage::app()->getWebsite($website);
            $baseUrl  = $website->getConfig('web/secure/base_url');
        }
        $helper = Mage::helper('ddg');
        $passcode = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE, $website);
        $orderId = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID, $website);


        if(!strlen($passcode)) $passcode = '[PLEASE SET UP A PASSCODE]';

        $text = sprintf('%sconnector/invoice/newguest/code/%s/order_id/@%s@', $baseUrl, $passcode, $orderId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}