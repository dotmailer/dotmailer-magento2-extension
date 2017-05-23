<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

/**
 * Class Automapdatafields
 * @package Dotdigitalgroup\Email\Controller\Adminhtml\Run
 */
class Automapdatafields extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $data;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\Datafield
     */
    public $datafield;

    /**
     * Automapdatafields constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data               $data
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield $datafield
     * @param \Magento\Backend\App\Action\Context              $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\Connector\Datafield $datafield,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->data           = $data;
        $this->datafield      = $datafield;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $result = ['errors' => false, 'message' => ''];
        $website = $this->getRequest()->getParam('website', 0);
        $client = false;
        if ($this->data->isEnabled()) {
            $client = $this->data->getWebsiteApiClient($website);
        }
        $params = [
            'section' => 'connector_developer_settings',
            'website' => $website
        ];
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', $params);

        if (!$client) {
            $this->messageManager->addNoticeMessage('Please enable api first.');
        } else {
            // get all possible datatifileds
            $datafields = $this->datafield->getContactDatafields();
            foreach ($datafields as $key => $datafield) {
                $response = $client->postDataFields($datafield);

                //ignore existing datafields message
                if (isset($response->message) && $response->message !=
                    \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_DATAFIELD_EXISTS
                ) {
                    $result['errors'] = true;
                    $result['message'] .= ' Datafield ' . $datafield['name'] . ' - ' . $response->message . '</br>';
                } else {
                    if ($website) {
                        $scope = 'websites';
                        $scopeId = $website;
                    } else {
                        $scope = 'default';
                        $scopeId = '0';
                    }
                    /*
                     * map the succesful created datafield
                     */
                    $this->data->saveConfigData(
                        'connector_data_mapping/customer_data/' . $key,
                        strtoupper($datafield['name']),
                        $scope,
                        $scopeId
                    );
                    $this->data->log('successfully connected : ' . $datafield['name']);
                }
            }
            if ($result['errors']) {
                $this->messageManager->addNoticeMessage($result['message']);
            } else {
                $this->messageManager->addSuccessMessage('All Datafields Created And Mapped.');
            }
        }

        $this->_redirect($redirectUrl);
    }

    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::config');
    }
}
