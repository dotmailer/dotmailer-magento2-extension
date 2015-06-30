<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Advanced_Notification extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$element->setValue(Mage::app()->loadCache(Dotdigitalgroup_Email_Helper_Config::CONNECTOR_FEED_LAST_CHECK_TIME));
		$format = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
		return Mage::app()->getLocale()->date(intval($element->getValue()))->toString($format);
	}
}
