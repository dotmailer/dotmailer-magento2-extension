<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight;

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
     * Data constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Dotdigitalgroup\Email\Model\Product\ImageFinder $imageFinder
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Dotdigitalgroup\Email\Model\Product\ImageFinder $imageFinder
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
        $this->urlFinder = $urlFinder;
        $this->imageFinder = $imageFinder;
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

        $payload = $this->getPayload($quote, $store);
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
        $data = [
            'key' => $quote->getId(),
            'contactIdentifier' => $quote->getCustomerEmail(),
            'json' => [
                'cartId' => $quote->getId(),
                'cartUrl' => $this->getBasketUrl($quote->getId(), $store),
                'createdDate' => $this->dateTime->date(\Zend_Date::ISO_8601, $quote->getCreatedAt()),
                'modifiedDate' => $this->dateTime->date(\Zend_Date::ISO_8601, $quote->getUpdatedAt()),
                'currency' => $quote->getQuoteCurrencyCode(),
                'subTotal' => round($quote->getSubtotal(), 2),
                'taxAmount' => round($quote->getShippingAddress()->getTaxAmount(), 2),
                'grandTotal' => round($quote->getGrandTotal(), 2)
            ]
        ];

        $discountTotal = 0;
        $lineItems = [];

        foreach ($quote->getAllVisibleItems() as $item) {

            $discountTotal += $item->getDiscountAmount();
            $product = $this->productRepository->getById($item->getProduct()->getId(), false, $store->getId());

            $mediaPath = $this->imageFinder->getProductImageUrl($item, $store);

            $lineItems[] = [
                'sku' => $item->getSku(),
                'imageUrl' => $this->urlFinder->getPath($mediaPath),
                'productUrl' => $this->urlFinder->fetchFor($product),
                'name' => $item->getName(),
                'unitPrice' => round($product->getPrice(), 2),
                'quantity' => $item->getQty(),
                'salePrice' => round($item->getBasePriceInclTax(), 2),
                'totalPrice' => round($item->getRowTotalInclTax(), 2)
            ];
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
     */
    private function getBasketUrl($quoteId, $store)
    {
        return $store->getUrl(
            self::CONNECTOR_BASKET_PATH,
            ['quote_id' => $quoteId]
        );
    }
}
