<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Dashboard;

/**
 * Dashboard Configuration block
 *
 * @api
 */
class Configuration extends \Magento\Config\Block\System\Config\Edit
{

    /**
     * @var mixed
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
     * Prepare layout.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->_prepareRequestParams();
        parent::_prepareLayout();

        return $this;
    }

    /**
     * Prepare request params.
     *
     * @return void
     */
    public function _prepareRequestParams()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $this->originalParams = $request->getParam('section');
        $request->setParam('section', 'connector_developer_settings');
    }

    /**
     * Reset request params.
     *
     * @return void
     */
    public function _resetRequestParams()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $request->setParam('section', $this->originalParams);
    }

    /**
     * Get save url.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('adminhtml/system_config/save', ['section' => 'connector_developer_settings']);
    }
}
