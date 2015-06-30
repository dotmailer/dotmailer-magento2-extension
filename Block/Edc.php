<?php

class Dotdigitalgroup_Email_Block_Edc extends Mage_Core_Block_Template
{
    public function getTextForUrl($store)
    {
        $store = Mage::app()->getStore($store);
        return $store->getConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}