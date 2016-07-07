<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Dashboard;

class System extends \Magento\Backend\Block\Template
{

    /**
     * @var string
     */
    protected $_template = 'dashboard/system.phtml';
    
    protected $data;


    /**
     * System constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data      $data
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->data = $helper;
        parent::__construct($context, $data);
    }



    /**
     * @return string
     */
    public function getPhpVersion()
    {
        return __('v. %1', PHP_VERSION);
    }


    /**
     * @return string
     */
    public function getPhpMaxExecutionTime()
    {
        return ini_get('max_execution_time') . ' sec.';
    }

    /**
     * @return string
     */
    public function getDeveloperMode()
    {
        return $this->_appState->getMode();
    }

    /**
     * Mgento version 
     * @return \Magento\Framework\Phrase
     */
    public function getMagentoVersion()
    {
        $productMetadata = new \Magento\Framework\App\ProductMetadata();

        return __('ver. %1', $productMetadata->getVersion());
    }

    /**
     * @return string
     */
    public function getMagentoEdition()
    {
        $productMetadata = new \Magento\Framework\App\ProductMetadata();

        return $productMetadata->getEdition();

    }


    /**
     * @return mixed
     */
    public function getConnectorVersion()
    {
        return __('v. %1', $this->data->getConnectorVersion());
    }

}
