<?php

namespace Dotdigitalgroup\Email\Block;

class Edc extends \Magento\Framework\View\Element\Template
{
	public function __construct()
	{

	}
    public function getTextForUrl($store)
    {
        $store = Mage::app()->getStore($store);
        return $store->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}