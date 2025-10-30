<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;

class CurrentProductProvider implements ProductProviderInterface
{
    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * @var ProductInterface|null
     */
    private $currentProduct = null;

    /**
     * Constructor
     *
     * @param CatalogHelper $catalogHelper
     */
    public function __construct(
        CatalogHelper $catalogHelper
    ) {
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @inheritDoc
     */
    public function getProduct(): ?ProductInterface
    {
        if (!isset($this->currentProduct)) {
            $this->currentProduct = $this->catalogHelper->getProduct();
        }
        return $this->currentProduct;
    }
}
