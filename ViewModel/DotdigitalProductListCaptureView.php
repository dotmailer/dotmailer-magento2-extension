<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation\ProductListAggregationInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Product\Aggregation\TrackingProductListAggregationFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class DotdigitalProductListCaptureView
 *
 * ViewModel for capturing product details for Dotdigital.
 */
class DotdigitalProductListCaptureView implements ArgumentInterface
{
    /**
     * @var TrackingProductListAggregationFactory
     */
    private $trackingProductListAggregationFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor
     *
     * @param TrackingProductListAggregationFactory $trackingProductListAggregationFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TrackingProductListAggregationFactory $trackingProductListAggregationFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->trackingProductListAggregationFactory = $trackingProductListAggregationFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Determine whether product list capture is enabled for the current scope.
     *
     * @return bool
     */
    public function isProductListCaptureEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(Config::XML_PATH_CONNECTOR_PRODUCT_LIST_CAPTURE_ENABLED);
    }

    /**
     * Format product details into an array.
     *
     * @return ProductListAggregationInterface
     */
    public function products(): ProductListAggregationInterface
    {
        return $this->trackingProductListAggregationFactory->create();
    }
}
