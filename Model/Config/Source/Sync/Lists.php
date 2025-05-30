<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Source\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Lists\ListFetcher;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;

class Lists implements OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ListFetcher
     */
    private $listFetcher;

    /**
     * Lists constructor.
     *
     * @param Data $data
     * @param ListFetcher $listFetcher
     */
    public function __construct(
        Data $data,
        ListFetcher $listFetcher
    ) {
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

            $client = $this->helper->getWebsiteApiClient(
                (int) $this->helper->getWebsiteForSelectedScopeInAdmin()->getId()
            );
            $lists = $this->listFetcher->fetchAllLists($client);

            if (isset($lists->message)) {
                $fields[] = [
                    'value' => 0,
                    'label' => $lists->message
                ];
            }

            foreach ($lists as $list) {
                if (isset($list->id) && isset($list->name)) {
                    $fields[] = [
                        'value' => (string)$list->id,
                        'label' => (string)$list->name,
                    ];
                }
            }
        }

        return $fields;
    }
}
