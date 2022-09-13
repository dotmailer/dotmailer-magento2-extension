<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Dashboard;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Test;
use Dotdigitalgroup\Email\Model\Connector\Module;
use Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth\CollectionFactory;
use IntlDateFormatter;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ProductMetadataFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Dashboard information block
 *
 * @api
 */
class Information extends Template
{
    /**
     * Helper.
     *
     * @var Data
     */
    private $helper;

    /**
     * Test class.
     * @var Test
     */
    private $test;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /*
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth\Collection
     */
    private $failedAuthCollectionFactory;

    /**
     * @var Module
     */
    private $moduleList;

    /**
     * @var int
     */
    private $storeIdFromParam = 1;

    /**
     * Information constructor.
     * @param Context $context
     * @param Test $test
     * @param Data $helper
     * @param ProductMetadataFactory $productMetadata
     * @param Module $moduleList
     * @param CollectionFactory $failedAuthCollectionFactory
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        Test $test,
        Data $helper,
        ProductMetadataFactory $productMetadata,
        Module $moduleList,
        CollectionFactory $failedAuthCollectionFactory,
        array $data = []
    ) {
        $this->productMetadata = $productMetadata->create();
        $this->test = $test;
        $this->helper = $helper;
        $this->failedAuthCollectionFactory = $failedAuthCollectionFactory;
        $this->moduleList = $moduleList;
        parent::__construct($context, $data);
        $this->getStoreIdParam();
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
        return $this->escapeHtml(ini_get('max_execution_time') . ' sec.');
    }

    /**
     * @return string
     */
    public function getDeveloperMode()
    {
        return $this->escapeHtml($this->_appState->getMode());
    }

    /**
     * Magento version
     * @return Phrase | string
     */
    public function getMagentoVersion()
    {
        $productMetadata = $this->productMetadata;

        return $this->escapeHtml(__('ver. %1', $productMetadata->getVersion()));
    }

    /**
     * @return array|array[]
     */
    public function fetchActiveModules()
    {
        return $this->moduleList->fetchActiveModules();
    }

    /**
     * Return HTML indicating the validity of stored API credentials.
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getApiValid()
    {
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();
        $apiUsername = $this->helper->getApiUsername($website);
        $apiPassword = $this->helper->getApiPassword($website);
        $result = $this->test->validate($apiUsername, $apiPassword);

        return ($result)
            ? '<span class="message message-success">Valid</span>'
            : '<span class="message message-error">Not Valid</span>';
    }

    /**
     * Get the last successful execution for import.
     *
     * @return string
     */
    public function getCronLastExecution()
    {
        $date = $this->escapeHtml($this->helper->getDateLastCronRun('ddg_automation_importer'));

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
        return $this->helper->getPasscode();
    }

    /**
     * Abandoned cart limit.
     *
     * @return string
     */
    public function getAbandonedCartLimit()
    {
        return ($this->helper->getAbandonedCartLimit()) ? __('%1 hour', $this->helper->getAbandonedCartLimit()) :
            __('No limit');
    }

    /**
     * @return Phrase|string]
     */
    public function getAuthStatus()
    {
        $collection = $this->failedAuthCollectionFactory->create()
            ->loadByStoreId($this->storeIdFromParam);
        $failedAuth = $collection->getFirstItem();

        //check if the failed auth is set for the store
        if ($failedAuth->getId()) {
            return ($failedAuth->isLocked()) ? __('Locked') : __('Not locked');
        } else {
            return __('Not locked');
        }
    }

    /**
     * @return string|Phrase
     */
    public function getLastFailedAuth()
    {
        $collection = $this->failedAuthCollectionFactory->create()
            ->loadByStoreId($this->storeIdFromParam);
        $failedAuth = $collection->getFirstItem();

        if ($failedAuth->getId()) {
            return $this->formatTime($failedAuth->getLastAttemptDate(), IntlDateFormatter::LONG);
        } else {
            return __('None found');
        }
    }

    /**
     * @throws LocalizedException
     */
    private function getStoreIdParam()
    {
        $storeCode = $this->getRequest()->getParam('store');
        $websiteCode = $this->getRequest()->getParam('website');
        //store level
        if ($storeCode) {
            $this->storeIdFromParam = $this->_storeManager->getStore($storeCode)->getId();
        } else {
            //website level
            $this->storeIdFromParam = $this->_storeManager->getWebsite($websiteCode)->getDefaultStore()->getId();
        }
    }
}
