<?php

class Dotdigitalgroup_Email_Model_Connector_Order
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

    /**
     * set the order information
     */
    public function __construct(Mage_Sales_Model_Order $orderData)
    {
        $customerModel = Mage::getModel('customer/customer');
        $customerModel->load($orderData->getCustomerId());

        $this->id           = $orderData->getIncrementId();
        $this->quote_id     = $orderData->getQuoteId();
        $this->email        = $orderData->getCustomerEmail();
        $this->store_name   = $orderData->getStoreName();

	    $created_at = new Zend_Date($orderData->getCreatedAt(), Zend_Date::ISO_8601);

	    $this->purchase_date = $created_at->toString(Zend_Date::ISO_8601);
        $this->delivery_method = $orderData->getShippingDescription();
        $this->delivery_total = $orderData->getShippingAmount();
        $this->currency = $orderData->getStoreCurrencyCode();

	    if ($payment = $orderData->getPayment())
            $this->payment = $payment->getMethodInstance()->getTitle();
        $this->couponCode = $orderData->getCouponCode();

        /**
         * custom order attributes
         */
        $helper = Mage::helper('ddg');
        $website = Mage::app()->getStore($orderData->getStore())->getWebsite();
        $customAttributes = $helper->getConfigSelectedCustomOrderAttributes($website);
        if($customAttributes){
            $fields = $helper->getOrderTableDescription();
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

        $syncCustomOption = $helper->getWebsiteConfig(
            Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_CUSTOM_OPTIONS,
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
	        $product = Mage::getModel('catalog/product')->load($productItem->getProductId());

	        if ($product) {
		        // category names
		        $categoryCollection = $product->getCategoryCollection()
		                                      ->addAttributeToSelect( 'name' );
                $productCat = array();
		        foreach ( $categoryCollection as $cat ) {
			        $categories                 = array();
			        $categories[]               = $cat->getName();
                    $productCat[]['Name'] = substr(implode(', ', $categories), 0, 244);
		        }

                $attributes = array();
                //selected attributes from config
                $configAttributes = Mage::helper('ddg')->getWebsiteConfig(
                    Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_ATTRIBUTES,
                    $orderData->getStore()->getWebsite()
                );
                if ($configAttributes) {
                    $configAttributes = explode(',', $configAttributes);
                    //attributes from attribute set
                    $attributesFromAttributeSet = $this->_getAttributesArray($product->getAttributeSetId());

                    foreach ($configAttributes as $attribute_code) {
                        //if config attribute is in attribute set
                        if (in_array($attribute_code, $attributesFromAttributeSet)) {
                            //attribute input type
                            $inputType = $product->getResource()
                                ->getAttribute($attribute_code)
                                ->getFrontend()
                                ->getInputType();

                            //fetch attribute value from product depending on input type
                            switch ($inputType) {
                                case 'multiselect':
                                case 'select':
                                case 'dropdown':
                                    $value = $product->getAttributeText($attribute_code);
                                    break;
                                default:
                                    $value = $product->getData($attribute_code);
                                    break;
                            }

                            if ($value) // check limit on text and assign value to array
                                $attributes[][$attribute_code] = $this->_limitLength($value);
                        }
                    }
                }

		        $attributeSetModel = Mage::getModel( "eav/entity_attribute_set" );
		        $attributeSetModel->load( $product->getAttributeSetId() );
		        $attributeSetName = $attributeSetModel->getAttributeSetName();
		        $this->products[] = array(
			        'name'          => $productItem->getName(),
			        'sku'           => $productItem->getSku(),
			        'qty'           => (int) number_format( $productItem->getData( 'qty_ordered' ), 2 ),
			        'price'         => (float) number_format( $productItem->getPrice(), 2, '.', '' ),
                    'attribute-set' => $attributeSetName,
                    'categories' => $productCat,
                    'attributes' => $attributes,
                    'custom-options' => $customOptions
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

        return true;
    }
    /**
     * get the street name by line number
     * @param $street
     * @param $line
     * @return string
     */
    private  function _getStreet($street, $line)
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

    private function _getCustomAttributeValue($field, $orderData)
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
                    $date = new Zend_Date($orderData->$function(), Zend_Date::ISO_8601);
                    $value = $date->toString(Zend_Date::ISO_8601);
                break;

                default:
                    $value = $orderData->$function();
            }
        }catch (Exception $e){
            Mage::logException($e);
        }

        return $value;
    }

    /** 
     * create property on runtime
     *
     * @param $field
     * @param $value
     */
    private function _assignCustom($field, $value)
    {
        $this->custom[$field['COLUMN_NAME']] = $value;
    }

    /**
     * get attributes from attribute set
     *
     * @param $attributeSetId
     * @return array
     */
    private function _getAttributesArray($attributeSetId)
    {
        $result = array();
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
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
    private function _limitLength($value)
    {
        if (strlen($value) > 250)
            $value = substr($value, 0, 250);

        return $value;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return array
     */
    private function _getOrderItemOptions($orderItem)
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
}
