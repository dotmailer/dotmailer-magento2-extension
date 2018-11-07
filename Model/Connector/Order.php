<?php

namespace Dotdigitalgroup\Email\Model\Connector;

/**
 * Transactional data for orders, including mapped custom order attributes to sync.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
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
     * @var object
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
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $stringUtils;

    /**
     * Order constructor.
     *
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helperData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Stdlib\StringUtils $stringUtils
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Stdlib\StringUtils $stringUtils
    ) {
        $this->attributeSet        = $attributeSet;
        $this->setFactory          = $setFactory;
        $this->attributeCollection = $attributeCollection;
        $this->productFactory      = $productFactory;
        $this->customerFactory     = $customerFactory;
        $this->helper              = $helperData;
        $this->productResource     = $productResource;
        $this->_storeManager       = $storeManagerInterface;
        $this->stringUtils         = $stringUtils;
    }

    /**
     * Set the order data information.
     *
     * @param \Magento\Sales\Model\Order $orderData
     *
     * @return $this
     */
    public function setOrderData($orderData)
    {
        $this->id = $orderData->getIncrementId();
        $this->email = $orderData->getCustomerEmail();
        $this->quoteId = $orderData->getQuoteId();
        $this->storeName = $orderData->getStoreName();
        $this->purchaseDate = $orderData->getCreatedAt();
        $this->deliveryMethod = $orderData->getShippingDescription();
        $this->deliveryTotal = (float)number_format(
            $orderData->getShippingAmount(),
            2,
            '.',
            ''
        );
        $this->currency = $orderData->getStoreCurrencyCode();
        $payment = $orderData->getPayment();

        if ($payment) {
            if ($payment->getMethod()) {
                $methodInstance = $payment->getMethodInstance($payment->getMethod());
                if ($methodInstance) {
                    $this->payment = $methodInstance->getTitle();
                }
            }
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
                    $value = $this->_getCustomAttributeValue(
                        $field,
                        $orderData
                    );
                    if ($value) {
                        $this->_assignCustom($field, $value);
                    }
                }
            }
        }

        /*
         * Billing address.
         */
        $this->processBillingAddress($orderData);

        /*
         * Shipping address.
         */
        $this->processShippingAddress($orderData);

        $syncCustomOption = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_CUSTOM_OPTIONS,
            $website
        );

        /*
         * Order items.
         */
        $this->processOrderItems($orderData, $syncCustomOption);

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
     * @param \Magento\Sales\Model\Order $orderData
     *
     * @return null
     */
    private function processBillingAddress($orderData)
    {
        if ($orderData->getBillingAddress()) {
            $billingData = $orderData->getBillingAddress()->getData();
            $this->billingAddress = [
                'billing_address_1' => $this->_getStreet(
                    $billingData['street'],
                    1
                ),
                'billing_address_2' => $this->_getStreet(
                    $billingData['street'],
                    2
                ),
                'billing_city' => $billingData['city'],
                'billing_region' => $billingData['region'],
                'billing_country' => $billingData['country_id'],
                'billing_postcode' => $billingData['postcode'],
            ];
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $orderData
     *
     * @return null
     */
    private function processShippingAddress($orderData)
    {
        if ($orderData->getShippingAddress()) {
            $shippingData = $orderData->getShippingAddress()->getData();

            $this->deliveryAddress = [
                'delivery_address_1' => $this->_getStreet(
                    $shippingData['street'],
                    1
                ),
                'delivery_address_2' => $this->_getStreet(
                    $shippingData['street'],
                    2
                ),
                'delivery_city' => $shippingData['city'],
                'delivery_region' => $shippingData['region'],
                'delivery_country' => $shippingData['country_id'],
                'delivery_postcode' => $shippingData['postcode'],
            ];
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $orderData
     * @param boolean $syncCustomOption
     *
     * @return null
     */
    private function processOrderItems($orderData, $syncCustomOption)
    {
        foreach ($orderData->getAllItems() as $productItem) {
            //product custom options
            $customOptions = [];
            if ($syncCustomOption) {
                $customOptions = $this->_getOrderItemOptions($productItem);
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
                    $productCat[]['Name'] = $this->limitLength(
                        implode(', ', $categories)
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
                    $attributesFromAttributeSet = $this->_getAttributesArray(
                        $productModel->getAttributeSetId()
                    );

                    $attributes = $this->processConfigAttributes(
                        $configAttributes,
                        $attributesFromAttributeSet,
                        $productModel,
                        $attributes
                    );
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
                if (!$customOptions) {
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
                if (!$customOptions) {
                    unset($productData['custom-options']);
                }
                $this->products[] = $productData;
            }
        }
    }

    /**
     * @param mixed $configAttributes
     * @param mixed $attributesFromAttributeSet
     * @param mixed $productModel
     * @param mixed $attributes
     *
     * @return array
     */
    private function processConfigAttributes($configAttributes, $attributesFromAttributeSet, $productModel, $attributes)
    {
        foreach ($configAttributes as $attributeCode) {
            //if config attribute is in attribute set
            if (in_array($attributeCode, $attributesFromAttributeSet)) {
                //attribute input type
                $inputType = $this->productResource
                    ->getAttribute($attributeCode)
                    ->getFrontend()
                    ->getInputType();

                //fetch attribute value from product depending on input type
                switch ($inputType) {
                    case 'multiselect':
                    case 'select':
                    case 'dropdown':
                        $value = $productModel->getAttributeText($attributeCode);
                        break;
                    case 'date':
                        $value = $productModel->getData($attributeCode);
                        break;
                    default:
                        $value = $productModel->getData($attributeCode);
                        break;
                }

                $attributes = $this->processAttributeValue($attributes, $value, $attributeCode);
            }
        }
        return $attributes;
    }

    /**
     * @param array $attributes
     * @param string|array $value
     * @param string $attributeCode
     *
     * @return array
     */
    private function processAttributeValue($attributes, $value, $attributeCode)
    {
        if ($value && !is_array($value)) {
            // check limit on text and assign value to array
            $attributes[][$attributeCode] = $this->limitLength($value);
        } elseif ($value && is_array($value)) {
            $values = (isset($value['values']))? implode(',', $value['values']) : implode(',', $value);

            if ($values) {
                $attributes[][$attributeCode] = $this->limitLength($values);
            }
        }

        return $attributes;
    }

    /**
     * Get the street name by line number.
     *
     * @param string $street
     * @param int $line
     *
     * @return string
     */
    public function _getStreet($street, $line)
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
     * Return any exposed data that will included into the import as transactinoal data for Orders.
     *
     * @return array
     */
    public function expose()
    {
        $properties = array_diff_key(
            get_object_vars($this),
            array_flip([
                '_storeManager',
                'helper',
                'customerFactory',
                'productFactory',
                'attributeCollection',
                'setFactory',
                'attributeSet',
                'productResource'
            ])
        );
        //remove null/0/false values
        $properties = array_filter($properties);

        return $properties;
    }

    /**
     * Get attrubute value for the field.
     *
     * @param array $field
     * @param \Magento\Sales\Model\Order $orderData
     *
     * @return float|int|null|string
     */
    public function _getCustomAttributeValue($field, $orderData)
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
                    $value = $orderData->$function();
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
     * @param string $field
     * @param mixed $value
     *
     * @return null
     */
    public function _assignCustom($field, $value)
    {
        $this->custom[$field['COLUMN_NAME']] = $value;
    }

    /**
     * Get attributes from attribute set.
     *
     * @param int $attributeSetId
     *
     * @return array
     */
    public function _getAttributesArray($attributeSetId)
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
     * @param string $value
     *
     * @return string
     */
    private function limitLength($value)
    {
        if ($this->stringUtils->strlen($value) > \Dotdigitalgroup\Email\Helper\Data::DM_FIELD_LIMIT) {
            $value = mb_substr($value, 0, \Dotdigitalgroup\Email\Helper\Data::DM_FIELD_LIMIT);
        }

        return $value;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    public function _getOrderItemOptions($orderItem)
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
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getAttributeSetName($product)
    {
        $attributeSetRepository = $this->attributeSet->get($product->getAttributeSetId());
        return $attributeSetRepository->getAttributeSetName();
    }
}
