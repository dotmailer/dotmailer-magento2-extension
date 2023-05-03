<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Test;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Dotdigitalgroup\Email\Model\Sync\DummyRecordsFactory;

/**
 * Validate api when saving credentials in admin.
 */
class AccountCredentials implements ObserverInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Test
     */
    private $test;

    /**
     * @var DummyRecordsFactory
     */
    private $dummyRecordsFactory;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param Data $data
     * @param Test $test
     * @param Context $context
     * @param DummyRecordsFactory $dummyRecordsFactory
     * @param Config $resourceConfig
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Data $data,
        Test $test,
        Context $context,
        DummyRecordsFactory $dummyRecordsFactory,
        Config $resourceConfig,
        PublisherInterface $publisher
    ) {
        $this->test = $test;
        $this->helper = $data;
        $this->context = $context;
        $this->messageManager = $context->getMessageManager();
        $this->resourceConfig = $resourceConfig;
        $this->dummyRecordsFactory = $dummyRecordsFactory;
        $this->publisher = $publisher;
    }

    /**
     * Execute method.
     *
     * Validate account credentials and publish integration insights queue
     *
     * @param Observer $observer
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\App\Request\Http\Proxy $request */
        $request = $this->context->getRequest();
        $groups = $request->getPost('groups');

        if (isset($groups['api']['fields']['username']['inherit'])
            || isset($groups['api']['fields']['password']['inherit'])
        ) {
            $this->deleteApiEndpoint();
            return $this;
        }

        $apiUsername = $groups['api']['fields']['username']['value'] ?? false;
        $apiPassword = $groups['api']['fields']['password']['value'] ?? false;

        if ($apiUsername && $apiPassword) {
            $isValidAccount = $this->isValidAccount($apiUsername, $apiPassword);
            if ($isValidAccount) {
                $this->helper->log('----PUBLISHING INTEGRATION INSIGHTS---');
                $this->publisher->publish('ddg.sync.integration', '');

                $websiteId = $this->context->getRequest()->getParam('website');

                if ($websiteId) {
                    $this->dummyRecordsFactory->create()
                        ->syncForWebsite($websiteId);

                    return $this;
                }

                $this->dummyRecordsFactory->create()
                    ->sync();
            }
        }

        return $this;
    }

    /**
     * Validate account
     *
     * @param string $apiUsername
     * @param string $apiPassword
     * @return bool
     */
    private function isValidAccount(string $apiUsername, string $apiPassword): bool
    {
        $this->helper->log('----VALIDATING ACCOUNT---');

        if ($this->test->validate($apiUsername, $apiPassword)) {
            $this->messageManager->addSuccessMessage(__('API Credentials Valid.'));
            return true;
        }

        $this->messageManager->addWarningMessage(__('Authorization has been denied for this request.'));
        return false;
    }

    /**
     * Deletes api endpoint if default value is used.
     * @return void
     */
    private function deleteApiEndpoint()
    {
        $websiteId = $this->context->getRequest()->getParam('website');

        $scope = 'default';
        $scopeId = '0';

        if ($websiteId) {
            $scope = 'websites';
            $scopeId = $websiteId;
        }

        $this->resourceConfig->deleteConfig(
            \Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT,
            $scope,
            $scopeId
        );
    }
}
