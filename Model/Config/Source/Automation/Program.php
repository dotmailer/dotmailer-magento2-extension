<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Automation;

class Program
{

    protected $_helper;
    protected $rest;
    protected $_request;


    /**
     * Configuration structure
     *
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $_configStructure;

    public function __construct(
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_helper       = $data;
        $this->_registry     = $registry;
        $this->_request      = $requestInterface;
        $this->_storeManager = $storeManager;
    }

    public function toOptionArray()
    {
        $fields      = array();
        $fields[]    = array('value' => '0', 'label' => __('-- Disabled --'));
        $websiteName = $this->_request->getParam('website', false);
        $website     = ($websiteName)
            ? $this->_storeManager->getWebsite($websiteName) : 0;
        //api client is enabled
        $apiEnabled = $this->_helper->isEnabled($website);
        if ($apiEnabled) {

            $client   = $this->_helper->getWebsiteApiClient($website);
            $programs = $client->getPrograms();

            foreach ($programs as $one) {
                if (isset($one->id)) {
                    if ($one->status == 'Active') {
                        $fields[] = array(
                            'value' => $one->id,
                            'label' => __($one->name)
                        );
                    }
                }
            }
        }

        return $fields;
    }

}