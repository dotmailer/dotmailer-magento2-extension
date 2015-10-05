<?php

namespace Dotdigitalgroup\Email\Model\Connector;

class Order
{
    /**
     * Order Increment ID
     * @var string
     */
    public  $id;
    /**
     * Email
     * @var string
     */
    public  $email;
    /**
     * @var int
     */
    public  $quote_id;
    /**
     * @var string
     */
    public  $store_name;
    /**
     * @var string
     */
    public  $purchase_date;
    /**
     * @var string
     */
    public  $delivery_address;
    /**
     * @var string
     */
    public  $billing_address;
    /**
     * @var array
     */
    public  $products = array();
    /**
     * @var float
     */
    public  $order_subtotal;
    /**
     * @var float
     */
    public  $discount_ammount;
    /**
     * @var float
     */
    public  $order_total;
    /**
     * Payment name
     * @var string
     */
    public  $payment;
    /**
     * @var string
     */
    public  $delivery_method;
    /**
     * @var float
     */
    public  $delivery_total;
    /**
     * @var string
     */
    public  $currency;


    public $couponCode;

    /**
     * @var array
     */
    public  $custom = array();

    /**
     * @var string
     */
    public $order_status;
	protected $_datetime;
	protected $_helper;
	protected $_customerFactory;
	protected $_productFactory;
	protected $_attributeCollection;
	protected $_setFactory;

	public function __construct(
		\Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory,
		\Magento\Catalog\Model\Resource\Product\Attribute\CollectionFactory $attributeCollection,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Dotdigitalgroup\Email\Helper\Data $helperData,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\Stdlib\Datetime $datetime,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_setFactory = $setFactory;
		$this->_attributeCollection = $attributeCollection;
		$this->_productFactory = $productFactory;
		$this->_customerFactory = $customerFactory;
		$this->_helper = $helperData;
		$this->_datetime = $datetime;
		$this->_storeManager = $storeManagerInterface;
	}


	/**
	 * set the order data information
	 * @param $orderData
	 *
	 * @return $this
	 */
    public function setOrder( $orderData)
    {
        $this->id           = $orderData->getIncrementId();
        $this->quote_id     = $orderData->getQuoteId();
        $this->email        = $orderData->getCustomerEmail();
        $this->store_name   = $orderData->getStoreName();

		$created_at = new \DateTime($orderData->getCreatedAt());
	    $this->purchase_date = $this->_datetime->formatDate($created_at);

	    $this->delivery_method = $orderData->getShippingDescription();
        $this->delivery_total = $orderData->getShippingAmount();
        $this->currency = $orderData->getStoreCurrencyCode();

	    if ($payment = $orderData->getPayment())
            $this->payment = $payment->getMethodInstance()->getTitle();
        $this->couponCode = $orderData->getCouponCode();

        /**
         * custom order attributes
         */

        //@todo check if the website object is loading as it look missing
	    $website = $this->_storeManager->getStore($orderData->getStore())
		    ->getWebsite();
        $customAttributes = $this->_helper->getConfigSelectedCustomOrderAttributes($website);

        if($customAttributes){
            $fields = $this->_helper->getOrderTableDescription();
            foreach($customAttributes as $customAttribute){
                if(isset($fields[$customAttribute])){
                    $field = $fields[$customAttribute];
                    $value = $this->_getCustomAttributeValue($field, $orderData);
                    if($value)
                        $this->_assignCustom($field, $value);
                }
            }
        }

        /**
         * Billing address.
         */
        if ($orderData->getBillingAddress()) {
            $billingData  = $orderData->getBillingAddress()->getData();
            $this->billing_address = array(
                'billing_address_1' => $this->_getStreet($billingData['street'], 1),
                'billing_address_2' => $this->_getStreet($billingData['street'], 2),
                'billing_city'      => $billingData['city'],
                'billing_region'    => $billingData['region'],
                'billing_country'   => $billingData['country_id'],
                'billing_postcode'  => $billingData['postcode'],
            );
        }
        /**
         * Shipping address.
         */
        if ($orderData->getShippingAddress()) {
            $shippingData = $orderData->getShippingAddress()->getData();

            $this->delivery_address = array(
                'delivery_address_1' => $this->_getStreet($shippingData['street'], 1),
                'delivery_address_2' => $this->_getStreet($shippingData['street'], 2),
                'delivery_city'      => $shippingData['city'],
                'delivery_region'    => $shippingData['region'],
                'delivery_country'   => $shippingData['country_id'],
                'delivery_postcode'  => $shippingData['postcode']
            );
        }

        $syncCustomOption = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_CUSTOM_OPTIONS,
            $website
        );

