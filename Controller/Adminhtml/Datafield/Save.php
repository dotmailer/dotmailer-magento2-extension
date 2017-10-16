<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Datafield;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $dataHelper;

    /**
     * Save constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Escaper $escaper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->dataHelper     = $data;
        $this->escaper = $escaper;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $datafield  = $this->getRequest()->getParam('name');
        $type       = $this->getRequest()->getParam('type');
        $default    = $this->getRequest()->getParam('default');
        $visibility = $this->getRequest()->getParam('visibility');
        $website    = (int) $this->getRequest()->getParam('website', 0);

        if (! empty($datafield)) {

            $client = $this->dataHelper->getWebsiteApiClient($website);
            $response = $this->createDatafield($client, $datafield, $type, $visibility, $default);

            if (isset($response->message)) {
                $this->messageManager->addErrorMessage($response->message);
            } else {
                $this->messageManager->addSuccessMessage('Datafield successfully created.');
            }
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::automation');
    }

    /**
     * @param $client \Dotdigitalgroup\Email\Model\Client
     * @param $datafield string
     * @param $type string
     * @param $visibility string
     * @param $default mixed
     * @return mixed
     */
    private function createDatafield($client, $datafield, $type, $visibility = 'Private', $default = 'String')
    {
        switch ($type) {
            case 'Numeric' :
                $default = (int)$default;
                break;
            case 'String' :
                $default = (string)$default;
                break;
            case 'Date' :
                $date = new \Zend_Date($default);
                $default = $date->toString(\Zend_Date::ISO_8601);
                break;
            case 'Boolean' :
                $default = (bool)$default;
                break;

        }
        $response = $client->postDataFields($datafield, $type, $visibility, $default);

        return $response;
    }
}
