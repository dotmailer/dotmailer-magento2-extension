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
     * @var string
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
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
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @return float
     */
    public function getTotalValueOfProduct()
    {
        return $this->totalValueOfProduct;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return $this
     */
    public function setPrice($product)
    {
        $this->price = (float)number_format($product->getFinalPrice(), 2, '.', '');
        $total = $this->price * $this->qty;

        $this->totalValueOfProduct = (float)number_format($total, 2, '.', '');

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
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
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }
}
