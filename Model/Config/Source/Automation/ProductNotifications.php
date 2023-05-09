<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class ProductNotifications implements OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Program constructor.
     *
     * @param RequestInterface $requestInterface
     * @param Data $data
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestInterface $requestInterface,
        Data $data,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $data;
        $this->request = $requestInterface;
        $this->storeManager = $storeManager;
    }

    /**
     * Get options.
     *
     * @return array<int,array>
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $websiteId = $this->getWebsiteId();
        $availableFields[] = [
            'value' => '0',
            'label' => __('-- Please Select --')
        ];

        if (!$this->helper->isEnabled($websiteId)) {
            return $availableFields;
        }

        $client = $this->helper->getWebsiteApiClient($websiteId);
        $notifications = $client->getProductNotifications();
        if (is_array($notifications)) {
            $availableFields = array_reduce(
                $notifications,
                function (array $availableFields, $notification) {
                    $availableFields[] = [
                        'value' => $notification->id,
                        'label' => __($notification->name),
                    ];
                    return $availableFields;
                },
                $availableFields
            );
        }

        return $availableFields;
    }

    /**
     * Get website Identifier
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getWebsiteId(): int
    {
        $websiteName = $this->request->getParam('website', false);
        return ($websiteName)
            ? $this->storeManager->getWebsite($websiteName)->getId()
            : 0;
    }
}
