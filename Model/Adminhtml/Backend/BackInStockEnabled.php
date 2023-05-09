<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Backend;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class BackInStockEnabled extends Value
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * BackInStockEnabled construct.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Data $helper
     * @param Account $account
     * @param WriterInterface $configWriter
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Data $helper,
        Account $account,
        WriterInterface $configWriter,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $config;
        $this->account = $account;
        $this->helper = $helper;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * After save.
     *
     * @throws \Exception
     */
    public function afterSave()
    {
        $websiteId = $this->helper->getWebsiteForSelectedScopeInAdmin()->getId();
        if ($this->isValueChanged() && $this->helper->isEnabled($websiteId)) {
            if ($this->getValue() && !$this->getStoredAccountId()) {
                $client = $this->helper->getWebsiteApiClient(
                    $websiteId
                );

                $accountId = $this->account->getAccountId(
                    $client->getAccountInfo($websiteId)
                );

                $this->configWriter->save(
                    Config::PATH_FOR_ACCOUNT_ID,
                    $accountId,
                    $this->getScope(),
                    $this->getScopeId()
                );
            }
        }

        return parent::afterSave();
    }

    /**
     * Get account id.
     *
     * @return string|null
     */
    private function getStoredAccountId(): ?string
    {
        return $this->scopeConfig->getValue(
            Config::PATH_FOR_ACCOUNT_ID,
            $this->getScope(),
            $this->getScopeId()
        );
    }
}
