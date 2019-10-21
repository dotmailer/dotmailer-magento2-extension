<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Trial\TrialSetup;
use Dotdigitalgroup\Email\Model\Trial\TrialSetupFactory;
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
     * @var TrialSetup
     */
    private $trialSetup;

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
     * @param TrialSetupFactory $trialSetupFactory
     * @param OauthValidator $oauth
     */
    public function __construct(
        Config $config,
        Context $context,
        Data $helper,
        TrialSetupFactory $trialSetupFactory,
        OauthValidator $oauth
    ) {
        $this->config  = $config;
        $this->helper = $helper;
        $this->trialSetup = $trialSetupFactory->create();
        $this->oauth = $oauth;

        parent::__construct($context, []);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAction(): string
    {
        if (!($this->helper->getApiUsername() && $this->helper->getApiPassword())) {
            return $this->trialSetup->getEcSignupUrl($this->getRequest());
        }

        return $this->oauth->createAuthorisedEcUrl($this->config->getLoginUserUrl());
    }
}
