<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Trial extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    const TRIAL_EXTERNAL_URL = 'https://www.dotmailer.com/trial/';

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Trial constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if (! $this->helper->isFrontEndAdminSecure()) {
            $html = '<a href=' .
                self::TRIAL_EXTERNAL_URL .
                ' target="_blank"><img style="margin-bottom:15px;" src=' .
                $this->getViewFileUrl('Dotdigitalgroup_Email::images/banner.png') .
                ' alt="Open Trial Account"></a>';
        } else {
            $internalUrl = $this->getUrl('dotdigitalgroup_email/connector/trial');
            $html = '<a class="ddg-fancyBox fancybox.iframe" data-fancybox-type="iframe" href=' .
                $this->escapeUrl($internalUrl) . '><img style="margin-bottom:15px;" src=' .
                $this->getViewFileUrl('Dotdigitalgroup_Email::images/banner.png') .
                ' alt="Open Trial Account"></a>';
        }

        return $html;
    }
}
