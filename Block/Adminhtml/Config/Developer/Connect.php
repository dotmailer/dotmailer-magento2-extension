<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Connect extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_getAddRowButtonHtml();
    }

    protected function _getAddRowButtonHtml()
    {

	    return 'connect element';

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
