<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Newsletter manage index plugin for customer
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class NewsletterManageIndexPlugin
{
    /**
     * @var \Magento\Framework\App\Response\Http
     */
    private $response;

    /**
     * @var \Magento\Framework\UrlFactory
     */
    private $urlFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    public function __construct(
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\UrlFactory $urlFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->response = $response;
        $this->urlFactory = $urlFactory;
    }

    /**
     * @param \Magento\Newsletter\Controller\Manage\Index $subject
     * @param callable $proceed
     */
    public function aroundExecute(
        \Magento\Newsletter\Controller\Manage\Index $subject,
        callable $proceed
    ) {
        $websiteId = $this->customerSession->getCustomer()->getWebsiteId();
        $isEnabled = $this->helper->isEnabled($websiteId);
        $dataFields = $this->helper->getCanShowDataFields(
            $websiteId
        );
        $addressBooks = $this->helper->getCanShowAdditionalSubscriptions(
            $websiteId
        );
        $preferences = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SHOW_PREFERENCES,
            $websiteId
        );

        if ($isEnabled && ($dataFields || $addressBooks || $preferences)) {
            $this->response->setRedirect(
                $this->urlFactory->create()->getUrl('connector/customer/index')
            );
        } else {
            return $proceed();
        }
    }
}
