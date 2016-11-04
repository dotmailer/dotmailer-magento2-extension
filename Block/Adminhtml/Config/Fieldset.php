<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_localeDate;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;

    /**
     * Trial constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $localeDate
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\Timezone $localeDate,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        array $data = []
    ) {
        $this->_remoteAddress = $remoteAddress;
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->_helper = $helper;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @codingStandardsIgnoreStart
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $block = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration\Preview',
            'ddg_automation_dynamic_preview'
        )->setTemplate(
            'system/preview.phtml'
        );
        $this->setElement($element);
        $header = $this->_getHeaderHtml($element);

        $elements = $this->_getChildrenElementsHtml($element);

        $footer = $this->_getFooterHtml($element);

        return $header . $block->_toHtml() . $elements . $footer;
    }
}
