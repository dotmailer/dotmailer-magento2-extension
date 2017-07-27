<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Addressbooks implements \Magento\Framework\Data\OptionSourceInterface
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Addressbooks constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Helper\Data         $data
     * @param \Magento\Framework\Registry                $registry
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Registry $registry
    ) {
        $this->storeManager = $storeManagerInterface;
        $this->helper       = $data;
        $this->registry     = $registry;
    }

    /**
     * Get address books.
     *
     * @return null
     */
    public function getAddressBooks()
    {
        $website = $this->helper->getWebsite();
        $client = $this->helper->getWebsiteApiClient($website);

        $savedAddressBooks = $this->registry->registry('addressbooks');
        //get saved address books from registry
        if ($savedAddressBooks) {
            $addressBooks = $savedAddressBooks;
        } else {
            // api all address books
            $addressBooks = $client->getAddressBooks();
            $this->registry->unregister('addressbooks'); // additional measure
            $this->registry->register('addressbooks', $addressBooks);
        }

        return $addressBooks;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];
        $website = $this->helper->getWebsite();
        $apiEnabled = $this->helper->isEnabled($website);

        //get address books options
        if ($apiEnabled) {
            $addressBooks = $this->getAddressBooks();
            //set the error message to the select option
            if (isset($addressBooks->message)) {
                $fields[] = [
                    'value' => 0,
                    'label' => $addressBooks->message,
                ];
            }

            $subscriberAddressBook
                = $this->helper->getSubscriberAddressBook($this->helper->getWebsite());

            //set up fields with book id and label
            foreach ($addressBooks as $book) {
                if (isset($book->id) && $book->visibility == 'Public'
                    && $book->id != $subscriberAddressBook
                ) {
                    $fields[] = [
                        'value' => $book->id,
                        'label' => $book->name,
                    ];
                }
            }
        }

        return $fields;
    }
}
