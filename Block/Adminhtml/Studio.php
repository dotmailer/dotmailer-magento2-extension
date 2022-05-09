<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetupFactory;
use Dotdigitalgroup\Email\Helper\OauthValidator;
use Magento\Backend\Block\Template\Context;

/**
 * Automation studio block
 *
 * @api
 */
class Studio extends \Magento\Backend\Block\Template implements EngagementCloudTrialInterface
{
    use HandlesMicrositeRequests;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var IntegrationSetupFactory
     */
    private $integrationSetupFactory;

    /**
     * @var OauthValidator
     */
    private $oauth;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Studio constructor
     *
     * @param Config $config
     * @param Context $context
     * @param Data $helper
     * @param IntegrationSetupFactory $integrationSetupFactory
     * @param OauthValidator $oauth
     */
    public function __construct(
        Config $config,
        Context $context,
        Data $helper,
        IntegrationSetupFactory $integrationSetupFactory,
        OauthValidator $oauth
    ) {
        $this->config  = $config;
        $this->helper = $helper;
        $this->integrationSetupFactory = $integrationSetupFactory;
        $this->oauth = $oauth;

        parent::__construct($context, []);
    }

    /**
     * Get action.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAction(): string
    {
        if (!($this->helper->getApiUsername() && $this->helper->getApiPassword())) {
            return $this->getIntegrationSetup()
                ->getEcSignupUrl($this->getRequest());
        }

        return $this->oauth->createAuthorisedEcUrl($this->config->getLoginUserUrl());
    }
}
