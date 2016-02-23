<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

class Program implements \Magento\Framework\Option\ArrayInterface
{

    protected $_helper;
    protected $_storeManager;
    protected $_registry;
    protected $_request;


    /**
     * Program constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data         $data
     * @param \Magento\Framework\App\RequestInterface    $requestInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Registry                $registry
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Registry $registry
    ) {
        $this->_helper       = $data;
        $this->_request      = $requestInterface;
        $this->_storeManager = $storeManagerInterface;
        $this->_registry     = $registry;
    }

    public function toOptionArray()
    {
        $fields   = array();
        $fields[] = array('value' => '0', 'label' => '-- Disabled --');

        $websiteName = $this->_request->getParam('website', false);
        $website     = ($websiteName)
            ? $this->_storeManager->getWebsite($websiteName) : 0;

        if ($this->_helper->isEnabled($website)) {
            $savedPrograms = $this->_registry->registry('programs');

            //get saved datafileds from registry
            if ($savedPrograms) {
                $programs = $savedPrograms;
            } else {
                //grab the datafields request and save to register
                $client   = $this->_helper->getWebsiteApiClient($website);
                $programs = $client->getPrograms();
                $this->_registry->register('programs', $programs);
            }

            //set the api error message for the first option
            if (isset($programs->message)) {
                //message
                $fields[] = array('value' => 0, 'label' => $programs->message);
            } else {
                //loop for all programs option
                foreach ($programs as $program) {
                    if (isset($program->id) && $program->status == 'Active') {
                        $fields[] = array(
                            'value' => $program->id,
                            'label' => $program->name
                        );
                    }
                }
            }
        }


        return $fields;
    }

}