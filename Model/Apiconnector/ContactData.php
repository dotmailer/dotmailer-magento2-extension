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
    private $storeManager;

    /**
     * @var \Magento\Store\Model\Store
     */
    private $store;

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
        $this->storeManager = $storeManager;
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
     * @param $storeId
     *
     * @return \Magento\Store\Api\Data\StoreInterface|\Magento\Store\Model\Store
     */
    private function getStore($storeId)
    {
        if (! isset($this->store)) {
            $this->store = $this->storeManager->getStore($storeId);
        }

        return $this->store;
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
        $store = $this->getStore($this->model->getStoreId());
        $website = $store->getWebsite(
            $store->getWebsiteId()
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
        $store = $this->getStore($this->model->getStoreId());

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
        if (! isset($this->brandValue[$id])) {
            //attribute mapped from the config
            $attributeCode = $this->configHelper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
                $this->model->getWebsiteId()
            );
            $storeId = $this->model->getStoreId();
            $brandValue = $this->getAttributeValue($id, $attributeCode, $storeId);

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
        $catIds = [];
        //categories from all products
        foreach ($orderItems as $item) {
            $product = $item->getProduct();
            //sales item product may return null if product no longer exists, rather than empty object
            if ($product) {
                $categoryIds = $product->getCategoryIds();
                if (count($categoryIds)) {
                    $catIds = array_unique(array_merge($catIds, $categoryIds));
                }
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
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMostPurBrand()
    {
        $productId = $this->model->getProductIdForMostSoldProduct();
        $store = $this->getStore($this->model->getStoreId());
        $attributeCode = $this->configHelper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $store->getWebsiteId()
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
}
