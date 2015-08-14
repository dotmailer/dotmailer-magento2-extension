<?php

class Dotdigitalgroup_Email_Model_Customer_Wishlist_Item
{
	protected   $sku;
	protected   $qty;
	protected   $name;
	protected   $price;
    protected   $total_value_of_product;


	/**
	 * construnctor.
	 *
	 * @param $product
	 */
	public function __construct($product)
    {
        $this->setSku($product->getSku());
        $this->setName($product->getName());
    }

    /**
     * @param $name
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
        return $this->total_value_of_product;
    }

    /**
     * @param $product
     * @return $this
     */
    public function setPrice($product)
    {
        $this->price = $product->getFinalPrice();
        $total = $this->price * $this->qty;

        $this->total_value_of_product = number_format($total, 2, '.', ',');
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