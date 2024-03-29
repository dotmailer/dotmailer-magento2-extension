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
     * After execute.
     *
     * @param Index $subject
     * @param void $result
     *
     * @return void
     */
    public function afterExecute(
        Index $subject,
        $result
    ) {
        $websiteId = $this->customerSession->getCustomer()->getWebsiteId();
        if (!$this->config->shouldRedirectToConnectorCustomerIndex($websiteId)) {
            return $result;
        }

        $this->response->setRedirect(
            $this->urlFactory->create()->getUrl('connector/customer/index')
        );
    }
}
