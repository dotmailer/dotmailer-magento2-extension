<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

class ApiValidate implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $_context;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var \Magento\Framework\App\Config\Storage\Writer
     */
    protected $_writer;

    /**
     * ApiValidate constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data           $data
     * @param \Magento\Backend\App\Action\Context          $context
     * @param \Magento\Framework\App\Config\Storage\Writer $writer
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\Storage\Writer $writer
    ) {
        $this->_helper = $data;
        $this->_context = $context;
        $this->messageManager = $context->getMessageManager();
        $this->_objectManager = $context->getObjectManager();
        $this->_writer = $writer;
    }

    /**
     * Execute method.
     * 
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $groups = $this->_context->getRequest()->getPost('groups');

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
            $this->_helper->log('----VALIDATING ACCOUNT---');
            $testModel
                     = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Test');
            $isValid = $testModel->validate($apiUsername, $apiPassword);
            if ($isValid) {

                //save endpoint for account
                foreach ($isValid->properties as $property) {
                    if ($property->name == 'ApiEndpoint'
                        && strlen($property->value)
                    ) {
                        $this->_saveApiEndpoint($property->value);
                        break;
                    }
                }

                $this->messageManager->addSuccess(__('API Credentials Valid.'));
            } else {
                $this->messageManager->addWarning(__('Authorization has been denied for this request.'));
            }
        }

        return $this;
    }

    /**
     * Save api endpoint into config.
     *
     * @param string $apiEndpoint
     */
    protected function _saveApiEndpoint($apiEndpoint)
    {
        $this->_writer->save(
            \Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT,
            $apiEndpoint
        );
    }
}
