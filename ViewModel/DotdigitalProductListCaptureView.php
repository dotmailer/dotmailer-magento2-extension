<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation\ProductListAggregationInterface;
use Dotdigitalgroup\Email\Model\Product\Aggregation\TrackingProductListAggregationFactory;
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
     * Constructor
     *
     * @param TrackingProductListAggregationFactory $trackingProductListAggregationFactory
     */
    public function __construct(
        TrackingProductListAggregationFactory $trackingProductListAggregationFactory
    ) {
        $this->trackingProductListAggregationFactory = $trackingProductListAggregationFactory;
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
