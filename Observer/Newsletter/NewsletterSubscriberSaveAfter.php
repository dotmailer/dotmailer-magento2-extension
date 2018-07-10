<?php

namespace Dotdigitalgroup\Email\Observer\Newsletter;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NewsletterSubscriberSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    private $configHelper;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;

    /**
     * @var \Magento\Framework\HTTP\Header
     */
    private $header;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $http;

    /**
     * @var \Dotdigitalgroup\Email\Model\ConsentFactory
     */
    private $consentFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Consent
     */
    private $consentResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * NewsletterSubscriberSaveAfter constructor.
     * @param \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\App\Request\Http $http
     * @param \Magento\Framework\HTTP\Header $header
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Request\Http $http,
        \Magento\Framework\HTTP\Header $header,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->http  = $http;
        $this->header = $header;
        $this->redirect = $redirect;
        $this->helper = $data;
        $this->configHelper = $this->helper->configHelperFactory->create();
        $this->timezone = $timezone;
        $this->storeManager   = $storeManagerInterface;
        $this->consentFactory = $consentFactory;
        $this->contactFactory = $contactFactory;
        $this->consentResource = $consentResource;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber->getEmail();
        $subscriberStatus = $subscriber->getSubscriberStatus();
        $websiteId = $this->storeManager->getStore($subscriber->getStoreId())
            ->getWebsiteId();

        //If not confirmed or not enabled.
        if ($subscriberStatus == \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED ||
            !$this->helper->isEnabled($websiteId) ||
            !$this->configHelper->isConsentSubscriberEnabled($websiteId)
        ) {
            return $this;
        }

        try {
            $contactEmail = $this->contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);
            $emailContactId = $contactEmail->getId();
            $consentModel = $this->consentFactory->create();

            $this->consentResource->load(
                $consentModel,
                $emailContactId,
                'email_contact_id'
            );

            //don't update the consent data for guest subscribers or not confrimed
            if (! $consentModel->isObjectNew()) {
                return $this;
            }

            $consentIp = $this->http->getClientIp();
            $consentUrl = $this->redirect->getRefererUrl();
            $consentUserAgent = $this->header->getHttpUserAgent();
            //save the consent data against the contact
            $consentModel->setEmailContactId($emailContactId)
                ->setConsentUrl($consentUrl)
                ->setConsentDatetime(time())
                ->setConsentIp($consentIp)
                ->setConsentUserAgent($consentUserAgent);

            //save contact
            $this->consentResource->save($consentModel);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
