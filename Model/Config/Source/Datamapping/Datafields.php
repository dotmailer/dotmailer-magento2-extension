<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

class Datafields implements \Magento\Framework\Option\ArrayInterface
{

    protected $_helper;
    protected $_registry;


    /**
     * Configuration structure
     *
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $_configStructure;

    /**
     * Datafields constructor.
     *
     * @param \Magento\Framework\Registry                $registry
     * @param \Dotdigitalgroup\Email\Helper\Data         $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_helper       = $data;
        $this->_registry     = $registry;
        $this->_storeManager = $storeManager;
    }

    /**
     *  Datafields option.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = array();
        //default data option
        $fields[] = array('value' => '0', 'label' => '-- Please Select --');

        $apiEnabled = $this->_helper->isEnabled($this->_helper->getWebsite());
        if ($apiEnabled) {
            $savedDatafields = $this->_registry->registry('datafields');

            //get saved datafileds from registry
            if ($savedDatafields) {
                $datafields = $savedDatafields;
            } else {
                //grab the datafields request and save to register
                $client     = $this->_helper->getWebsiteApiClient();
                $datafields = $client->GetDataFields();
                
                $this->_registry->register('datafields', $datafields);
            }

            //set the api error message for the first option
            if (isset($datafields->message)) {
                //message
                $fields[] = array(
                    'value' => 0,
                    'label' => $datafields->message
                );
            } else {
                //loop for all datafields option
                foreach ($datafields as $datafield) {
                    $fields[] = array(
                        'value' => (string)$datafield->name,
                        'label' => (string)$datafield->name
                    );
                }
            }
        }

        return $fields;
    }
}