<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Customer\DataField\Date;
use Magento\Framework\Model\AbstractModel;

/**
 * Manages data synced as contact.
 */
class ContactData
{
    use CustomAttributesTrait;

    /**
     * @var array
     */
    protected $contactData;

    /**
     * @var AbstractModel
     */
    public $model;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    private $configHelper;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;

    /**
     * @var \Magento\Catalog\Api\Data\CategoryInterfaceFactory
     */
    private $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    private $categoryResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $brandValue = [];

    /**
     * @var array
     */
    private $categoryNames = [];

    /**
     * @var array
     */
    private $products = [];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var Date
     */
    protected $dateField;

    /**
     * @var array
     */
    private $subscriberStatuses = [
        \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED => 'Subscribed',
        \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE => 'Not Active',
        \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
        \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED => 'Unconfirmed',
    ];

    /**
     * ContactData constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     * @param Logger $logger
     * @param Date $dateField
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        Logger $logger,
        Date $dateField,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->configHelper = $configHelper;
        $this->orderResource = $orderResource;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->productResource = $productResource;
        $this->categoryResource = $categoryResource;
        $this->logger = $logger;
        $this->dateField = $dateField;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Initialize the model.
     *
     * @param AbstractModel $model
     * @param array $columns
     *
     * @return $this
     */
    public function init(AbstractModel $model, array $columns)
    {
        $this->model = $model;
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set column data on the customer model.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setContactData()
    {
        foreach (array_keys($this->getColumns()) as $key) {
            switch ($key) {
                case 'dob':
                    $value = $this->model->getDob()
                        ? $this->dateField->getScopeAdjustedDate(
                            $this->model->getStoreId(),
                            $this->model->getDob()
                        )
                        : null;
                    break;

                case 'email_type':
                    $value = 'Html';
                    break;

                default:
                    $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                    $value = method_exists($this, $method)
                        ? $this->$method()
                        : $this->getValue($key);
            }

            $this->contactData[$key] = $value;
        }

        return $this;
    }

    /**
     * Get the hydrated model.
     *
     * @return AbstractModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set columns.
     *
     * @param mixed $columns
     *
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Contact data array.
     *
     * @return array
     */
    public function toCSVArray()
    {
        return is_array($this->contactData) ? array_values($this->contactData) : [];
    }

    /**
     * Get website name.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getWebsiteName()
    {
        $website = $this->storeManager->getWebsite($this->model->getWebsiteId());

        if ($website) {
            return $website->getName();
        }

        return '';
    }

    /**
     * Get store name.
     *
     * @return string
     */
    public function getStoreName()
    {
        try {
            $store = $this->storeManager->getStore($this->model->getStoreId());
            return $store->getName();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->debug(
                'Requested store is not found. Store id: ' . $this->model->getStoreId(),
                [(string) $e]
            );
        }

        return '';
    }

    /**
     * Get the data field value for store name, when store view name is already mapped.
     *
     * @return string
     */
    public function getStoreNameAdditional()
    {
        try {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore($this->model->getStoreId());
            return $store->getGroup()->getName();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->debug(
                'Requested store is not found. Store id: ' . $this->model->getStoreId(),
                [(string) $e]
            );
        }

        return '';
    }

    /**
     * Get brand value.
     *
     * @param mixed $id
     * @return string
     */
    public function getBrandValue($id)
    {
        if (! isset($this->brandValue[$id])) {
            //attribute mapped from the config
            $attributeCode = $this->configHelper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
                $this->model->getWebsiteId()
            );

            $brandValue = $this->getBrandAttributeValue($id, $attributeCode, $this->model->getStoreId());

            if (is_array($brandValue)) {
                $this->brandValue[$id] = implode(',', $brandValue);
            } else {
                $this->brandValue[$id] = $brandValue;
            }
        }

        return $this->brandValue[$id];
    }

