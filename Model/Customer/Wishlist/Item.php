<?php

namespace Dotdigitalgroup\Email\Model\Customer\Wishlist;

class Item
{
    /**
     * @var
     */
    public $sku;
    /**
     * @var
     */
    public $qty;
    /**
     * @var
     */
    public $name;
    /**
     * @var
     */
    public $price;
    /**
     * @var
     */
    public $totalValueOfProduct;

    /**
     * @param $product
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
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        $this->qty = (int)$qty;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @return mixed
     */
    public function getTotalValueOfProduct()
    {
        return $this->totalValueOfProduct;
    }

    /**
     * @param $product
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
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param $sku
     *
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * @return mixed
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
