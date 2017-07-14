<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Dashboard;

/**
 * Class Configuration
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Dashboard
 */
class Configuration extends \Magento\Config\Block\System\Config\Edit
{

    /**
     * @var
     */
    public $originalParams;

    /**
     * Configuration constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Config\Model\Config\Structure  $configStructure
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        array $data = []
    ) {
        parent::__construct($context, $configStructure, $data);

        $this->_prepareRequestParams();
        $this->setTitle(__('dotmailer Configuration'));
        $this->_resetRequestParams();
    }

    /**
     * @return $this
     */
    public function _prepareLayout()  //@codingStandardsIgnoreLine
    {
        $this->_prepareRequestParams();
        parent::_prepareLayout();

        return $this;
    }

    /**
     *
     */
    public function _prepareRequestParams()  //@codingStandardsIgnoreLine
    {
        $this->originalParams = $this->getRequest()->getParam('section');
        
        $this->getRequest()->setParam('section', 'connector_developer_settings');
    }

    /**
     *
     */
    public function _resetRequestParams()  //@codingStandardsIgnoreLine
    {
        $this->getRequest()->setParam('section', $this->originalParams);
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('adminhtml/system_config/save', ['section' => 'connector_developer_settings']);
    }
}
