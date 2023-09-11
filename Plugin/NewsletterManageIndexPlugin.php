<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\Http;
use Magento\Framework\UrlFactory;
use Magento\Newsletter\Controller\Manage\Index;

class NewsletterManageIndexPlugin
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Http
     */
    private $response;

    /**
     * @var UrlFactory
     */
    private $urlFactory;

    /**
     * @param Configuration $config
     * @param Session $customerSession
     * @param Http $response
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        Configuration $config,
        Session $customerSession,
        Http $response,
        UrlFactory $urlFactory
    ) {
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->response = $response;
        $this->urlFactory = $urlFactory;
    }

    /**
     * Around execute.
     *
     * @param Index $subject
     * @param callable $proceed
     *
     * @return callable|void
     */
    public function aroundExecute(
        Index $subject,
        callable $proceed
    ) {
        $websiteId = $this->customerSession->getCustomer()->getWebsiteId();
        if (!$this->config->shouldRedirectToConnectorCustomerIndex($websiteId)) {
            return $proceed();
        }

        $this->response->setRedirect(
            $this->urlFactory->create()->getUrl('connector/customer/index')
        );
    }
}
