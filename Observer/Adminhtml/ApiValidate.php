<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

/**
 * Validate api when saving creds in admin.
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
     * ApiValidate constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Test $test
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Config\Storage\Writer $writer
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\Apiconnector\Test $test,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\Storage\Writer $writer
    ) {
        $this->test           = $test;
        $this->helper         = $data;
        $this->writer         = $writer;
        $this->context        = $context;
        $this->messageManager = $context->getMessageManager();
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

        $apiUsername = isset($groups['api']['fields']['username']['value'])
            ? $groups['api']['fields']['username']['value'] : false;
        $apiPassword = isset($groups['api']['fields']['password']['value'])
            ? $groups['api']['fields']['password']['value'] : false;

        $this->validateAccount($apiUsername, $apiPassword);

        return $this;
    }

    /**
     * Validate account
     *
     * @param string|boolean $apiUsername
     * @param string|boolean $apiPassword
     * @return void
     */
    private function validateAccount($apiUsername, $apiPassword)
    {
        //skip if the inherit option is selected
        if ($apiUsername && $apiPassword) {
            $this->helper->log('----VALIDATING ACCOUNT---');
            $isValid = $this->test->validate($apiUsername, $apiPassword);
            if ($isValid) {
                $this->messageManager->addSuccessMessage(__('API Credentials Valid.'));
            } else {
                $this->messageManager->addWarningMessage(__('Authorization has been denied for this request.'));
            }
        }
    }
}
