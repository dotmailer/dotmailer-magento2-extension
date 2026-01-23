<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\Model\Product\PwaUrlFinderInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Laminas\Uri\Http;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;

class PwaUrlFinder implements PwaUrlFinderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Http
     */
    private $zendUri;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $zendUri
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $zendUri
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->zendUri = $zendUri;
    }

    /**
     * Build a PWA URL for the given product
     *
     * By default, this method will either:
     * - Use the product's rewrite path if rewrites are enabled
     * - Or concatenate the PWA base URL with the product's URL key and .html extension
     *
     * Merchants can write a plugin for this method to customize the URL generation.
     *
     * @param string $pwaUrl The base PWA URL configured for the website
     * @param Product $product The product to build the URL for
     * @return string The complete PWA URL for the product
     */
    public function buildPwaProductUrl(string $pwaUrl, Product $product): string
    {
        $useRewrites = $this->scopeConfig->getValue(
            Config::XML_PATH_PWA_URL_REWRITES
        );

        $pwaUrl = rtrim($pwaUrl, '/') . '/';

        if ($useRewrites) {
            $uri = $this->zendUri->parse($product->getProductUrl());
            return $pwaUrl . ltrim($uri->getPath(), '/');
        }

        return $pwaUrl . $product->getUrlKey() . '.html';
    }
}
