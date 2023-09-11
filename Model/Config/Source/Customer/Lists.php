<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Customer;

use Dotdigitalgroup\Email\Model\Lists\ListFetcher;
use Magento\Framework\Exception\LocalizedException;

class Lists implements \Magento\Framework\Data\OptionSourceInterface
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
     * @var ListFetcher
     */
    private $listFetcher;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\Registry $registry
     * @param ListFetcher $listFetcher
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Registry $registry,
        ListFetcher $listFetcher
    ) {
        $this->storeManager = $storeManagerInterface;
        $this->helper       = $data;
        $this->registry     = $registry;
        $this->listFetcher = $listFetcher;
    }

    /**
     * Get options.
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray()
    {
        $fields[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];
        $websiteId = $this->helper->getWebsiteForSelectedScopeInAdmin()->getId();
        $apiEnabled = $this->helper->isEnabled($websiteId);

        //get address books options
        if ($apiEnabled && $lists = $this->getLists($websiteId)) {
            //set the error message to the select option
            if (isset($lists->message)) {
                $fields[] = [
                    'value' => 0,
                    'label' => $lists->message,
                ];
            }

            $subscriberList = $this->helper->getSubscriberAddressBook($websiteId);

            foreach ($lists as $list) {
                if (isset($list->id)
                    && isset($list->visibility)
                    && isset($list->name)
                    && $list->visibility == 'Public'
                    && $list->id != $subscriberList
                ) {
                    $fields[] = [
                        'value' => $list->id,
                        'label' => $list->name,
                    ];
                }
            }
        }

        return $fields;
    }

    /**
     * Get lists.
     *
     * @param int $websiteId
     * @return array|mixed
     * @throws LocalizedException
     */
    private function getLists(int $websiteId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);

        $savedLists = $this->registry->registry('lists');
        //get saved address books from registry
        if ($savedLists) {
            $lists = $savedLists;
        } else {
            $lists = $this->listFetcher->fetchAllLists($client);
            $this->registry->unregister('lists'); // additional measure
            $this->registry->register('lists', $lists);
        }

        return $lists;
    }
}
