<?php

namespace Dotdigitalgroup\Email\Model\Customer;

/**
 * Handle the email confirmation.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountManagement extends \Magento\Customer\Model\AccountManagement
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Customer\Model\Metadata\Validator $validator
     * @param \Magento\Customer\Api\Data\ValidationResultsInterfaceFactory $validationResultsDataFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerMetadataInterface $customerMetadataService
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param \Magento\Framework\Stdlib\StringUtils $stringHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Helper\View $customerViewHelper
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Magento\Framework\DataObjectFactory $objectFactory
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Customer\Model\Metadata\Validator $validator,
        \Magento\Customer\Api\Data\ValidationResultsInterfaceFactory $validationResultsDataFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $customerMetadataService,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Customer\Model\Config\Share $configShare,
        \Magento\Framework\Stdlib\StringUtils $stringHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Helper\View $customerViewHelper,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Framework\DataObjectFactory $objectFactory,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct(
            $customerFactory,
            $eventManager,
            $storeManager,
            $mathRandom,
            $validator,
            $validationResultsDataFactory,
            $addressRepository,
            $customerMetadataService,
            $customerRegistry,
            $logger,
            $encryptor,
            $configShare,
            $stringHelper,
            $customerRepository,
            $scopeConfig,
            $transportBuilder,
            $dataProcessor,
            $registry,
            $customerViewHelper,
            $dateTime,
            $customerModel,
            $objectFactory,
            $extensibleDataObjectConverter
        );
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $redirectUrl
     * @return $this
     */
    protected function sendEmailConfirmation(\Magento\Customer\Api\Data\CustomerInterface $customer, $redirectUrl)
    {
        $storeId = $this->getWebsiteStoreId($customer);
        if ($this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_CUSTOMER_SUCCESS,
            'store',
            $storeId
        )
        ) {
            return $this;
        } else {
            parent::sendEmailConfirmation($customer, $redirectUrl);
        }
    }
}
