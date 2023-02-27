<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetupFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\DateTime\Timezone;

class Accountcallback extends Action
{
    /**
     * @var Timezone
     */
    private $timezone;

    /**
     * @var IntegrationSetup
     */
    private $integrationSetup;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * Accountcallback constructor.
     *
     * @param Context $context
     * @param Timezone $timezone
     * @param IntegrationSetupFactory $integrationSetupFactory
     * @param Data $helper
     * @param Manager $moduleManager
     * @param ReinitableConfigInterface $reinitableConfig
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Context $context,
        Timezone $timezone,
        IntegrationSetupFactory $integrationSetupFactory,
        Data $helper,
        Manager $moduleManager,
        ReinitableConfigInterface $reinitableConfig,
        PublisherInterface $publisher
    ) {

        $this->timezone = $timezone;

        $this->integrationSetup = $integrationSetupFactory->create();
        $this->helper = $helper;
        $this->moduleManager = $moduleManager;
        $this->reinitableConfig = $reinitableConfig;
        $this->publisher = $publisher;
        parent::__construct($context);
    }

    /**
     * Process the callback
     *
     * @return ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

        $this->helper->debug('Account callback request', $params);

        if (!isset($params['code']) || !$this->integrationSetup->isCodeValid($params['code'])) {
            return $this->sendErrorResponse();
        }

        // save credentials and reinit cache
        $this->helper->saveApiCredentials(
            $params['apiusername'],
            $params['apipassword'],
            $params['apiendpoint'] ?? null,
            $website
        );

        // enable EC in Magento
        $this->helper->enableEngagementCloud($website);
        $this->reinitableConfig->reinit();

        // set up EC account
        $dataFieldsStatus = $this->integrationSetup->setupDataFields();
        $addressBookStatus = $this->integrationSetup->createAddressBooks();
        $syncStatus = $this->integrationSetup->enableSyncs();

        //Clear config cache.
        $this->reinitableConfig->reinit();

        $this->helper->log('Dotdigital account creation', [
            'api_username' => $params['apiusername'],
            'api_endpoint' => $params['apiendpoint'],
            'data_field_set_up' => $dataFieldsStatus,
            'address_books_set_up' => $addressBookStatus,
            'syncs_enabled_for_trial' => $syncStatus,
        ]);

        $this->helper->log('----PUBLISHING INTEGRATION INSIGHTS---');
        $this->publisher->publish('ddg.sync.integration', '');

        return $this->getResponse()
            ->setHttpResponseCode(201)
            ->sendHeaders();
    }

    /**
     * Send error response
     *
     * @return ResponseInterface
     */
    private function sendErrorResponse()
    {
        return $this->getResponse()
            ->setHttpResponseCode(401)
            ->sendHeaders();
    }
}