        /**
         * Order items.
         */
        foreach ($orderData->getAllItems() as $productItem) {
            //product custom options
            $customOptions = array();
            if ($syncCustomOption)
                $customOptions = $this->_getOrderItemOptions($productItem);

	        //load product by product id, for compatibility
	        $productModel = $this->_productFactory->create()
		        ->load($productItem->getProductId());

	        if ($productModel) {
		        // category names
		        $categoryCollection = $productModel->getCategoryCollection()
			        ->addAttributeToSelect( 'name' );
                $productCat = array();
		        foreach ( $categoryCollection as $cat ) {
			        $categories                 = array();
			        $categories[]               = $cat->getName();
                    $productCat[]['Name'] = substr(implode(', ', $categories), 0, 244);
		        }

                $attributes = array();
                //selected attributes from config
                $configAttributes = $this->_helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_ATTRIBUTES,
                    $orderData->getStore()->getWebsite()
                );

                if ($configAttributes) {
                    $configAttributes = explode(',', $configAttributes);
                    //attributes from attribute set
                    $attributesFromAttributeSet = $this->_getAttributesArray($productModel->getAttributeSetId());

                    foreach ($configAttributes as $attribute_code) {
                        //if config attribute is in attribute set
                        if (in_array($attribute_code, $attributesFromAttributeSet)) {
                            //attribute input type
                            $inputType = $productModel->getResource()
                                ->getAttribute($attribute_code)
                                ->getFrontend()
                                ->getInputType();

                            //fetch attribute value from product depending on input type
                            switch ($inputType) {
                                case 'multiselect':
                                case 'select':
                                case 'dropdown':
                                    $value = $productModel->getAttributeText($attribute_code);
                                    break;
                                default:
                                    $value = $productModel->getData($attribute_code);
                                    break;
                            }

                            if ($value) // check limit on text and assign value to array
                                $attributes[][$attribute_code] = $this->_limitLength($value);
                        }
                    }
                }

		        $attributeSetName = $this->_setFactory->create()
		            ->load( $productModel->getAttributeSetId() )
		            ->getAttributeSetName();
		        $this->products[] = array(
			        'name'          => $productItem->getName(),
			        'sku'           => $productItem->getSku(),
			        'qty'           => (int) number_format( $productItem->getData( 'qty_ordered' ), 2 ),
			        'price'         => (float) number_format( $productItem->getPrice(), 2, '.', '' ),
                    'attribute-set' => $attributeSetName,
                    'categories'    => $productCat,
                    'attributes'    => $attributes,
                    'custom-options'=> $customOptions
		        );
	        } else {
		        // when no product information is available limit to this data
		        $this->products[] = array(
			        'name'          => $productItem->getName(),
			        'sku'           => $productItem->getSku(),
			        'qty'           => (int) number_format( $productItem->getData( 'qty_ordered' ), 2 ),
                    'price' => (float)number_format($productItem->getPrice(), 2, '.', ''),
                    'attribute-set' => '',
                    'categories' => array(),
                    'attributes' => array(),
                    'custom-options' => $customOptions
		        );
	        }
        }

        $this->order_subtotal   = (float)number_format($orderData->getData('subtotal'), 2 , '.', '');
        $this->discount_ammount = (float)number_format($orderData->getData('discount_amount'), 2 , '.', '');
        $orderTotal = abs($orderData->getData('grand_total') - $orderData->getTotalRefunded());
        $this->order_total      = (float)number_format($orderTotal, 2 , '.', '');
        $this->order_status = $orderData->getStatus();

	    unset($this->_storeManager);
        return $this;
    }
    /**
     * get the street name by line number
     * @param $street
     * @param $line
     * @return string
     */
    protected  function _getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if ($line == 1) {
            return $street[0];
        }
        if (isset($street[$line -1])) {

            return $street[$line - 1];
        } else {

            return '';
        }
    }

    /**
	 * exposes the class as an array of objects.
	 * @return array
	 */
    public function expose()
    {
        return get_object_vars($this);

    }

    protected function _getCustomAttributeValue($field, $orderData)
    {
        $type = $field['DATA_TYPE'];

        $function = 'get';
        $exploded = explode('_', $field['COLUMN_NAME']);
        foreach ($exploded as $one) {
            $function .= ucfirst($one);
        }

        $value = null;
        try{
            switch ($type) {
                case 'int':
                case 'smallint':
                    $value = (int)$orderData->$function();
                    break;

                case 'decimal':
                    $value = (float)number_format($orderData->$function(), 2 , '.', '');
                    break;

                case 'timestamp':
                case 'datetime':
                case 'date':
                    $date = new \Zend_Date($orderData->$function(), \Zend_Date::ISO_8601);
                    $value = $date->toString(\Zend_Date::ISO_8601);
                break;

                default:
                    $value = $orderData->$function();
            }
        }catch (\Exception $e){
			$this->_helper->debug((string)$e, array());
        }

        return $value;
    }

    /** 
     * create property on runtime
     *
     * @param $field
     * @param $value
     */
    protected function _assignCustom($field, $value)
    {
        $this->custom[$field['COLUMN_NAME']] = $value;
    }

    /**
     * get attributes from attribute set
     *
     * @param $attributeSetId
     * @return array
     */
    protected function _getAttributesArray($attributeSetId)
    {
        $result = array();
	    $attributes = $this->_attributeCollection->create()
            ->setAttributeSetFilter($attributeSetId)
            ->getItems();

        foreach ($attributes as $attribute) {
            $result[] = $attribute->getAttributeCode();
        }

        return $result;
    }

    /**
     *  check string length and limit to 250
     *
     * @param $value
     * @return string
     */
    protected function _limitLength($value)
    {
        if (strlen($value) > 250)
            $value = substr($value, 0, 250);

        return $value;
    }

    /**
     * @return array
     */
    protected function _getOrderItemOptions($orderItem)
    {
        $orderItemOptions = $orderItem->getProductOptions();

        //if product doesn't have options
        if (!array_key_exists('options', $orderItemOptions)) {
            return array();
        }

        $orderItemOptions = $orderItemOptions['options'];

        //if product options isn't array
        if (!is_array($orderItemOptions)) {
            return array();
        }

        $options = array();

        foreach ($orderItemOptions as $orderItemOption) {
            if (array_key_exists('value', $orderItemOption) && array_key_exists('label', $orderItemOption)) {
                $label = str_replace(' ', '-', $orderItemOption['label']);
                $options[][$label] = $orderItemOption['value'];
            }
        }

        return $options;
    }


	/**
	 * @return string[]
	 */
	public function __sleep()
	{
		$properties = array_keys(get_object_vars($this));
		$properties = array_diff($properties, ['_storeManager', '_datetime', '_helper', '_customerFactory', '_productFactory', '_attributeCollection', '_setFactory']);

		return $properties;
	}

	/**
	 * Init not serializable fields
	 *
	 * @return void
	 */
	public function __wakeup()
	{

	}
}
