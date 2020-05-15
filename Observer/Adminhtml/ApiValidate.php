<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Dotdigitalgroup\Email\Model\Sync\IntegrationInsightsFactory;
use Dotdigitalgroup\Email\Model\Sync\DummyRecordsFactory;

/**
 * Validate api when saving credentials in admin.
 */
class ApiValidate implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    private $context;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\App\Config\Storage\Writer
     */
    private $writer;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Test
     */
    private $test;

    /**
     * @var IntegrationInsightsFactory
     */
    private $integrationInsightsFactory;

    /**
     * @var DummyRecordsFactory
     */
    private $dummyRecordsFactory;

    /**
     * ApiValidate constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Test $test
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Config\Storage\Writer $writer
     * @param IntegrationInsightsFactory $integrationInsightsFactory
     * @param DummyRecordsFactory $dummyRecordsFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\Apiconnector\Test $test,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\Storage\Writer $writer,
        IntegrationInsightsFactory $integrationInsightsFactory,
        DummyRecordsFactory $dummyRecordsFactory
    ) {
        $this->test           = $test;
        $this->helper         = $data;
        $this->writer         = $writer;
        $this->context        = $context;
        $this->messageManager = $context->getMessageManager();
        $this->integrationInsightsFactory = $integrationInsightsFactory;
        $this->dummyRecordsFactory = $dummyRecordsFactory;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $groups = $this->context->getRequest()->getPost('groups');

        if (isset($groups['api']['fields']['username']['inherit'])
            || isset($groups['api']['fields']['password']['inherit'])
        ) {
            return $this;
        }

        $apiUsername = $groups['api']['fields']['username']['value'] ?? false;
        $apiPassword = $groups['api']['fields']['password']['value'] ?? false;

        if ($apiUsername && $apiPassword) {
            $isValidAccount = $this->isValidAccount($apiUsername, $apiPassword);
            if ($isValidAccount) {
                // send integration data
                $this->integrationInsightsFactory->create()
                    ->sync();

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
}
