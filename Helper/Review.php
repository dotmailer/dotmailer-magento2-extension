<?php

namespace Dotdigitalgroup\Email\Helper;

class Review extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * configs
     */
    const XML_PATH_REVIEW_STATUS                                  = 'connector_automation_studio/review_settings/status';
    const XML_PATH_REVIEW_DELAY                                   = 'connector_automation_studio/review_settings/delay';
    const XML_PATH_REVIEW_NEW_PRODUCT                             = 'connector_automation_studio/review_settings/new_product';
    const XML_PATH_REVIEW_CAMPAIGN                                = 'connector_automation_studio/review_settings/campaign';
    const XML_PATH_REVIEW_ANCHOR                                  = 'connector_automation_studio/review_settings/anchor';
    const XML_PATH_REVIEW_DISPLAY_TYPE                            = 'connector_dynamic_content/products/review_display_type';


	protected $_context;
	protected $_helper;
	protected $_storeManager;
	protected $_objectManager;
	protected $_backendConfig;

	public function __construct(
		\Magento\Framework\App\Resource $adapter,
		\Magento\Framework\UrlInterface $urlBuilder,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Store\Model\StoreManagerInterface $storeManager
	)
	{
		$this->_adapter = $adapter;
		$this->_helper = $data;
		$this->_storeManager = $storeManager;
		$this->_objectManager = $objectManager;

		parent::__construct($context);
	}

	/**
     * get config value on website level
     *
     * @param $path
     * @param $website
     * @return mixed
     */
    public function getReviewWebsiteSettings($path, $website)
    {
        return $this->_helper->getWebsiteConfig($path, $website);
    }

    /**
     * @param $website
     * @return boolean
     */
    public function isEnabled($website)
    {
        return $this->getReviewWebsiteSettings(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_ENABLED, $website);
    }

    /**
     * @param $website
     * @return string
     */
    public function getOrderStatus($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_STATUS, $website);
    }

    /**
     * @param $website
     * @return int
     */
    public function getDelay($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_DELAY, $website);
    }

    /**
     * @param $website
     * @return boolean
     */
    public function isNewProductOnly($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_NEW_PRODUCT, $website);
    }

    /**
     * @param $website
     * @return int
     */
    public function getCampaign($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_CAMPAIGN, $website);
    }

    /**
     * @param $website
     * @return string
     */
    public function getAnchor($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_ANCHOR, $website);
    }

    /**
     * @param $website
     * @return string
     */
    public function getDisplayType($website)
    {
        return $this->getReviewWebsiteSettings(self::XML_PATH_REVIEW_DISPLAY_TYPE, $website);
    }
}
