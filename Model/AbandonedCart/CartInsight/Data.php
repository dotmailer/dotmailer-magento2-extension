<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\AbandonedCart;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class Data
{
    /**
     * Basket prefix for accessing stored quotes
     */
    private const CONNECTOR_BASKET_PATH = 'connector/email/getbasket';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepository;

    /**
     * @var UrlFinder
     */
    private $urlFinder;

    /**
     * @var ImageFinder
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
     *
     * @param ClientFactory $clientFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param Emulation $appEmulation
     * @param DateTime $dateTime
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param UrlFinder $urlFinder
     * @param ImageFinder $imageFinder
     * @param Logger $logger
     * @param AbandonedCart $imageType
     * @param PriceCurrencyInterface $priceCurrencyInterface
     */
    public function __construct(
        ClientFactory $clientFactory,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Emulation $appEmulation,
        DateTime $dateTime,
        CartTotalRepositoryInterface $cartTotalRepository,
        UrlFinder $urlFinder,
        ImageFinder $imageFinder,
        Logger $logger,
        AbandonedCart $imageType,
        PriceCurrencyInterface $priceCurrencyInterface
    ) {
        $this->clientFactory = $clientFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->appEmulation = $appEmulation;
        $this->dateTime = $dateTime;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->urlFinder = $urlFinder;
        $this->imageFinder = $imageFinder;
        $this->logger = $logger;
        $this->imageType = $imageType;
        $this->priceCurrencyInterface = $priceCurrencyInterface;
    }

    /**
     * Send cart insight data via API client.
     *
     * @param Quote $quote
     * @param int $storeId
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send($quote, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $client = $this->clientFactory->create(
            ['data' => ['websiteId' => $store->getWebsiteId()]]
        );

        $this->appEmulation->startEnvironmentEmulation(
            $storeId,
            Area::AREA_FRONTEND,
            true
        );

        try {
            $payload = $this->getPayload($quote, $store);
        } catch (\Exception $e) {
            $this->logger->debug(
                sprintf('Error fetching cart insight data for quote ID: %s', $quote->getId()),
                [(string) $e]
            );
            $payload = [];
        }

        $this->appEmulation->stopEnvironmentEmulation();

        if (empty($payload)) {
            return;
        }

        try {
            $client->insightData->createOrUpdateContactCollectionRecord(
                'CartInsight',
                (string) $quote->getId(),
                'email',
                $quote->getCustomerEmail(),
                $payload
            );
        } catch (ResponseValidationException $e) {
            $this->logger->debug(
                sprintf(
                    '%s: %s',
                    'Error sending cart insight data',
                    $e->getMessage()
                ),
                [$e->getDetails()]
            );
        }
    }

    /**
     * Get payload data for API push.
     *
     * @param Quote $quote
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPayload($quote, $store)
    {
        $quoteCurrency = $quote->getQuoteCurrencyCode();
        $total = $this->cartTotalRepository->get($quote->getId());

        $data = [
            'cartId' => $quote->getId(),
            'cartUrl' => $this->getBasketUrl($quote->getId(), $store),
            'createdDate' => $this->dateTime->date(\DateTime::ATOM, $quote->getCreatedAt()),
            'modifiedDate' => $this->dateTime->date(\DateTime::ATOM, $quote->getUpdatedAt()),
            'currency' => $quoteCurrency,
            'subTotal' => round($quote->getSubtotal() ?: 0, 2),
            'subtotal_incl_tax' => round($total->getSubtotalInclTax() ?: 0, 2),
            'taxAmount' => round($quote->getShippingAddress()->getTaxAmount() ?: 0, 2),
            'shipping' => round($quote->getShippingAddress()->getShippingAmount() ?: 0, 2),
            'grandTotal' => round($quote->getGrandTotal() ?: 0, 2)
        ];

        $discountTotal = 0;
        $lineItems = [];
        $imageType = $this->imageType->getImageType($store->getWebsiteId());
        $visibleItems = $quote->getAllVisibleItems();

        foreach ($visibleItems as $item) {
            $product = $this->loadProduct($item, $store->getId());
            $discountTotal += $item->getDiscountAmount();

            $lineItems[] = [
                'sku' => $item->getSku(),
                'imageUrl' => $this->imageFinder->getCartImageUrl($item, $store->getId(), $imageType),
                'productUrl' => $this->urlFinder->fetchFor($product),
                'name' => $this->getItemName($item),
                'unitPrice' => $this->getConvertedPrice($product->getPrice(), $store->getId(), $quoteCurrency),
                'unitPrice_incl_tax' => $this->getUnitPriceIncTax($item, $product),
                'quantity' => $item->getQty(),
                'salePrice' => $this->getConvertedPrice($item->getBasePrice(), $store->getId(), $quoteCurrency),
                'salePrice_incl_tax' => round($item->getPriceInclTax() ?: 0, 2),
                'totalPrice' => round($item->getRowTotal() ?: 0, 2),
                'totalPrice_incl_tax' => round($item->getRowTotalInclTax() ?: 0, 2)
            ];
        }

        $data['discountAmount'] = (float) $discountTotal;
        $data['lineItems'] = $lineItems;
        $data['cartPhase'] = 'ORDER_PENDING';

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
     * @param Item $item
     * @param string|int $storeId
     * @return ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function loadProduct($item, $storeId)
    {
        try {
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
        } catch (\Exception $exception) {
            return $item->getProduct();
        }
    }

    /**
     * Get the quote item name.
     *
     * @param Item $item
     *
     * @return string
     * @throws \Exception
     */
    private function getItemName($item)
    {
        if (strlen((string) $item->getName()) === 0) {
            throw new \ErrorException(
                sprintf(
                    'Product name is missing for quote item id %d.',
                    $item->getItemId()
                )
            );
        }

        return $item->getName();
    }

    /**
     * Get unit price including tax.
     *
     * @param Item $item
     * @param ProductInterface $product
     * @return float
     */
    private function getUnitPriceIncTax($item, $product)
    {
        return round($product->getPrice() + ($product->getPrice() * ($item->getTaxPercent() / 100)), 2);
    }

    /**
     * Get price, converted to the quote's currency.
     *
     * @param mixed  $price
     * @param string $storeId
     * @param string $currencyCode
     * @return float
     */
    private function getConvertedPrice($price, string $storeId, string $currencyCode)
    {
        return $this->priceCurrencyInterface
            ->convertAndRound(
                (is_numeric($price)) ? $price : '0.00',
                $storeId,
                $currencyCode
            );
    }
}
