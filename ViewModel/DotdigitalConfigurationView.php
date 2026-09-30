<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class DotdigitalConfigurationView implements ArgumentInterface
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param SecureHtmlRenderer $secureRenderer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        SecureHtmlRenderer $secureRenderer,
        StoreManagerInterface $storeManager,
    ) {
        $this->context = $context;
        $this->secureRenderer = $secureRenderer;
        $this->storeManager = $storeManager;
    }

    /**
     * Render configuration.
     *
     * @return string
     */
    public function renderConfig(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $configData = [
            'currencyCode' => $store->getCurrentCurrency()->getCode(),
            'locale' =>$store->getConfig('general/locale/code'),
            'storeCode' => $store->getCode(),
        ];

        return $this->secureRenderer->renderTag(
            'script',
            ['type' => 'application/json', 'id' => 'dotdigital-configuration-config'],
            json_encode($configData),
            false
        );
    }

    /**
     * Render script.
     *
     * @return string
     */
    public function renderScript(): string
    {
        return $this->secureRenderer->renderTag(
            "script",
            ['src' => $this->context->getAssetRepository()->getUrl(
                'Dotdigitalgroup_Email::js/configuration.js',
            )]
        );
    }
}
