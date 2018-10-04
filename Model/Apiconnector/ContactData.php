<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * Manages data synced as contact.
 * @package Dotdigitalgroup\Email\Model\Apiconnector
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
    private $mappingHash;

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
    private $store;

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
        \Dotdigitalgroup\Email\Helper\Config $configHelper
    ) {
        $this->store = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->configHelper = $configHelper;
        $this->orderResource = $orderResource;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->productResource = $productResource;
        $this->categoryResource = $categoryResource;
        $this->eavConfigFactory = $eavConfigFactory;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->contactData[] = $data;
    }

    /**
     * @param $model
     */
    public function setContactData($model)
    {
        $this->model = $model;
        $mappingHash = array_keys($this->getMappingHash());
        foreach ($mappingHash as $key) {
            //Call user function based on the attribute mapped.
            $function = 'get';
            $exploded = explode('_', $key);
            foreach ($exploded as $one) {
                $function .= ucfirst($one);
            }
            $value = call_user_func(
                ['self', $function]
            );
            $this->contactData[$key] = $value;
        }
    }

    /**
     * @return array
     */
    public function getMappingHash()
    {
        return $this->mappingHash;
    }

    /**
     * @param mixed $mappingHash
     *
     * @return $this
     */
    public function setMappingHash($mappingHash)
    {
        $this->mappingHash = $mappingHash;

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
        $storeId = $this->model->getStoreId();
        $website = $this->store->getWebsite(
            $this->store->getStore($storeId)->getWebsiteId()
        );
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
        $storeId = $this->model->getStoreId();
        $store = $this->store->getStore($storeId);

        if ($store) {
            return $store->getName();
        }

        return '';
    }

    /**
     * @param mixed $id
     * @return string
     */
    public function getBrandValue($id)
    {
        //attribute mapped from the config
        $attributeCode = $this->configHelper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->model->getWebsiteId()
        );
        $storeId = $this->model->getStoreId();

        //if the id and attribute found
        if ($id && $attributeCode) {
            $product = $this->productFactory->create();
            $product = $product->setStoreId($storeId);
            $this->productResource->load($product, $id);

            $value = $this->productResource
                ->getAttribute($attributeCode)
                ->setStoreId($storeId)
                ->getSource()
                ->getOptionText($product->getData($attributeCode));

            //check for brand text
            if ($value) {
                return $value;
            }
        }

        return '';
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
        $catIds = [];
        //categories from all products
        foreach ($orderItems as $item) {
            $product = $item->getProduct();
            $categoryIds = $product->getCategoryIds();
            if (count($categoryIds)) {
                $catIds = array_unique(array_merge($catIds, $categoryIds));
            }
        }

        return $catIds;
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
            $categoryValue = $this->getCategoryValue($id);
            $names[$categoryValue] = $categoryValue;
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
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMostPurBrand()
    {
        $productId = $this->model->getProductIdForMostSoldProduct();
        $storeId = $this->model->getStoreId();
        $attributeCode = $this->configHelper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->store->getStore($storeId)->getWebsiteId()
        );

        //if the id and attribute found
        if ($productId && $attributeCode) {
            $product = $this->productFactory->create()
                ->setStoreId($storeId);
            $this->productResource->load($product, $productId);
            $value = $this->productResource->getAttribute($attributeCode)->getFrontend()->getValue($product);
            if ($value) {
                return $value;
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
            $product = $this->productFactory->create()
                ->setStoreId($this->model->getStoreId());
            $this->productResource->load($product, $productId);
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
}