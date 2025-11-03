<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class DotdigitalEmailCaptureView implements ArgumentInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param SecureHtmlRenderer $secureRenderer
     * @param SerializerInterface $serializer
     * @param Context $context
     * @param FormKey $formKey
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        SecureHtmlRenderer $secureRenderer,
        SerializerInterface $serializer,
        Context $context,
        FormKey $formKey
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->secureRenderer = $secureRenderer;
        $this->serializer = $serializer;
        $this->context = $context;
        $this->formKey = $formKey;
    }

    /**
     * Render email capture configuration data.
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function renderConfig(): string
    {
        $layoutType = $this->getLayoutType();
        $configData = [
            'capture_url' => $this->getEmailCaptureUrl(),
            'form_key' => $this->formKey->getFormKey(),
            'layout' => $layoutType,
            'layout_postable' => $this->isEmailCaptureEnabled() ? $this->getPostableLayouts() : [],
            'layout_identifiers' => $this->getLayoutIdentifiers()
        ];

        return $this->secureRenderer->renderTag(
            'script',
            ['type' => 'application/json', 'id' => 'dotdigital-email-capture-config'],
            json_encode($configData),
            false
        );
    }

    /**
     * Render email capture script.
     *
     * @return string
     */
    public function renderScript(): string
    {
        return $this->secureRenderer->renderTag(
            "script",
            ['src' => $this->context->getAssetRepository()->getUrl(
                'Dotdigitalgroup_Email::js/emailCapture.js',
            )]
        );
    }

    /**
     * Layouts to capture email.
     *
     * @return string[]
     */
    public function getPostableLayouts(): array
    {
        return ['checkout_index_index'];
    }

    /**
     * Get layout identifiers.
     *
     * @return array[]
     * @throws LocalizedException
     */
    public function getLayoutIdentifiers(): array
    {
        return  json_decode(($this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_SELECTORS,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getId()
        )) ?? '[]', true);
    }

    /**
     * Get email capture url.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getEmailCaptureUrl()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        return $store->getUrl(
            Config::EASY_EMAIL_CAPTURE_ROUTE,
            ['_secure' => $store->isCurrentlySecure()]
        );
    }

    /**
     * Get the current page layout type
     *
     * @return string
     */
    private function getLayoutType(): string
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->context->getRequest();
        return $request->getFullActionName();
    }

    /**
     * Is email capture enabled (applies to checkout only).
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEmailCaptureEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getId()
        );
    }
}
