<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Notification extends \Magento\Config\Block\System\Config\Form\Field
{
	protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{
		return 'notification element';
		$element->setValue(Mage::app()->loadCache(Dotdigitalgroup_Email_Helper_Config::CONNECTOR_FEED_LAST_CHECK_TIME));
		$format = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
		return Mage::app()->getLocale()->date(intval($element->getValue()))->toString($format);
	}
}
