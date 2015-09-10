<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Connect extends \Magento\Config\Block\System\Config\Form\Field
{
	protected $_buttonLabel = 'Connect';

	protected $_objectManager;
	/**
	 * Construct.
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		$data = []
	)
	{
		$this->_objectManager = $objectManagerInterface;
		parent::__construct($context, $data);
	}

	/**
	 * @param $buttonLabel
	 *
	 * @return $this
	 */
	public function setButtonLabel($buttonLabel)
	{
		$this->_buttonLabel = $buttonLabel;
		return $this;
	}

	/**
	 * Get the button and scripts contents.
	 *
	 * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
	 * @return string
	 */
	protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{

		$url = $this->_objectManager->create('Dotdigitalgroup\Email\Helper\Data')->getAuthoriseUrl();
		$ssl = $this->_checkForSecureUrl();
		$disabled = false;
		//disable for ssl missing
		if (!$ssl) {
			$disabled = true;
		}

		$adminUser = $this->_objectManager->get('Magento\Backend\Model\Auth\Session')->getUser();
		$refreshToken = $adminUser->getRefreshToken();

		$title = ($refreshToken)? __('Disconnect') : __('Connect');


		$url = ($refreshToken)? $this->getUrl('*/email_automation/disconnect') : $url;

		return $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setType('button')
            ->setLabel(__($title))
            ->setDisabled($disabled)
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
	}

    private function _checkForSecureUrl() {
	    $baseUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);

        if (!preg_match('/https/',$baseUrl)) {
            return false;
        }
        return $this;
    }
}
