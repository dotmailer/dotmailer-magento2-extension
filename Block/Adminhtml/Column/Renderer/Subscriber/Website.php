<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Subscriber;

class Website extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if($item['customer_id'] == 0 && $item['is_subscriber'] == 1) {
                    //Get store if from column store_id for item
                    $storeId = $item['store_id'];
                    //Get website id from store
                    $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
                    //Set correct website id
                    $item[$this->getData('name')] = $websiteId;
                }
            }
        }

        return $dataSource;
    }
}