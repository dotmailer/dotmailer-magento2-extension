<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Automapdatafields extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $data;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\Datafield
     */
    private $datafield;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    private $config;

    /**
     * Automapdatafields constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                        $data
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield          $datafield
     * @param \Magento\Backend\App\Action\Context                       $context
     * @param \Magento\Framework\Escaper                                $escaper
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface   $config
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\Connector\Datafield $datafield,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config
    ) {
        $this->data           = $data;
        $this->datafield      = $datafield;
        $this->messageManager = $context->getMessageManager();
        $this->escaper        = $escaper;
        $this->config         = $config;
        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return null
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
            // get all possible datafields
            $datafields = $this->datafield->getContactDatafields();
            $eeFields = $this->datafield->getExtraDataFields();
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

                    //Config path depends on EE or CE
                    $configPath = isset($eeFields[$key]) ? 'connector_data_mapping/extra_data/' :
                        'connector_data_mapping/customer_data/';

                    //map the successfully created datafield
                    $this->data->saveConfigData(
                        $configPath . $key,
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
                $this->messageManager->addSuccessMessage('All Data Fields Created And Mapped.');
            }

            //Clear config cache
            $this->config->reinit();
        }

        $this->_redirect($redirectUrl);
    }
}
