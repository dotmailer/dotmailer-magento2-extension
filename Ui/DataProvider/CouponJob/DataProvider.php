<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Ui\DataProvider\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\SalesRule\Helper\Coupon as CouponHelper;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /** coupon_type = 2 → "Specific Coupon" */
    private const COUPON_TYPE_SPECIFIC = 2;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var CouponHelper */
    private $couponHelper;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var RuleCollectionFactory */
    private $ruleCollectionFactory;

    /** @var array */
    protected $loadedData;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param UrlInterface $urlBuilder
     * @param CouponHelper $couponHelper
     * @param StoreManagerInterface $storeManager
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param Logger $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        UrlInterface $urlBuilder,
        CouponHelper $couponHelper,
        StoreManagerInterface $storeManager,
        RuleCollectionFactory $ruleCollectionFactory,
        Logger $logger,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection            = $collectionFactory->create();
        $this->urlBuilder            = $urlBuilder;
        $this->couponHelper          = $couponHelper;
        $this->storeManager          = $storeManager;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->logger                = $logger;
    }

    /**
     * Inject per-field config and options into meta.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getMeta()
    {
        $meta      = parent::getMeta();
        $websiteId = (int) $this->storeManager->getWebsite()->getId();

        $fieldConfig = [
            'sales_rule_id' => ['options' => $this->getSalesRuleOptions($websiteId)],
            'code_format'   => ['options' => $this->getCodeFormatOptions()],
            'data_field'    => [
                'searchUrl' => $this->urlBuilder->getUrl('dotdigitalgroup_email/couponjob/datafields'),
                'websiteId' => $websiteId,
            ],
            'audience'      => [
                'listsUrl'    => $this->urlBuilder->getUrl('dotdigitalgroup_email/couponjob/lists'),
                'segmentsUrl' => $this->urlBuilder->getUrl('dotdigitalgroup_email/couponjob/segments'),
                'websiteId'   => $websiteId,
            ],
        ];

        foreach ($fieldConfig as $fieldName => $config) {
            foreach ($config as $key => $value) {
                $meta['general']['children'][$fieldName]['arguments']['data']['config'][$key] = $value;
            }
        }

        return $meta;
    }

    /**
     * Get data — create form only.
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $websiteId = (int) $this->storeManager->getWebsite()->getId();
        $this->loadedData = ['' => [
            'website_id' => $websiteId,
            'code_length' => $this->couponHelper->getDefaultLength(),
            'code_dash' => $this->couponHelper->getDefaultDashInterval(),
            'expires_at' => '',
        ]];

        return $this->loadedData;
    }

    /**
     * Get code format options for dropdown.
     *
     * @return array
     */
    private function getCodeFormatOptions(): array
    {
        $options = [['value' => '', 'label' => (string) __('-- Please Select --')]];
        foreach ($this->couponHelper->getFormatsList() as $value => $label) {
            $options[] = ['value' => $value, 'label' => (string) $label];
        }
        return $options;
    }

    /**
     * Get sales rule options (specific coupon + auto generation only).
     *
     * @param int $websiteId
     * @return array
     */
    private function getSalesRuleOptions(int $websiteId = 0): array
    {
        $options = [['value' => '', 'label' => (string) __('-- Please Select --')]];
        try {
            $rules = $this->ruleCollectionFactory->create()
                ->addFieldToFilter('coupon_type', self::COUPON_TYPE_SPECIFIC)
                ->addFieldToFilter('use_auto_generation', 1)
                ->addWebsiteFilter($websiteId)
                ->addFieldToSelect(['rule_id', 'name']);
            foreach ($rules as $rule) {
                $options[] = ['value' => $rule->getRuleId(), 'label' => $rule->getName()];
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
        return $options;
    }
}
