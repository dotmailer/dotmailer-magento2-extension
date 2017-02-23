<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

class ApiValidate implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    public $context;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;
    /**
     * @var \Magento\Framework\App\Config\Storage\Writer
     */
    public $writer;
    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Test
     */
    public $test;

    /**
     * ApiValidate constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data           $data
     * @param \Magento\Backend\App\Action\Context          $context
     * @param \Magento\Framework\App\Config\Storage\Writer $writer
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\Apiconnector\Test $test,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\Storage\Writer $writer
    ) {
        $this->helper         = $data;
        $this->test           = $test;
        $this->context        = $context;
        $this->messageManager = $context->getMessageManager();
        $this->objectManager  = $context->getObjectManager();
        $this->writer         = $writer;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     * @codingStandardsIgnoreStart
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //@codingStandardsIgnoreEnd
        $groups = $this->context->getRequest()->getPost('groups');

        if (isset($groups['api']['fields']['username']['inherit'])
            || isset($groups['api']['fields']['password']['inherit'])
        ) {
            return $this;
        }

        $apiUsername = isset($groups['api']['fields']['username']['value'])
            ? $groups['api']['fields']['username']['value'] : false;
        $apiPassword = isset($groups['api']['fields']['password']['value'])
            ? $groups['api']['fields']['password']['value'] : false;

        //skip if the inherit option is selected
        if ($apiUsername && $apiPassword) {
            $this->helper->log('----VALIDATING ACCOUNT---');
            $isValid = $this->test->validate($apiUsername, $apiPassword);
            if ($isValid) {
                $this->saveApiEndpoint($apiUsername, $apiPassword);
                $this->messageManager->addSuccessMessage(__('API Credentials Valid.'));
            } else {
                $this->messageManager->addWarningMessage(__('Authorization has been denied for this request.'));
            }
        }

        return $this;
    }

    /**
     * Save api endpoint
     *
     * @param $apiUsername
     * @param $apiPassword
     */
    protected function saveApiEndpoint($apiUsername, $apiPassword)
    {
        $website = $this->helper->getWebsite();
        $client = $this->helper->getWebsiteApiClient($website);
        $client->setApiUsername($apiUsername)
            ->setApiPassword($apiPassword);
        $apiEndpoint = $this->helper->getApiEndPointFromApi($client);
        if ($apiEndpoint) {
            $this->helper->saveApiEndpoint($apiEndpoint, $website->getId());
        }
    }
}
