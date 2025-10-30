<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * ViewModel class for rendering Dotdigital tags.
 */
class DotdigitalTagView implements ArgumentInterface
{
    /**
     * Context instance.
     *
     * @var Context
     */
    private $context;

    /**
     * SecureHtmlRenderer instance.
     *
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param SecureHtmlRenderer $secureRenderer
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        SecureHtmlRenderer $secureRenderer,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->context = $context;
        $this->secureRenderer = $secureRenderer;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Renders the Dotdigital tag.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function renderTag()
    {
        return $this->secureRenderer->renderTag(
            "script",
            ['src' => $this->context->getAssetRepository()->getUrl(
                'Dotdigitalgroup_Email::js/dotdigital-tag.js',
            )],
            null,
            false
        );
    }

    /**
     * Renders the Dotdigital script with the region and tag ID.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function renderScript(): string
    {
        return $this->secureRenderer->renderTag(
            'script',
            [],
            sprintf(
                'window.ddg.init("%s", "%s");',
                preg_replace(
                    '/^https?:\/\//',
                    '',
                    $this->getRegion()
                ),
                $this->getTagId()
            ),
            false
        );
    }

    /**
     * Determines if the Dotdigital tag should be rendered.
     *
     * @throws LocalizedException
     */
    public function shouldRender():bool
    {
        return $this->isWebTrackingEnabled();
    }

    /**
     * Retrieves the tag ID from the configuration.
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    private function getTagId(): ?string
    {
        return $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * Get the region prefix for the tracking URL.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getRegion(): string
    {
        return $this->helper->getTrackingRegionPrefix((int)$this->storeManager->getStore()->getWebsiteId());
    }

    /**
     * Is WBT enabled.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isWebTrackingEnabled(): bool
    {
        $wbt = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getId()
        );

        return !empty($wbt);
    }
}
