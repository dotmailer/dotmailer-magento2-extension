<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetupFactory;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsights;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class Accountcallback implements HttpPostActionInterface
{
    /**
     * @var IntegrationSetup
     */
    private $integrationSetup;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * Accountcallback constructor.
     *
     * @param Context $context
     * @param IntegrationSetupFactory $integrationSetupFactory
     * @param Data $helper
     * @param Logger $logger
     * @param ReinitableConfigInterface $reinitableConfig
     * @param ResultFactory $resultFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Context $context,
        IntegrationSetupFactory $integrationSetupFactory,
        Data $helper,
        Logger $logger,
        ReinitableConfigInterface $reinitableConfig,
        ResultFactory $resultFactory,
        PublisherInterface $publisher
    ) {
        $this->integrationSetup = $integrationSetupFactory->create();
        $this->helper = $helper;
        $this->logger = $logger;
        $this->reinitableConfig = $reinitableConfig;
        $this->request = $context->getRequest();
        $this->resultFactory = $resultFactory;
        $this->publisher = $publisher;
    }

    /**
     * Process the callback
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

        $this->logger->debug('Account callback request', $params);

        if (!isset($params['code']) || !$this->integrationSetup->isCodeValid($params['code'])) {
            return $this->sendErrorResponse();
        }

        try {
            // save credentials and reinit cache
            $this->helper->saveApiCredentials(
                $params['apiusername'],
                $params['apipassword'],
                $website,
                $params['apiendpoint'] ?? null,
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

            $this->logger->info('Dotdigital account creation', [
                'api_username' => $params['apiusername'],
                'api_endpoint' => $params['apiendpoint'],
                'data_field_set_up' => $dataFieldsStatus,
                'address_books_set_up' => $addressBookStatus,
                'syncs_enabled_for_trial' => $syncStatus,
            ]);

            $this->logger->info('----PUBLISHING INTEGRATION INSIGHTS---');
            $this->publisher->publish(IntegrationInsights::TOPIC_SYNC_INTEGRATION, '');
        } catch (\Exception $e) {
            $this->logger->error('Error in account callback controller', [(string) $e]);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_RAW)
            ->setHttpResponseCode(201);
    }

    /**
     * Send error response
     *
     * @return ResultInterface
     */
    private function sendErrorResponse()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)
            ->setHttpResponseCode(401);
    }
}
