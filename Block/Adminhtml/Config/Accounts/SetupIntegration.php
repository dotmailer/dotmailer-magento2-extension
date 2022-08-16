<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Accounts;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\AbstractButton;
use Dotdigitalgroup\Email\Model\Integration\AccountDetails;
use Magento\Backend\Block\Template\Context;

class SetupIntegration extends AbstractButton
{
    /**
     * @var AccountDetails
     */
    private $accountDetails;

    /**
     * SetupIntegration constructor.
     *
     * @param Context $context
     * @param AccountDetails $accountDetails
     * @param array $data
     */
    public function __construct(
        Context $context,
        AccountDetails $accountDetails,
        array $data = []
    ) {
        $this->accountDetails = $accountDetails;
        parent::__construct($context, $data);
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    protected function getDisabled()
    {
        return !$this->accountDetails->isEnabled() || !$this->accountDetails->getIsConnected();
    }

    /**
     * Get button label.
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function getButtonLabel()
    {
        return  __('Set Up Integration');
    }

    /**
     * Get button url.
     *
     * @return string
     */
    protected function getButtonUrl()
    {
        $website = $this->getRequest()->getParam('website', 0);
        $params = ['website' => $website];
        return $this->_urlBuilder->getUrl('dotdigitalgroup_email/run/setupintegration', $params);
    }

    /**
     * Get element html.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $website = $this->getRequest()->getParam('website', 0);
        $params = ['website' => $website];

        $block = $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class);

        /** @var \Magento\Backend\Block\Widget\Button $block */
        return $block->setType('button')
            ->setLabel($this->getButtonLabel())
            ->setValue($this->_urlBuilder->getUrl('dotdigitalgroup_email/run/setupintegration', $params))
            ->setClass('ddg-integration')
            ->setDisabled($this->getDisabled())
            ->toHtml();
    }
}
