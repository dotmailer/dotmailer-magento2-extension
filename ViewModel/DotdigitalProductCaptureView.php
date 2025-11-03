<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation\ProductAggregationInterface;
use Dotdigitalgroup\Email\Model\Product\Aggregation\TrackingProductAggregationFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class DotdigitalProductCaptureView
 *
 * ViewModel for capturing product details for Dotdigital.
 */
class DotdigitalProductCaptureView implements ArgumentInterface
{
    /**
     * @var TrackingProductAggregationFactory
     */
    private $trackingProductAggregationFactory;

    /**
     * Constructor
     *
     * @param TrackingProductAggregationFactory $trackingProductAggregationFactory
     */
    public function __construct(
        TrackingProductAggregationFactory $trackingProductAggregationFactory
    ) {
        $this->trackingProductAggregationFactory = $trackingProductAggregationFactory;
    }

    /**
     * Format product details into an array.
     *
     * @return ProductAggregationInterface
     */
    public function product(): ProductAggregationInterface
    {
        return $this->trackingProductAggregationFactory->create();
    }
}
