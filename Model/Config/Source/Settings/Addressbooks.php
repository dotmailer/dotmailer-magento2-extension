<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Settings;

/**
 * Class Addressbooks
 * @package Dotdigitalgroup\Email\Model\Config\Source\Settings
 */
class Addressbooks implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var null
     */
    public $options = null;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * Configuration structure.
     *
     * @var \Magento\Config\Model\Config\Structure
     */
    public $configStructure;

    /**
     * Addressbooks constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Config\Model\Config\Structure $configStructure
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Config\Model\Config\Structure $configStructure
    ) {
        $this->registry        = $registry;
        $this->helper          = $data;
        $this->configStructure = $configStructure;
    }

    /**
     * Retrieve list of options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = [];
        // Add a "Do Not Map" Option
        $fields[] = ['value' => 0, 'label' => '-- Please Select --'];

        $apiEnabled = $this->helper->isEnabled($this->helper->getWebsite());
        if ($apiEnabled) {
            $savedAddressbooks = $this->registry->registry('addressbooks');

            if ($savedAddressbooks) {
                $addressBooks = $savedAddressbooks;
            } else {
                $client = $this->helper->getWebsiteApiClient($this->helper->getWebsite());
                //make an api call an register the addressbooks
                $addressBooks = $client->getAddressBooks();
                if ($addressBooks) {
                    $this->registry->register('addressbooks', $addressBooks);
                }
            }

            //set up fields with book id and label
            foreach ($addressBooks as $book) {
                if (isset($book->id)) {
                    $fields[] = [
                        'value' => (string)$book->id,
                        'label' => (string)$book->name,
                    ];
                }
            }
        }

        return $fields;
    }
}
