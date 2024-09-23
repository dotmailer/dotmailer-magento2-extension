<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetupFactory;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsights;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
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
     * @param ReinitableConfigInterface $reinitableConfig
     * @param ResultFactory $resultFactory
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Context $context,
        IntegrationSetupFactory $integrationSetupFactory,
        Data $helper,
        ReinitableConfigInterface $reinitableConfig,
        ResultFactory $resultFactory,
        PublisherInterface $publisher
    ) {
        $this->integrationSetup = $integrationSetupFactory->create();
        $this->helper = $helper;
        $this->reinitableConfig = $reinitableConfig;
        $this->request = $context->getRequest();
        $this->resultFactory = $resultFactory;
        $this->publisher = $publisher;
    }

    /**
     * Process the callback
     *
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $params = $this->request->getParams();
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
        $this->publisher->publish(IntegrationInsights::TOPIC_SYNC_INTEGRATION, '');

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
