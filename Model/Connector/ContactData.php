<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Helper\Config as ConfigHelper;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\ProductLoader;
use Dotdigitalgroup\Email\Model\Customer\DataField\Date;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\Model\AbstractModel;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var Product
     */
    private $productResource;

    /**
     * @var CategoryInterfaceFactory
     */
    private $categoryFactory;

    /**
     * @var Category
     */
    private $categoryResource;

    /**
     * @var StoreManagerInterface
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
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var Date
     */
    protected $dateField;

    /**
     * @var ProductLoader
     */
    protected $productLoader;

    /**
     * @var array
     */
    private $subscriberStatuses = [
        Subscriber::STATUS_SUBSCRIBED => 'Subscribed',
        Subscriber::STATUS_NOT_ACTIVE => 'Not Active',
        Subscriber::STATUS_UNSUBSCRIBED => 'Unsubscribed',
        Subscriber::STATUS_UNCONFIRMED => 'Unconfirmed',
    ];

    /**
     * ContactData constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Product $productResource
     * @param CategoryInterfaceFactory $categoryFactory
     * @param Category $categoryResource
     * @param ConfigHelper $configHelper
     * @param Logger $logger
     * @param Date $dateField
     * @param Config $eavConfig
     * @param ProductLoader $productLoader
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Product $productResource,
        CategoryInterfaceFactory $categoryFactory,
        Category $categoryResource,
        ConfigHelper $configHelper,
        Logger $logger,
        Date $dateField,
        Config $eavConfig,
        ProductLoader $productLoader
    ) {
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->categoryFactory = $categoryFactory;
        $this->productResource = $productResource;
        $this->categoryResource = $categoryResource;
        $this->logger = $logger;
        $this->dateField = $dateField;
        $this->eavConfig = $eavConfig;
        $this->productLoader = $productLoader;
    }

    /**
     * Initialize the model.
     *
     * @param AbstractModel $model
     * @param array $columns
     * @param array $categoryNames
     *
     * @return $this
     */
    public function init(AbstractModel $model, array $columns, array $categoryNames = [])
    {
        $this->model = $model;
        $this->columns = $columns;
        $this->contactData = [];
        $this->categoryNames = $categoryNames;
        return $this;
    }

    /**
     * Get contact data.
     *
     * @return array
     */
    public function getContactData()
    {
        return $this->contactData;
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
                ConfigHelper::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
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
     * Get the categories of all products purchased in the first order.
     *
     * @return string
     */
    public function getFirstCategoryPur()
    {
        $productIds = $this->model->getProductIdsForFirstOrder();
        if (empty($productIds)) {
            return '';
        }

        $categoryIds = $this->getCategoriesFromProducts($productIds);
        return $this->getCategoryNamesFromIds($categoryIds);
    }

    /**
     * Get the categories of all products purchased in the last order.
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $productIds = $this->model->getProductIdsForLastOrder();
        if (empty($productIds)) {
            return '';
        }

        $categoryIds = $this->getCategoriesFromProducts($productIds);
        return $this->getCategoryNamesFromIds($categoryIds);
    }

    /**
     * Get categories from historical orders.
     *
     * @param array $orderItems
     * @return array
     *
     * @deprecated Use the new private method
     * @see getCategoriesFromProducts
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
     *
     * @deprecated Use the new private method
     * @see getCategoryNamesFromIds
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
     * Get category names from category ids.
     *
     * @param array $categoryIds
     *
     * @return string
     */
    private function getCategoryNamesFromIds(array $categoryIds): string
    {
        $names = [];
        $storeId = $this->model->getStoreId();

        foreach ($categoryIds as $id) {
            if (!isset($this->categoryNames[$storeId][$id])) {
                // no match found for the category id in the current category tree
                continue;
            }
            $names[] = $this->categoryNames[$storeId][$id];
        }

        return (count($names)) ? implode(',', $names) : '';
    }

    /**
     * Get the brand value of the first product in the first order.
     *
     * @return string
     */
    public function getFirstBrandPur()
    {
        $ids = $this->model->getProductIdsForFirstOrder();
        if (empty($ids)) {
            return '';
        }
        $id = reset($ids);
        return empty($id) ? '' : $this->getBrandValue($id);
    }

    /**
     * Get the brand value of the first product in the last order.
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $ids = $this->model->getProductIdsForLastOrder();
        if (empty($ids)) {
            return '';
        }
        $id = reset($ids);
        return empty($id) ? '' : $this->getBrandValue($id);
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
            ConfigHelper::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->model->getWebsiteId()
        );

        //if the id and attribute found
        if ($productId && $attributeCode) {
            $product = $this->productLoader->getCachedProductById((int) $productId, (int) $this->model->getStoreId());
            if ($product && $product->getId()) {
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
            $product = $this->productLoader->getCachedProductById((int) $productId, (int) $this->model->getStoreId());
            //product found
            if ($product && $product->getId()) {
                $categoryIds = $product->getCategoryIds();
                if (count($categoryIds)) {
                    $categories = $this->getCategoryNamesFromIds($categoryIds);
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
            $product = $this->productLoader->getCachedProductById((int) $id, (int) $storeId);
            $attribute = $this->productResource->getAttribute($attributeCode);

            if ($attribute && $product && $product->getId()) {
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
                $value = $this->model->getData($attributeCode);
                if (!$value) {
                    $defaultValue = $attribute->getDefaultValue();
                    if ((string)$defaultValue != '') {
                        return $defaultValue;
                    }
                }

                return $value;
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

    /**
     * Get categories from an array of product ids.
     *
     * @param array $productIds
     *
     * @return array
     */
    private function getCategoriesFromProducts(array $productIds): array
    {
        $categoryIds = [];
        $products = $this->productLoader->getCachedProducts($productIds, (int) $this->model->getStoreId());

        foreach ($products as $product) {
            if ($product && $product->getId()) {
                foreach ($product->getCategoryIds() as $categoryId) {
                    if (!in_array($categoryId, $categoryIds)) {
                        $categoryIds[] = $categoryId;
                    }
                }
            }
        }
        return $categoryIds;
    }
}
