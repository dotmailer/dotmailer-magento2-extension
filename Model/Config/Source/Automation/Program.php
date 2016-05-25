<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Automation;

class Program
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var
     */
    protected $rest;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * Configuration structure.
     *
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $_configStructure;

    /**
     * Program constructor.
     *
     * @param \Magento\Framework\App\RequestInterface    $requestInterface
     * @param \Magento\Framework\Registry                $registry
     * @param \Dotdigitalgroup\Email\Helper\Data         $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_helper = $data;
        $this->_registry = $registry;
        $this->_request = $requestInterface;
        $this->_storeManager = $storeManager;
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
        $websiteName = $this->_request->getParam('website', false);
        $website = ($websiteName)
            ? $this->_storeManager->getWebsite($websiteName) : 0;
        //api client is enabled
        $apiEnabled = $this->_helper->isEnabled($website);
        if ($apiEnabled) {
            $client = $this->_helper->getWebsiteApiClient($website);
            $programs = $client->getPrograms();

            foreach ($programs as $one) {
                if (isset($one->id)) {
                    if ($one->status == 'Active') {
                        $fields[] = [
                            'value' => $one->id,
                            'label' => __($one->name)
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
