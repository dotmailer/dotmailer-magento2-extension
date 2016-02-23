<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Settings;


class Addressbooks implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * options
     *
     * @var array
     */
    protected $_options = null;
    protected $_helper;
    protected $_registry;

    /**
     * Configuration structure
     *
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $_configStructure;


    /**
     * @param \Magento\Config\Model\Config\Structure $configStructure
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Config\Model\Config\Structure $configStructure

    ) {
        $this->_registry        = $registry;
        $this->_helper          = $data;
        $this->_configStructure = $configStructure;
    }

    /**
     * Retrieve list of options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = array();
        // Add a "Do Not Map" Option
        $fields[] = array('value' => 0, 'label' => '-- Please Select --');

        $apiEnabled = $this->_helper->isEnabled($this->_helper->getWebsite());
        if ($apiEnabled) {

            $savedAddressbooks = $this->_registry->registry('addressbooks');

            if ($savedAddressbooks) {
                $addressBooks = $savedAddressbooks;
            } else {
                $client = $this->_helper->getWebsiteApiClient();
                //make an api call an register the addressbooks
                $addressBooks = $client->getAddressBooks();
                if ($addressBooks) {
                    $this->_registry->register('addressbooks', $addressBooks);
                }
            }

            //set up fields with book id and label
            foreach ($addressBooks as $book) {
                if (isset($book->id)) {
                    $fields[] = array(
                        'value' => $book->id,
                        'label' => $book->name
                    );
                }
            }
        }

        return $fields;
    }
}
