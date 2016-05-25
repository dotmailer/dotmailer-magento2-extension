<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Automapdatafields extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_data;

    /**
     * Automapdatafields constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data  $data
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_data = $data;
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
        $client = $this->_data->getWebsiteApiClient($website);
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        if (!$client) {
            $this->messageManager->addNotice('Please enable api first.');
        } else {
            // get all possible datatifileds
            $datafields = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Connector\Datafield')
                ->getContactDatafields();
            foreach ($datafields as $key => $datafield) {
                $response = $client->postDataFields($datafield);

                //ignore existing datafields message
                if (isset($response->message) && $response->message !=
                    \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_DATAFIELD_EXISTS) {
                    $result['errors'] = true;
                    $result['message'] .=  ' Datafield '.$datafield['name'].' - '.$response->message.'</br>';
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
                    $this->_data->saveConfigData(
                        'connector_data_mapping/customer_data/'.$key, strtoupper($datafield['name']), $scope, $scopeId
                    );
                    $this->_data->log('successfully connected : '.$datafield['name']);
                }
            }
            if ($result['errors']) {
                $this->messageManager->addNotice($result['message']);
            } else {
                $this->messageManager->addSuccess('All Datafields Created And Mapped.');
            }
        }

        $this->_redirect($redirectUrl);
    }
}
