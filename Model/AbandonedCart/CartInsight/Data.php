<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\AbandonedCart;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Model\App\Emulation;

class Data
{
    /**
     * Basket prefix for accessing stored quotes
     */
    const CONNECTOR_BASKET_PATH = 'connector/email/getbasket';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UrlFinder
     */
    private $urlFinder;

    /**
     * @var \Dotdigitalgroup\Email\Model\Product\ImageFinder
     */
    private $imageFinder;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AbandonedCart
     */
    private $imageType;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrencyInterface;

    /**
     * Data constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param Emulation $appEmulation
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Dotdigitalgroup\Email\Model\Product\ImageFinder $imageFinder
     * @param Logger $logger
     * @param AbandonedCart $imageType
     * @param PriceCurrencyInterface $priceCurrencyInterface
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Emulation $appEmulation,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Dotdigitalgroup\Email\Model\Product\ImageFinder $imageFinder,
        Logger $logger,
        AbandonedCart $imageType,
        PriceCurrencyInterface $priceCurrencyInterface
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->appEmulation = $appEmulation;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
        $this->urlFinder = $urlFinder;
        $this->imageFinder = $imageFinder;
        $this->logger = $logger;
        $this->imageType = $imageType;
        $this->priceCurrencyInterface = $priceCurrencyInterface;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $storeId
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send($quote, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $client = $this->helper->getWebsiteApiClient($store->getWebsiteId());

        $this->appEmulation->startEnvironmentEmulation(
            $storeId,
            Area::AREA_FRONTEND,
            true
        );
        $payload = $this->getPayload($quote, $store);
        $this->appEmulation->stopEnvironmentEmulation();

        $client->postAbandonedCartCartInsight($payload);
    }

    /**
     * Get payload data for API push.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPayload($quote, $store)
    {
        $quoteCurrency = $quote->getQuoteCurrencyCode();

        $data = [
            'key' => $quote->getId(),
            'contactIdentifier' => $quote->getCustomerEmail(),
            'json' => [
                'cartId' => $quote->getId(),
                'cartUrl' => $this->getBasketUrl($quote->getId(), $store),
                'createdDate' => $this->dateTime->date(\Zend_Date::ISO_8601, $quote->getCreatedAt()),
                'modifiedDate' => $this->dateTime->date(\Zend_Date::ISO_8601, $quote->getUpdatedAt()),
                'currency' => $quoteCurrency,
                'subTotal' => round($quote->getSubtotal(), 2),
                'taxAmount' => round($quote->getShippingAddress()->getTaxAmount(), 2),
                'grandTotal' => round($quote->getGrandTotal(), 2)
            ]
        ];

        $discountTotal = 0;
        $lineItems = [];
        $imageType = $this->imageType->getImageType($store->getWebsiteId());

        foreach ($quote->getAllVisibleItems() as $item) {
            try {
                $product = $this->loadProduct($item, $store->getId());
                $discountTotal += $item->getDiscountAmount();

                $lineItems[] = [
                    'sku' => $item->getSku(),
                    'imageUrl' => $this->imageFinder->getCartImageUrl($item, $store->getId(), $imageType),
                    'productUrl' => $this->urlFinder->fetchFor($product),
                    'name' => $item->getName(),
                    'unitPrice' => $this->getConvertedPrice($product->getPrice(), $store->getId(), $quoteCurrency),
                    'unitPrice_incl_tax' => $this->getUnitPriceIncTax($item, $product),
                    'quantity' => $item->getQty(),
                    'salePrice' => $this->getConvertedPrice($item->getBasePrice(), $store->getId(), $quoteCurrency),
                    'salePrice_incl_tax' => round($item->getPriceInclTax(), 2),
                    'totalPrice' => round($item->getRowTotal(), 2),
                    'totalPrice_incl_tax' => round($item->getRowTotalInclTax(), 2)
                ];
            } catch (\Exception $e) {
                $this->logger->debug('Exception thrown when fetching CartInsight data', [(string) $e]);
            }
        }

        $data['json']['discountAmount'] = (float) $discountTotal;
        $data['json']['lineItems'] = $lineItems;
        $data['json']['cartPhase'] = 'ORDER_PENDING';

        return $data;
    }

    /**
     * Get basket URL for use in abandoned cart block in email templates.
     *
     * @param int $quoteId
     * @param \Magento\Store\Model\Store $store
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getBasketUrl($quoteId, $store)
    {
        return $store->getUrl(
            self::CONNECTOR_BASKET_PATH,
            ['quote_id' => $quoteId]
        );
    }

    /**
     * Fetching products by SKU is required for configurable products, in order to get
     * the correct unit price of any child products. For other types, this is not required.
     * For bundle products with dynamic SKUs, we *must* use getById().
     *
     * @param CartItemInterface $item
     * @param string|int $storeId
     * @return ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function loadProduct($item, $storeId)
    {
        switch ($item->getProductType()) {
            case 'configurable':
                return $this->productRepository->get(
                    $item->getSku(),
                    false,
                    $storeId
                );
            default:
                return $this->productRepository->getById(
                    $item->getProduct()->getId(),
                    false,
                    $storeId
                );
        }
    }

    /**
     * @param CartItemInterface $item
     * @param ProductInterface $product
     * @return float
     */
    private function getUnitPriceIncTax($item, $product)
    {
        return round($product->getPrice() + ($product->getPrice() * ($item->getTaxPercent() / 100)), 2);
    }

    /**
     * @param string $price
     * @param string $storeId
     * @param string $currencyCode
     * @return float
     */
    private function getConvertedPrice(string $price, string $storeId, string $currencyCode)
    {
        return $this->priceCurrencyInterface
            ->convertAndRound(
                $price,
                $storeId,
                $currencyCode
            );
    }
}
