<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Order\OrderItemProcessorFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Transactional data for orders, including mapped custom order attributes to sync.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Order extends AbstractConnectorModel
{
    /**
     * Dotdigital order required schema
     */
    public const SCHEMA_RULES = [
        'orderTotal' => ':isFloat',
        'currency' => ':isString',
        'purchaseDate' => ':dateFormat',
        'orderSubtotal' => ':isFloat',
        'products' =>  [
            '*' => [
                'product_id' => ':isString',
                'parent_id' => ':isString',
                'name' => ':isString',
                'price' => ':isFloat',
                'sku' => ':isString',
                'qty' => ':isInt',
            ]
        ]
    ];

    /**
     * Order Increment ID.
     *
     * @var string
     */
    public $id;

    /**
     * Email address.
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
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var OrderItemProcessorFactory
     */
    private $orderItemProcessorFactory;

    /**
     * Order constructor.
     *
     * @param Data $helperData
     * @param Logger $logger
     * @param SchemaValidatorFactory $schemaValidatorFactory
     * @param OrderItemProcessorFactory $orderItemProcessorFactory
     */
    public function __construct(
        Data $helperData,
        Logger $logger,
        SchemaValidatorFactory $schemaValidatorFactory,
        OrderItemProcessorFactory $orderItemProcessorFactory
    ) {
        $this->helper = $helperData;
        $this->schemaValidator = $schemaValidatorFactory->create(['pattern'=>static::SCHEMA_RULES]);
        $this->logger = $logger;
        $this->orderItemProcessorFactory = $orderItemProcessorFactory;
    }

    /**
     * Set the order data information.
     *
     * @param mixed $orderData
     *
     * @return $this
     * @throws NoSuchEntityException
     * @throws ValidatorException
     * @throws SchemaValidationException|LocalizedException
     */
    public function setOrderData($orderData): Order
    {
        $this->id = $orderData->getIncrementId();
        $this->email = $orderData->getCustomerEmail();
        $this->quoteId = $orderData->getQuoteId();
        $this->storeName = $orderData->getStore()->getName();
        $this->purchaseDate = $orderData->getCreatedAt();
        $this->deliveryMethod = $orderData->getShippingDescription();
        $this->deliveryTotal = (float) number_format(
            (float) $orderData->getShippingAmount(),
            2,
            '.',
            ''
        );
        $this->currency = $orderData->getOrderCurrencyCode();

        /** @var OrderPaymentInterface|InfoInterface $payment */
        $payment = $orderData->getPayment();
        if ($payment) {
            if ($payment->getMethod()) {
                $methodInstance = $payment->getMethodInstance();
                if ($methodInstance) {
                    $this->payment = $methodInstance->getTitle();
                }
            }
        }

        $this->couponCode = (string) $orderData->getCouponCode();

        /*
         * custom order attributes
         */
        $customAttributes = $this->getConfigSelectedCustomOrderAttributes(
            $orderData->getStore()->getWebsite()->getId()
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

        $websiteId = $orderData->getStore()->getWebsiteId();

        $includeCustomOptions = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_PRODUCT_CUSTOM_OPTIONS,
            $websiteId
        );

        $orderItemProcessor = $this->orderItemProcessorFactory
            ->create(['data' => [
                'websiteId' => $websiteId,
                'includeCustomOptions' => $includeCustomOptions
            ]]);

        /*
         * Order items.
         */
        try {
            foreach ($orderData->getAllItems() as $productItem) {
                $productData = $orderItemProcessor->process($productItem);
                $this->mergeProductData($productData);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug(
                'Error processing items for order ID: ' . $orderData->getId(),
                [(string) $e]
            );
            $this->products = [];
        }

        $this->orderSubtotal = (float) number_format(
            (float) $orderData->getData('subtotal'),
            2,
            '.',
            ''
        );
        $this->discountAmount = (float) number_format(
            (float) $orderData->getData('discount_amount'),
            2,
            '.',
            ''
        );
        $orderTotal = abs(
            $orderData->getData('grand_total') - $orderData->getTotalRefunded()
        );
        $this->orderTotal = (float) number_format($orderTotal, 2, '.', '');
        $this->orderStatus = $orderData->getStatus();

        if (!$this->schemaValidator->isValid($this->toArray())) {
            throw new SchemaValidationException(
                $this->schemaValidator,
                __("Validation error")
            );
        }

        return $this;
    }

    /**
     * Merge product data.
     *
     * @param mixed $productData
     * @return void
     */
    private function mergeProductData($productData)
    {
        if (isset($productData["isChildOfBundled"])) {
            unset($productData["isChildOfBundled"]);
            end($this->products);
            $lastKey = key($this->products);
            $this->products[$lastKey]['sub_items'][] = $productData;
        } elseif (is_array($productData)) {
            $this->products[] = $productData;
        }
    }

    /**
     * Process order billing address.
     *
     * @param MagentoOrder $orderData
     *
     * @return void
     */
    private function processBillingAddress($orderData)
    {
        if ($billingAddress = $orderData->getBillingAddress()) {
            /** @var \Magento\Framework\Model\AbstractExtensibleModel $billingAddress */
            $billingData = $billingAddress->getData();

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
     * Process order shipping address.
     *
     * @param MagentoOrder $orderData
     *
     * @return void
     */
    private function processShippingAddress($orderData)
    {
        if ($shippingAddress = $orderData->getShippingAddress()) {
            /** @var \Magento\Framework\Model\AbstractExtensibleModel $shippingAddress */
            $shippingData = $shippingAddress->getData();
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
     * Get the street name by line number.
     *
     * @param string|null $street
     * @param int $line
     *
     * @return string
     */
    public function _getStreet(?string $street, $line)
    {
        $street = explode("\n", (string) $street);
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
     * Get attribute value for the field.
     *
     * @param array $field
     * @param MagentoOrder $orderData
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
                    $value = (int) $orderData->$function();
                    break;

                case 'decimal':
                    $value = (float) number_format(
                        (float) $orderData->$function(),
                        2,
                        '.',
                        ''
                    );
                    break;

                case 'timestamp':
                case 'datetime':
                case 'date':
                    $value = null;
                    if ($orderData->$function() !== null) {
                        $date = new \DateTime($orderData->$function());
                        $value = $date->format(\DateTime::ATOM);
                    }
                    break;

                default:
                    $value = $orderData->$function();
            }
        } catch (\Exception $e) {
            $this->logger->debug(
                'Error processing custom attribute values in order ID: ' . $orderData->getId(),
                [(string) $e]
            );
        }

        return $value;
    }

    /**
     * Create property on runtime.
     *
     * @param string $field
     * @param mixed $value
     *
     * @return void
     */
    private function _assignCustom($field, $value)
    {
        $this->custom[$field['COLUMN_NAME']] = $value;
    }

    /**
     * Get array of custom attributes for orders from config.
     *
     * @param int $websiteId
     *
     * @return array|bool
     */
    private function getConfigSelectedCustomOrderAttributes($websiteId)
    {
        $customAttributes = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES,
            $websiteId
        );
        return $customAttributes ? explode(',', $customAttributes) : false;
    }
}
