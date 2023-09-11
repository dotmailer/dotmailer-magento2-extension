<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Lists\ListFetcher;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class Lists implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ListFetcher
     */
    private $listFetcher;

    /**
     * Lists constructor.
     *
     * @param Registry $registry
     * @param Data $data
     * @param ListFetcher $listFetcher
     */
    public function __construct(
        Registry $registry,
        Data $data,
        ListFetcher $listFetcher
    ) {
        $this->registry = $registry;
        $this->helper = $data;
        $this->listFetcher = $listFetcher;
    }

    /**
     * Retrieve list of options.
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray()
    {
        $fields = [];
        // Add a "Do Not Map" Option
        $fields[] = ['value' => 0, 'label' => '-- Please Select --'];

        $apiEnabled = $this->helper->isEnabled($this->helper->getWebsiteForSelectedScopeInAdmin());
        if ($apiEnabled) {
            $savedLists = $this->registry->registry('lists');
            if ($savedLists) {
                $lists = $savedLists;
            } else {
                $client = $this->helper->getWebsiteApiClient(
                    $this->helper->getWebsiteForSelectedScopeInAdmin()->getId()
                );
                $lists = $this->listFetcher->fetchAllLists($client);
                if ($lists) {
                    $this->registry->unregister('lists'); // additional measure
                    $this->registry->register('lists', $lists);
                }
            }

            foreach ($lists as $list) {
                if (isset($list->id) && isset($list->name)) {
                    $fields[] = [
                        'value' => (string) $list->id,
                        'label' => (string) $list->name,
                    ];
                }
            }
        }

        return $fields;
    }
}
