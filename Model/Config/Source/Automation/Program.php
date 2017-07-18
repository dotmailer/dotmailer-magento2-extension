<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Automation;

class Program implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Program constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->helper        = $data;
        $this->registry     = $registry;
        $this->request       = $requestInterface;
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
        $website = ($websiteName)
            ? $this->storeManager->getWebsite($websiteName) : 0;
        //api client is enabled
        $apiEnabled = $this->helper->isEnabled($website);
        if ($apiEnabled) {
            $client = $this->helper->getWebsiteApiClient($website);
            $programs = $client->getPrograms();

            foreach ($programs as $one) {
                if (isset($one->id)) {
                    if ($one->status == 'Active') {
                        $fields[] = [
                            'value' => $one->id,
                            'label' => $one->name,
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
