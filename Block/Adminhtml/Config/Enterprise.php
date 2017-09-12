<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Enterprise extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Enterprise constructor.
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
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if (! $this->helper->isEnterprise()) {
            return "";
        }

        return parent::render($element);
    }
}
