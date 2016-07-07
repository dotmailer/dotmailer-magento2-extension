<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Dashboard;

class Information extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_template = 'dashboard/information.phtml';
    

    protected $data;

    protected $test;


    /**
     * Information constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Test $test
     * @param \Magento\Backend\Block\Template\Context        $context
     * @param \Magento\Backend\Helper\Data                   $backendHelper
     * @param \Dotdigitalgroup\Email\Helper\Data             $helper
     * @param array                                          $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Apiconnector\Test $test,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        array $data = []
    ) {
        $this->test = $test;
        $this->data = $helper;
        parent::__construct($context, $backendHelper, $data);
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


    /**
     * Get the api creds are valid.
     * @return string
     */
    public function getApiValid()
    {
        $apiUsername = $this->data->getApiUsername();
        $apiPassword = $this->data->getApiPassword();

        $result = $this->test->validate($apiUsername, $apiPassword);

        return ($result)? 'Valid' : 'Not Valid';
    }

    /**
     * Get the last successful execution for import.
     *
     * @return string
     */
    public function getCronLastExecution()
    {

        $date = $this->data->getDateLastCronRun('ddg_automation_importer');

        if (! $date) {
            $date = 'No cron found';
        }
        return $date;
    }

    /**
     * Get the passcode used for DC.
     *
     * @return string
     */
    public function getDynamicContentPasscode()
    {
        return $this->data->getPasscode();
    }

    /**
     * Abandoned cart limit.
     *
     * @return mixed
     */
    public function getAbandonedCartLimit()
    {
        return ($this->data->getAbandonedCartLimit())?
            '<span style="background: #e22626; color: #ffffff">' . $this->data->getAbandonedCartLimit().
            ' h</span>' : 'No limit';
    }
}