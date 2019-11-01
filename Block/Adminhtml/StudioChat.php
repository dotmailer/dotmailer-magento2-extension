<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Dotdigitalgroup\Email\Model\Trial\TrialSetup;
use Dotdigitalgroup\Email\Model\Trial\TrialSetupFactory;
use Dotdigitalgroup\Email\Model\Chat\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\OauthValidator;

/**
 * Chat template
 *
 * @api
 */
class StudioChat extends \Magento\Backend\Block\Template implements EngagementCloudTrialInterface
{
    use HandlesMicrositeRequests;

    /**
     * @var TrialSetupFactory
     */
    private $trialSetupFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OauthValidator
     */
    private $oauth;

    /**
     * StudioChat constructor.
     * @param Context $context
     * @param TrialSetupFactory $trialSetupFactory
     * @param Data $helper
     * @param Config $config
     * @param OauthValidator $oauth
     */
    public function __construct(
        Context $context,
        TrialSetupFactory $trialSetupFactory,
        Data $helper,
        Config $config,
        OauthValidator $oauth
    ) {
        $this->trialSetupFactory = $trialSetupFactory;
        $this->helper = $helper;
        $this->config = $config;
        $this->oauth = $oauth;

        parent::__construct($context, []);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAction(): string
    {
        //If API Creds Aren't set
        if (!$this->helper->isEnabled()) {
            return $this->getTrialSetup()
                ->getEcSignupUrl($this->getRequest(), TrialSetup::SOURCE_CHAT);
        }

        return $this->oauth->createAuthorisedEcUrl($this->config->getChatPortalUrl());
    }
}
