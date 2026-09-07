<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class WebsiteName extends Column
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $websites = $this->storeManager->getWebsites();
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $websiteId = (int) ($item['website_id'] ?? 0);
                $item[$fieldName] = isset($websites[$websiteId])
                    ? $websites[$websiteId]->getName()
                    : __('Unknown');
            }
        }

        return $dataSource;
    }
}
