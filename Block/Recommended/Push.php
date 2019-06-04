<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

/**
 * Push block
 *
 * @api
 */
class Push extends \Dotdigitalgroup\Email\Block\Recommended
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    public $recommnededHelper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    public $catalog;

    /**
     * Push constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Dotdigitalgroup\Email\Block\Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Block\Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->catalog = $catalog;
        $this->recommnededHelper = $recommended;
        $this->priceHelper = $priceHelper;

        parent::__construct($context, $font, $urlFinder, $data);
    }

    /**
     * Get the products to display for table.
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getLoadedProductCollection()
    {
        $params = $this->getRequest()->getParams();
        if (! isset($params['code']) || ! $this->helper->isCodeValid($params['code'])) {
            $this->helper->log('Product push no valid code is set');
            return [];
        }

        $mode = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $productIds = $this->recommnededHelper->getProductPushIds();
        $productCollection = $this->catalog->getProductCollectionFromIds($productIds, $limit);

        //important check the salable product in template
        return $productCollection;
    }

    /**
     * Display  type mode.
     *
     * @return boolean|string
     */
    public function getMode()
    {
        return $this->recommnededHelper->getDisplayType();
    }

    /**
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string|boolean
     */
    public function getTextForUrl($store)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
