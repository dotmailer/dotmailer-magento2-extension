<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * manages Subscriber data synced as contact.
 */
class Subscriber
{
    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    private $subscriber;

    /**
     * @var []
     */
    private $subscriberData;

    /**
     * @var []
     */
    private $mappingHash;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    private $categoryResource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;

    /**
     * @var \Magento\Eav\Model\ConfigFactory
     */
    private $eavConfigFactory;

    /**
     * Subscriber constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Eav\Model\ConfigFactory $eavConfigFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory
    ) {
        $this->helper = $helper;
        $this->_store = $storeManager;
        $this->categoryFactory = $categoryFactory;
        $this->productFactory = $productFactory;
        $this->categoryResource  = $categoryResource;
        $this->productResource = $productResource;
        $this->eavConfigFactory = $eavConfigFactory;
    }

    /**
     * Set key value data.
     *
     * @param mixed $data
     *
     * @return null
     */
    public function setData($data)
    {
        $this->subscriberData[] = $data;
    }

    /**
     * @param mixed $subscriber
     * @return void
     */
    public function setSubscriberData($subscriber)
    {
        $this->subscriber = $subscriber;
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
            $this->subscriberData[$key] = $value;
        }
    }

    /**
     * @param mixed $mappingHash
     * @return $this
     */
    public function setMappingHash($mappingHash)
    {
        $this->mappingHash = $mappingHash;
        return $this;
    }

    /**
     * @return array
     */
    public function getMappingHash()
    {
        return $this->mappingHash;
    }

    /**
     * export to CSV.
     *
     * @return array
     */
    public function toCSVArray()
    {
        $result = $this->subscriberData;
        return $result;
    }

    /**
     * get website name.
     *
     * @return string
     */
    public function getWebsiteName()
    {
        return $this->_getWebsiteName();
    }

    /**
     * get store name.
     *
     * @return null|string
     */
    public function getStoreName()
    {
        return $this->_getStoreName();
    }

    /**
     * get numbser of orders.
     *
     * @return string|int
     */
    public function getNumberOfOrders()
    {
        if ($this->subscriber->getNumberOfOrders()) {
            return $this->subscriber->getNumberOfOrders();
        }
        return '';
    }

    /**
     * get average order value.
     *
     * @return float|string
     */
    public function getAverageOrderValue()
    {
        if ($this->subscriber->getAverageOrderValue()) {
            return $this->subscriber->getAverageOrderValue();
        }
        return '';
    }

    /**
     * get total spend.
     *
     * @return float|string
     */
    public function getTotalSpend()
    {
        if ($this->subscriber->getTotalSpend()) {
            return $this->subscriber->getTotalSpend();
        }
        return '';
    }

    /**
     * get last order date.
     *
     * @return string
     */
    public function getLastOrderDate()
    {
        if ($this->subscriber->getLastOrderDate()) {
            return $this->subscriber->getLastOrderDate();
        }
        return '';
    }

    /**
     * get last order id.
     *
     * @return int|string
     */
    public function getLastOrderId()
    {
        if ($this->subscriber->getLastOrderId()) {
            return $this->subscriber->getLastOrderId();
        }
        return '';
    }

    /**
     * get last increment id
     *
     * @return int|string
     */
    public function getLastIncrementId()
    {
        if ($this->subscriber->getLastIncrementId()) {
            return $this->subscriber->getLastIncrementId();
        }
        return '';
    }

    /**
     * @return string
     */
    public function _getWebsiteName()
    {
        $storeId = $this->subscriber->getStoreId();
        $website = $this->_store->getWebsite(
            $this->_store->getStore($storeId)->getWebsiteId()
        );
        if ($website) {
            return $website->getName();
        }

        return '';
    }

    /**
     * @return string
     */
    public function _getStoreName()
    {
        $storeId = $this->subscriber->getStoreId();
        $store = $this->_store->getStore($storeId);

        if ($store) {
            return $store->getName();
        }

        return '';
    }

    /**
     * Get most purchased category.
     *
     * @return string
     */
    public function getMostPurCategory()
    {
        $categoryId = $this->subscriber->getMostCategoryId();
        return $this->getCategoryValue($categoryId);
    }

    /**
     * Get most purchased brand.
     *
     * @return string
     */
    public function getMostPurBrand()
    {
        $optionId = $this->subscriber->getMostBrand();
        $storeId = $this->subscriber->getStoreId();

        //attribute mapped from the config
        $attributeCode = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->_store->getStore($storeId)->getWebsiteId()
        );

        //if the id and attribute found
        if ($optionId && $attributeCode) {
            $attribute = $this->eavConfigFactory->create()
                ->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);

            $value = $attribute->setStoreId($storeId)
                ->getSource()
                ->getOptionText($optionId);

            //check for brand text
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
        $day = $this->subscriber->getWeekDay();
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
        $month = $this->subscriber->getMonthDay();
        if ($month) {
            return $month;
        }
        return "";
    }

    /**
     * Get first purchased category.
     *
     * @return string
     */
    public function getFirstCategoryPur()
    {
        $categoryId = $this->subscriber->getFirstCategoryId();
        return $this->getCategoryValue($categoryId);
    }

    /**
     * Get last purchased category.
     *
     * @return string
     */
    public function getLastCategoryPur()
    {
        $categoryId = $this->subscriber->getLastCategoryId();
        return $this->getCategoryValue($categoryId);
    }

    /**
     * Get category name from id
     *
     * @param int $categoryId
     * @return string
     */
    private function getCategoryValue($categoryId)
    {
        if ($categoryId) {
            $category = $this->categoryFactory->create()
                ->setStoreId($this->subscriber->getStoreId());
            $this->categoryResource->load($category, $categoryId);
            return $category->getName();
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
        $id = $this->subscriber->getProductIdForFirstBrand();
        return $this->getBrandValue($id);
    }

    /**
     * Get last purchased brand.
     *
     * @return string
     */
    public function getLastBrandPur()
    {
        $id = $this->subscriber->getProductIdForLastBrand();
        return $this->getBrandValue($id);
    }

    /**
     * @param mixed $id
     * @return string
     */
    private function getBrandValue($id)
    {
        $storeId = $this->subscriber->getStoreId();

        //attribute mapped from the config
        $attributeCode = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $this->_store->getStore($storeId)->getWebsiteId()
        );

        //if the id and attribute found
        if ($id && $attributeCode) {
            $product = $this->productFactory->create();
            $product = $product->setStoreId($storeId);
            $this->productResource->load($product, $id);

            $value = $product->getResource()
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
}
