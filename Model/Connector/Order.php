<?php

namespace Dotdigitalgroup\Email\Model\Connector;

/**
 * Class Order
 * @package Dotdigitalgroup\Email\Model\Connector
 */
class Order
{
    /**
     * Order Increment ID.
     *
     * @var string
     */
    public $id;
    /**
     * Email.
     *
     * @var string
     */
    public $email;
    /**
     * @var int
     */
    public $quoteId;
    /**
     * @var string
     */
    public $storeName;
    /**
     * @var string
     */
    public $purchaseDate;
    /**
     * @var array
     */
    public $deliveryAddress = [];
    /**
     * @var array
     */
    public $billingAddress = [];
    /**
     * @var array
     */
    public $products = [];
    /**
     * @var float
     */
    public $orderSubtotal;
    /**
     * @var float
     */
    public $discountAmount;
    /**
     * @var float
     */
    public $orderTotal;
    /**
     * Payment name.
     *
     * @var string
     */
    public $payment;
    /**
     * @var string
     */
    public $deliveryMethod;
    /**
     * @var float
     */
    public $deliveryTotal;
    /**
     * @var string
     */
    public $currency;
    /**
     * @var
     */
    public $couponCode;

    /**
     * @var array
     */
    public $custom = [];

    /**
     * @var string
     */
    public $orderStatus;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $localeDate;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    public $attributeCollection;
    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    public $setFactory;

    /**
     * Order constructor.
     *
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory                           $setFactory
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface                         $attributeSet
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection
     * @param \Magento\Catalog\Model\ProductFactory                                    $productFactory
     * @param \Magento\Customer\Model\CustomerFactory                                  $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                                       $helperData
     * @param \Magento\Store\Model\StoreManagerInterface                               $storeManagerInterface
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface                     $localeDate
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->attributeSet        = $attributeSet;
        $this->setFactory          = $setFactory;
        $this->attributeCollection = $attributeCollection;
        $this->productFactory      = $productFactory;
        $this->customerFactory     = $customerFactory;
        $this->helper              = $helperData;
        $this->localeDate          = $localeDate;
        $this->_storeManager       = $storeManagerInterface;
    }

    /**
     * Set the order data information.
     *
     * @param $orderData
     *
     * @return $this
     */
    public function setOrderData($orderData)
    {
        $this->id = $orderData->getIncrementId();
        $this->quoteId = $orderData->getQuoteId();
        $this->email = $orderData->getCustomerEmail();
        $this->storeName = $orderData->getStoreName();

        $this->purchaseDate = $this->localeDate->date($orderData->getCreatedAt())->format(\Zend_Date::ISO_8601);
        $this->deliveryMethod = $orderData->getShippingDescription();
        $this->deliveryTotal = (float)number_format(
            $orderData->getShippingAmount(),
            2,
            '.',
            ''
        );
        $this->currency = $orderData->getStoreCurrencyCode();

        if ($payment = $orderData->getPayment()) {
            $this->payment = $payment->getMethodInstance()->getTitle();
        }
        $this->couponCode = $orderData->getCouponCode();

        /*
         * custom order attributes
         */
        $website = $this->_storeManager->getStore($orderData->getStore())->getWebsite();

        $customAttributes
            = $this->helper->getConfigSelectedCustomOrderAttributes(
                $website
            );

        if ($customAttributes) {
            $fields = $this->helper->getOrderTableDescription();
            $this->custom = [];
            foreach ($customAttributes as $customAttribute) {
                if (isset($fields[$customAttribute])) {
                    $field = $fields[$customAttribute];
                    $value = $this->getCustomAttributeValue(
                        $field,
                        $orderData
                    );
                    if ($value) {
                        $this->assignCustom($field, $value);
                    }
                }
            }
        }

        /*
         * Billing address.
         */
        if ($orderData->getBillingAddress()) {
            $billingData = $orderData->getBillingAddress()->getData();
            $this->billingAddress = [
                'billing_address_1' => $this->getStreet(
                    $billingData['street'],
                    1
                ),
                'billing_address_2' => $this->getStreet(
                    $billingData['street'],
                    2
                ),
                'billing_city' => $billingData['city'],
                'billing_region' => $billingData['region'],
                'billing_country' => $billingData['country_id'],
                'billing_postcode' => $billingData['postcode'],
            ];
        }
        /*
         * Shipping address.
         */
        if ($orderData->getShippingAddress()) {
            $shippingData = $orderData->getShippingAddress()->getData();

            $this->deliveryAddress = [
                'delivery_address_1' => $this->getStreet(
                    $shippingData['street'],
                    1
                ),
                'delivery_address_2' => $this->getStreet(
                    $shippingData['street'],
                    2
                ),
                'delivery_city' => $shippingData['city'],
                'delivery_region' => $shippingData['region'],
                'delivery_country' => $shippingData['country_id'],
                'delivery_postcode' => $shippingData['postcode'],
            ];
        }

        $syncCustomOption = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_CUSTOM_OPTIONS,
            $website
        );

