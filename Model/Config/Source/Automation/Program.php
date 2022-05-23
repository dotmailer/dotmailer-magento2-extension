<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;

class Program implements \Magento\Framework\Data\OptionSourceInterface
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
     * @return array
     */
    public function toOptionArray()
    {
        $fields = [];
        $fields[] = ['value' => '0', 'label' => __('-- Disabled --')];
        $websiteName = $this->request->getParam('website', false);
        $websiteId = ($websiteName)
            ? $this->storeManager->getWebsite($websiteName)->getId() : 0;
        //api client is enabled
        $apiEnabled = $this->helper->isEnabled($websiteId);
        if ($apiEnabled) {
            $client = $this->helper->getWebsiteApiClient($websiteId);
            if ($programs = $client->getPrograms()) {
                foreach ($programs as $one) {
                    $id = $one->id ?? null;
                    $status = $one->status ?? null;
                    $name = $one->name ?? null;
                    if (($id) && $status == 'Active') {
                        $fields[] = [
                            'value' => $id,
                            'label' => $name,
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
