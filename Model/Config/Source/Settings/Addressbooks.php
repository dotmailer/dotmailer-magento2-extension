<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Settings;

class Addressbooks implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Addressbooks constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->registry        = $registry;
        $this->helper          = $data;
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

        $apiEnabled = $this->helper->isEnabled($this->helper->getWebsiteForSelectedScopeInAdmin());
        if ($apiEnabled) {
            $savedAddressbooks = $this->registry->registry('addressbooks');

            if ($savedAddressbooks) {
                $addressBooks = $savedAddressbooks;
            } else {
                $client = $this->helper->getWebsiteApiClient($this->helper->getWebsiteForSelectedScopeInAdmin());
                //make an api call an register the addressbooks
                $addressBooks = $client->getAddressBooks();
                if ($addressBooks) {
                    $this->registry->unregister('addressbooks'); // additional measure
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