        /*
         * Order items.
         */
        foreach ($orderData->getAllItems() as $productItem) {
            //product custom options
            $customOptions = [];
            if ($syncCustomOption) {
                $customOptions = $this->getOrderItemOptions($productItem);
            }

            $productModel = $productItem->getProduct();

            if ($productModel) {
                // category names
                $categoryCollection = $productModel->getCategoryCollection()
                    ->addAttributeToSelect('name');
                $productCat = [];
                foreach ($categoryCollection as $cat) {
                    $categories = [];
                    $categories[] = $cat->getName();
                    $productCat[]['Name'] = substr(
                        implode(', ', $categories),
                        0,
                        244
                    );
                }

                $attributes = [];
                //selected attributes from config
                $configAttributes = $this->helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_ATTRIBUTES,
                    $orderData->getStore()->getWebsite()
                );

                if ($configAttributes) {
                    $configAttributes = explode(',', $configAttributes);
                    //attributes from attribute set
                    $attributesFromAttributeSet = $this->getAttributesArray(
                        $productModel->getAttributeSetId()
                    );

                    foreach ($configAttributes as $attributeCode) {
                        //if config attribute is in attribute set
                        if (in_array(
                            $attributeCode,
                            $attributesFromAttributeSet
                        )) {
                            //attribute input type
                            $inputType = $productModel->getResource()
                                ->getAttribute($attributeCode)
                                ->getFrontend()
                                ->getInputType();

                            //fetch attribute value from product depending on input type
                            switch ($inputType) {
                                case 'multiselect':
                                case 'select':
                                case 'dropdown':
                                    $value = $productModel->getAttributeText(
                                        $attributeCode
                                    );
                                    break;
                                case 'date':
                                    $value = $this->localeDate->date($productModel->getData($attributeCode))
                                        ->format(\Zend_Date::ISO_8601);
                                    break;
                                default:
                                    $value = $productModel->getData(
                                        $attributeCode
                                    );
                                    break;
                            }

                            if ($value && !is_array($value)) {
                                // check limit on text and assign value to array

                                $attributes[][$attributeCode]
                                    = $this->limitLength($value);
                            } elseif (is_array($value)) {
                                $value = implode($value, ', ');
                                $attributes[][$attributeCode]
                                    = $this->limitLength($value);
                            }
                        }
                    }
                }

                $attributeSetName = $this->getAttributeSetName($productModel);

                $productData = [
                    'name' => $productItem->getName(),
                    'sku' => $productItem->getSku(),
                    'qty' => (int)number_format(
                        $productItem->getData('qty_ordered'),
                        2
                    ),
                    'price' => (float)number_format(
                        $productItem->getPrice(),
                        2,
                        '.',
                        ''
                    ),
                    'attribute-set' => $attributeSetName,
                    'categories' => $productCat,
                    'attributes' => $attributes,
                    'custom-options' => $customOptions,
                ];
                if (! $customOptions) {
                    unset($productData['custom-options']);
                }
                $this->products[] = $productData;
            } else {
                // when no product information is available limit to this data
                $productData = [
                    'name' => $productItem->getName(),
                    'sku' => $productItem->getSku(),
                    'qty' => (int)number_format(
                        $productItem->getData('qty_ordered'),
                        2
                    ),
                    'price' => (float)number_format(
                        $productItem->getPrice(),
                        2,
                        '.',
                        ''
                    ),
                    'attribute-set' => '',
                    'categories' => [],
                    'attributes' => [],
                    'custom-options' => $customOptions,
                ];
                if (! $customOptions) {
                    unset($productData['custom-options']);
                }
                $this->products[] = $productData;
            }
        }

        $this->orderSubtotal = (float)number_format(
            $orderData->getData('subtotal'),
            2,
            '.',
            ''
        );
        $this->discountAmount = (float)number_format(
            $orderData->getData('discount_amount'),
            2,
            '.',
            ''
        );
        $orderTotal = abs(
            $orderData->getData('grand_total') - $orderData->getTotalRefunded()
        );
        $this->orderTotal = (float)number_format($orderTotal, 2, '.', '');
        $this->orderStatus = $orderData->getStatus();

        unset($this->_storeManager);

        return $this;
    }

    /**
     * Get the street name by line number.
     *
     * @param $street
     * @param $line
     *
     * @return string
     */
    public function getStreet($street, $line)
    {
        $street = explode("\n", $street);
        if ($line == 1) {
            return $street[0];
        }
        if (isset($street[$line - 1])) {
            return $street[$line - 1];
        } else {
            return '';
        }
    }

    /**
     * Exposes the class as an array of objects.
     *
     * @return array
     */
    public function expose()
    {
        return array_diff_key(
            get_object_vars($this),
            array_flip([
                '_storeManager',
                'localeDate',
                'helper',
                'customerFactory',
                'productFactory',
                'attributeCollection',
                'setFactory',
                'attributeSet'
            ])
        );
    }

    /**
     * Get attrubute value for the field.
     *
     * @param $field
     * @param $orderData
     *
     * @return float|int|null|string
     */
    public function getCustomAttributeValue($field, $orderData)
    {
        $type = $field['DATA_TYPE'];

        $function = 'get';
        $exploded = explode('_', $field['COLUMN_NAME']);
        foreach ($exploded as $one) {
            $function .= ucfirst($one);
        }

        $value = null;
        try {
            switch ($type) {
                case 'int':
                case 'smallint':
                    $value = (int)$orderData->$function();
                    break;

                case 'decimal':
                    $value = (float)number_format(
                        $orderData->$function(),
                        2,
                        '.',
                        ''
                    );
                    break;

                case 'timestamp':
                case 'datetime':
                case 'date':
                    $value = $this->localeDate->date($orderData->$function())->format(\Zend_Date::ISO_8601);
                    break;

                default:
                    $value = $orderData->$function();
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $value;
    }

    /**
     * Create property on runtime.
     *
     * @param $field
     * @param $value
     */
    public function assignCustom($field, $value)
    {
        $this->custom[$field['COLUMN_NAME']] = $value;
    }

    /**
     * Get attributes from attribute set.
     *
     * @param $attributeSetId
     *
     * @return array
     */
    public function getAttributesArray($attributeSetId)
    {
        $result = [];
        $attributes = $this->attributeCollection->create()
            ->setAttributeSetFilter($attributeSetId)
            ->getItems();

        foreach ($attributes as $attribute) {
            $result[] = $attribute->getAttributeCode();
        }

        return $result;
    }

    /**
     *  Check string length and limit to 250.
     *
     * @param $value
     *
     * @return string
     */
    public function limitLength($value)
    {
        if (strlen($value) > 250) {
            $value = substr($value, 0, 250);
        }

        return $value;
    }

    /**
     * Get options for the item.
     *
     * @return array
     */
    public function getOrderItemOptions($orderItem)
    {
        $orderItemOptions = $orderItem->getProductOptions();

        //if product doesn't have options
        if (!array_key_exists('options', $orderItemOptions)) {
            return [];
        }

        $orderItemOptions = $orderItemOptions['options'];

        //if product options isn't array
        if (!is_array($orderItemOptions)) {
            return [];
        }

        $options = [];

        foreach ($orderItemOptions as $orderItemOption) {
            if (array_key_exists('value', $orderItemOption)
                && array_key_exists(
                    'label',
                    $orderItemOption
                )
            ) {
                $label = str_replace(
                    ' ',
                    '-',
                    $orderItemOption['label']
                );
                $options[][$label] = $orderItemOption['value'];
            }
        }

        return $options;
    }

    /**
     * @return string
     */
    public function __sleep()
    {
        $properties = array_keys(get_object_vars($this));
        $properties = array_diff(
            $properties,
            [
                '_storeManager',
                'localeDate',
                'helper',
                'customerFactory',
                'productFactory',
                'attributeCollection',
                'setFactory',
                'attributeSet'
            ]
        );

        if (! $this->couponCode) {
            $properties = array_diff($properties, ['couponCode']);
        }

        if (! $this->custom) {
            $properties = array_diff($properties, ['custom']);
        }

        return $properties;
    }

    /**
     * Init not serializable fields.
     */
    public function __wakeup()
    {
    }

    /**
     * @param $product
     *
     * @return string
     */
    public function getAttributeSetName($product)
    {
        $attributeSetRepository = $this->attributeSet->get($product->getAttributeSetId());
        return $attributeSetRepository->getAttributeSetName();
    }
}
