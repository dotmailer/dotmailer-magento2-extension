<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Dotdigitalgroup\Email\Logger\Logger;

/**
 * Manages data synced as contact.
 */
class ContactData
{
    /**
     * @var array
     */
    public $contactData;

    /**
     * @var Object
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
     * @var \Magento\Eav\Model\ConfigFactory
     */
    private $eavConfigFactory;

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
     * @var TimezoneInterfaceFactory
     */
    private $localeDateFactory;

    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ContactData constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Magento\Eav\Model\ConfigFactory $eavConfigFactory
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     * @param TimezoneInterfaceFactory $localeDateFactory
     * @param DateIntervalFactory $dateIntervalFactory
     * @param Logger $logger
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        TimezoneInterfaceFactory $localeDateFactory,
        DateIntervalFactory $dateIntervalFactory,
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->configHelper = $configHelper;
        $this->orderResource = $orderResource;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->productResource = $productResource;
        $this->categoryResource = $categoryResource;
        $this->eavConfigFactory = $eavConfigFactory;
        $this->localeDateFactory = $localeDateFactory;
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->logger = $logger;
    }

    public function init(AbstractModel $model, array $columns)
    {
        $this->model = $model;
        $this->columns = $columns;
        return $this;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->contactData[] = $data;
    }

    /**
     * Set column data on the customer model
     *
     * @return $this
     */
    public function setContactData()
    {
        foreach (array_keys($this->getColumns()) as $key) {
            switch ($key) {
                case 'dob':
                    $value = $this->model->getDob()
                        ? $this->getScopeAdjustedDob($this->model->getStoreId())
                        : null;
                    break;

                case 'email_type':
                    $value = 'Html';
                    break;

                default:
                    $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                    $value = method_exists($this, $method)
                        ? $this->$method()
                        : $this->model->$method();
            }

            $this->contactData[$key] = $value;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
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
        return array_values($this->contactData);
    }

    /**
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

            $brandValue = $this->getAttributeValue($id, $attributeCode, $this->model->getStoreId());

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
        $categoryIds = $this->getCategoriesFromOrderItems($order->getAllItems());

        return $this->getCategoryNames($categoryIds);
    }

    /**
     * @param $orderItems
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
        $categoryIds = $this->getCategoriesFromOrderItems($order->getAllItems());

        return $this->getCategoryNames($categoryIds);
    }

    /**
     * @param $categoryId
     * @return string
     */
    private function getCategoryValue($categoryId)
    {
        if ($categoryId) {
            $category = $this->categoryFactory->create()
                ->setStoreId($this->model->getStoreId());
            $this->categoryResource->load($category, $categoryId);
            return $category->getName();
        }

        return '';
    }

    /**
     * @param $categoryIds
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
     * @return AbstractModel
     */
    public function getModel()
    {
        return $this->model;
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
     * get most frequent day of purchase
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
     * get most frequent month of purchase
     *
     * @return string
     */
    public function getMostFreqPurMon()
    {
        $month = $this->model->getMonthDay();
        if ($month) {
            return $month;
        }

        return "";
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
     * @return int
     */
    public function getLastOrderId()
    {
        return $this->model->getLastOrderId();
    }

    /**
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
     * Get average order value.
     *
     * @return string
     */
    public function getAverageOrderValue()
    {
        return $this->model->getAverageOrderValue();
    }

    /**
     * @return int
     */
    public function getNumberOfOrders()
    {
        return $this->model->getNumberOfOrders();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->model->getId();
    }

    /**
     * @param int $id
     * @param string $attributeCode
     * @param int $storeId
     *
     * @return string
     */
    private function getAttributeValue($id, $attributeCode, $storeId)
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
     * @param string $storeId
     * @return string
     */
    private function getScopeAdjustedDob($storeId)
    {
        $scopedDob = $this->localeDateFactory->create()
            ->scopeDate(
                $storeId,
                strtotime($this->model->getDob()),
                true
            );

        $timezoneOffset = $scopedDob->getOffset();

        // For locales east of GMT i.e. +01:00 and up, return the raw date
        if ($timezoneOffset > 0) {
            return $this->model->getDob();
        }

        // For locales west of GMT i.e. -01:00 and below, adjust DOB by adding the current timezone offset
        $offset = $this->dateIntervalFactory->create(
            ['interval_spec' => 'PT' . abs($timezoneOffset) . 'S']
        );

        return $this->localeDateFactory->create()
            ->date(
                strtotime($this->model->getDob()),
                null,
                false
            )
            ->add($offset)
            ->format(\Zend_Date::ISO_8601);
    }
}