    /**
     * Get first purchased category.
     *
     * @return string
     */
    public function getFirstCategoryPur()
    {
        $firstOrderId = $this->model->getFirstOrderId();
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $firstOrderId);
        try {
            $orderItems = $order->getAllItems();
        } catch (\Exception $e) {
            $orderItems = [];
            $this->logger->debug(
                'Error fetching items for order ID: ' . $firstOrderId,
                [(string) $e]
            );
        }

        $categoryIds = $this->getCategoriesFromOrderItems($orderItems);

        return $this->getCategoryNames($categoryIds);
    }

    /**
     * Get categories from historical orders.
     *
     * @param array $orderItems
     * @return array
     */
    public function getCategoriesFromOrderItems($orderItems)
    {
        $catIds = $categoryIds = [];
        //categories from all products
        foreach ($orderItems as $item) {
            $product = $item->getProduct();
            //sales item product may return null if product no longer exists, rather than empty object
            if ($product) {
                $categoryIds[] = $product->getCategoryIds();
            }
        }

        foreach ($categoryIds as $array) {
            foreach ($array as $key => $value) {
                $catIds[] = $value;
            }
        }

        return array_unique($catIds);
    }

    /**
     * Get last purchased category.
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $lastOrderId = $this->model->getLastOrderId();
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $lastOrderId);

        try {
            $orderItems = $order->getAllItems();
        } catch (\Exception $e) {
            $orderItems = [];
            $this->logger->debug(
                'Error fetching items for order ID: ' . $lastOrderId,
                [(string) $e]
            );
        }

        $categoryIds = $this->getCategoriesFromOrderItems($orderItems);

        return $this->getCategoryNames($categoryIds);
    }

    /**
     * Get category value.
     *
     * @param string $categoryId
     * @return string
     */
    private function getCategoryValue($categoryId)
    {
        if ($categoryId) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $this->categoryFactory->create();
            $category->setStoreId($this->model->getStoreId());
            $this->categoryResource->load($category, $categoryId);
            return $category->getName();
        }

        return '';
    }

    /**
     * Get category names.
     *
     * @param array $categoryIds
     * @return string
     */
    public function getCategoryNames($categoryIds)
    {
        $names = [];
        foreach ($categoryIds as $id) {
            if (! isset($this->categoryNames[$id])) {
                $this->categoryNames[$id] = $this->getCategoryValue($id);
            }
            $names[$this->categoryNames[$id]] = $this->categoryNames[$id];
        }
        //comma separated category names
        if (count($names)) {
            return implode(',', $names);
        }

        return '';
    }

    /**
     * Get first purchased brand.
     *
     * @return string
     */
    public function getFirstBrandPur()
    {
        $id = $this->model->getProductIdForFirstBrand();
        return $this->getBrandValue($id);
    }

    /**
     * Get last purchased brand.
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->model->getProductIdForLastBrand();
        return $this->getBrandValue($id);
    }

    /**
     * Get most purchased brand.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMostPurBrand()
    {
        $productId = $this->model->getProductIdForMostSoldProduct();
        $attributeCode = $this->configHelper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->model->getWebsiteId()
        );

        //if the id and attribute found
        if ($productId && $attributeCode) {
            $product = $this->getProduct($productId);
            if ($product->getId()) {
                $attribute = $this->productResource->getAttribute($attributeCode);
                $value = is_object($attribute) ? $attribute->getFrontend()->getValue($product) : null;
                if ($value) {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Get most frequent day of purchase.
     *
     * @return string
     */
    public function getMostFreqPurDay()
    {
        $day = $this->model->getWeekDay();
        if ($day) {
            return $day;
        }

        return "";
    }

    /**
     * Get most frequent month of purchase.
     *
     * @return string
     */
    public function getMostFreqPurMon()
    {
        return $this->model->getMonth() ?: '';
    }

    /**
     * Get most purchased category.
     *
     * @return string
     */
    public function getMostPurCategory()
    {
        $categories = '';
        $productId = $this->model->getProductIdForMostSoldProduct();
        //sales data found for customer with product id
        if ($productId) {
            $product = $this->getProduct($productId);
            //product found
            if ($product->getId()) {
                $categoryIds = $product->getCategoryIds();
                if (count($categoryIds)) {
                    $categories = $this->getCategoryNames($categoryIds);
                }
            }
        }

        return $categories;
    }

    /**
     * Get last increment id.
     *
     * @return int
     */
    public function getLastIncrementId()
    {
        return $this->model->getLastIncrementId();
    }

    /**
     * Get last order id.
     *
     * @return int
     */
    public function getLastOrderId()
    {
        return $this->model->getLastOrderId();
    }

    /**
     * Get last order date.
     *
     * @return string
     */
    public function getLastOrderDate()
    {
        return $this->model->getLastOrderDate();
    }

    /**
     * Get total spend.
     *
     * @return string
     */
    public function getTotalSpend()
    {
        return $this->model->getTotalSpend();
    }

    /**
     * Total value refunded for the customer.
     *
     * @return float|int
     */
    public function getTotalRefund()
    {
        return $this->model->getTotalRefund();
    }

    /**
     * Get average order value.
     *
     * @return string
     */
    public function getAverageOrderValue()
    {
        return $this->model->getAverageOrderValue();
    }

    /**
     * Get number of orders.
     *
     * @return int
     */
    public function getNumberOfOrders()
    {
        return $this->model->getNumberOfOrders();
    }

    /**
     * Get the model's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->model->getId();
    }

    /**
     * Get the value of the nominated 'Brand' attribute.
     *
     * @param int $id
     * @param string $attributeCode
     * @param int $storeId
     *
     * @return string
     */
    private function getBrandAttributeValue($id, $attributeCode, $storeId)
    {
        //if the id and attribute found
        if ($id && $attributeCode) {
            $product = $this->productFactory->create();
            $product = $product->setStoreId($storeId);
            $this->productResource->load($product, $id);
            $attribute = $this->productResource->getAttribute($attributeCode);

            if ($attribute && $product->getId()) {
                $value = $attribute->setStoreId($storeId)
                    ->getSource()
                    ->getOptionText($product->getData($attributeCode));

                //check for brand text
                if ($value) {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Load a product by id.
     *
     * @param int $productId
     *
     * @return \Magento\Catalog\Model\Product
     */
    private function getProduct($productId)
    {
        if (! isset($this->products[$productId])) {
            $product = $this->productFactory->create()
                ->setStoreId($this->model->getStoreId());
            $this->productResource->load($product, $productId);
            $this->products[$productId] = $product;
        }

        return $this->products[$productId];
    }

    /**
     * Get the value of an attribute by code.
     *
     * @param string $attributeCode
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getValue($attributeCode)
    {
        $attribute = $this->eavConfig->getAttribute('customer', $attributeCode);

        switch ($attribute->getData('frontend_input')) {
            case 'select':
                return $this->getSelectedDropDownValue($attribute, $attributeCode);

            case 'multiselect':
                return $this->getMultiSelectValues($attribute, $attributeCode);

            default:
                //Text, Dates, Multilines, Boolean
                return $this->model->getData($attributeCode);
        }
    }

    /**
     * Subscriber status for email contact.
     *
     * @return string
     */
    public function getSubscriberStatus()
    {
        try {
            return $this->getSubscriberStatusString($this->model->getSubscriberStatus());
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug(
                'Error fetching subscriber status for contact ' . $this->model->getEmail(),
                [(string) $e]
            );
            return "";
        }
    }

    /**
     * Get subscriber status as a string (checking it matches an accepted value).
     *
     * @param string|int $statusCode
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getSubscriberStatusString($statusCode)
    {
        if (!array_key_exists((int) $statusCode, $this->subscriberStatuses)) {
            throw new \InvalidArgumentException();
        }
        return $this->subscriberStatuses[$statusCode];
    }
}
