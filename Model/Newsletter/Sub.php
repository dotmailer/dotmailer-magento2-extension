<?php
namespace Dotdigitalgroup\Email\Model\Newsletter;

class Sub extends \Magento\Newsletter\Model\Subscriber
{
	protected $_helper;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Newsletter\Helper\Data $newsletterData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
		\Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
		\Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
		\Magento\Framework\Model\Resource\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	)
	{
		$this->_helper = $data;
		parent::__construct($context, $registry, $newsletterData, $scopeConfig, $transportBuilder, $storeManager,
			$customerSession, $customerAccountManagement, $inlineTranslation, $resource, $resourceCollection, $data);
	}
    public function sendConfirmationSuccessEmail()
    {
        if ($this->_helper->isNewsletterSuccessDisabled($this->getStoreId()))
            return $this;
        else
            parent::sendConfirmationSuccessEmail();
    }
}