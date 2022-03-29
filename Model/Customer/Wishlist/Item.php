<?php

namespace Dotdigitalgroup\Email\Model\Customer\Wishlist;

/**
 * Wishlist product item to sync.
 *
 */
class Item
{
    /**
     * @var string
     */
    public $sku;

    /**
     * @var int
     */
    public $qty;

    /**
     * @var string
     */
    public $name;

    /**
     * @var float
     */
    public $price;

    /**
     * @var float
     */
    public $totalValueOfProduct;

    /**
     * Set product.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return $this
     */
    public function setProduct($product)
    {
        $this->setSku($product->getSku());
        $this->setName($product->getName());

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set quantity.
     *
     * @param float $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        $this->qty = (int)$qty;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Get total value of product.
     *
     * @return float
     */
    public function getTotalValueOfProduct()
    {
        return $this->totalValueOfProduct;
    }

    /**
     * Set price (and update total item value).
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return $this
     */
    public function setPrice($product)
    {
        $this->price = (float)number_format($product->getFinalPrice(), 2, '.', '');
        $total = (int)$this->price * (int)$this->qty;

        $this->totalValueOfProduct = (float)number_format($total, 2, '.', '');

        return $this;
    }

    /**
     * Get price.
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set SKU.
     *
     * @param string $sku
     *
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get SKU.
     *
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Get this class's public properties.
     *
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }
}
