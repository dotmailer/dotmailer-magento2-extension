<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Automation_Connect extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml();
    }

    protected function _getAddRowButtonHtml()
    {
        $url = Mage::helper('ddg')->getAuthoriseUrl();
        $ssl = $this->_checkForSecureUrl();
        $disabled = false;
	    //disable for ssl missing
        if (!$ssl) {
            $disabled = true;
        }

        $adminUser = Mage::getSingleton('admin/session')->getUser();
        $refreshToken = $adminUser->getRefreshToken();
        $title = ($refreshToken)? $this->__('Disconnect') : $this->__('Connect');
        $url = ($refreshToken)? $this->getUrl('*/email_automation/disconnect') : $url;

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setDisabled($disabled)
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }

    private function _checkForSecureUrl() {
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);
        if (!preg_match('/https/',$baseUrl)) {
            return false;
        }
        return $this;
    }
}
