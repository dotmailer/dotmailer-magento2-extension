<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Dashboard;

class Information extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var string
     */
    public $_template = 'dashboard/information.phtml';

    /**
     * Helper.
     *
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $data;

    /**
     * Test class.
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Test
     */
    public $test;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    public $productMetadata;

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
        \Magento\Framework\App\ProductMetadataFactory $productMetadata,
        array $data = []
    ) {
        $this->productMetadata = $productMetadata->create();
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
        $productMetadata = $this->productMetadata;

        return __('ver. %1', $productMetadata->getVersion());
    }

    /**
     * @return string
     */
    public function getMagentoEdition()
    {
        $productMetadata = $this->productMetadata;

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

        return ($result)? '<span class="message message-success">Valid</span>' :
            '<span class="message message-error">Not Valid</span>';
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
            $date = '<span class="message message-error">No cron found</span>';
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
            '<span class="message message-warning">' . $this->data->getAbandonedCartLimit().
            ' h</span>' : 'No limit';
    }
}
