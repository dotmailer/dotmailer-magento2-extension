<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Customer extends \Magento\Customer\Model\Customer
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\ResourceModel\Customer $resource
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressesFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Api\CustomerMetadataInterface $metadataService
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\ResourceModel\Customer $resource,
        \Magento\Customer\Model\Config\Share $configShare,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressesFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Api\CustomerMetadataInterface $metadataService,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $storeManager,
            $config,
            $scopeConfig,
            $resource,
            $configShare,
            $addressFactory,
            $addressesFactory,
            $transportBuilder,
            $groupRepository,
            $encryptor,
            $dateTime,
            $customerDataFactory,
            $dataObjectProcessor,
            $dataObjectHelper,
            $metadataService,
            $indexerRegistry,
            $resourceCollection,
            $data
        );
    }

    /**
     * Send email with new account related information
     *
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @return \Magento\Customer\Model\Customer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0')
    {
        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId($this->getSendemailStoreId());
        }

        if ($this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            'store',
            $storeId
        )
        ) {
            return $this;
        } else {
            return parent::sendNewAccountEmail($type, $backUrl, $storeId);
        }
    }
}
