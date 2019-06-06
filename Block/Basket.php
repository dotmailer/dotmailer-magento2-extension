<?php

namespace Dotdigitalgroup\Email\Block;

use Magento\Catalog\Model\Product;

/**
 * Basket block
 *
 * @api
 */
class Basket extends Recommended
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    public $quote;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    public $quoteFactory;

    /**
     * @var \Magento\Store\Model\App\EmulationFactory
     */
    public $emulationFactory;

    /**
     * Basket constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Magento\Store\Model\App\EmulationFactory $emulationFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Magento\Store\Model\App\EmulationFactory $emulationFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        array $data = []
    ) {
        $this->quoteFactory     = $quoteFactory;
        $this->helper           = $helper;
        $this->priceHelper      = $priceHelper;
        $this->emulationFactory = $emulationFactory;

        parent::__construct($context, $font, $urlFinder, $data);
    }

    /**
     * Basket items.
     *
     * @return array
     */
    public function getBasketItems()
    {
        $params = $this->getRequest()->getParams();

        if (! isset($params['quote_id']) ||
            ! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            $this->helper->log('Abandoned cart not found or invalid code');

            return false;
        }
        $quoteId = (int) $params['quote_id'];
        $quoteModel = $this->quoteFactory->create()
            ->loadByIdWithoutStore($quoteId);

        //check for any quote for this email, don't want to render further
        if (!$quoteModel->getId()) {
            $this->helper->log('no quote found for ' . $quoteId);

            return false;
        }
        if (!$quoteModel->getIsActive()) {
            $this->helper->log('Cart is not active : ' . $quoteId);

            return false;
        }

        $this->quote = $quoteModel;

        //Start environment emulation of the specified store
        $storeId = $quoteModel->getStoreId();

        $appEmulation = $this->emulationFactory->create();
        $appEmulation->startEnvironmentEmulation($storeId);

        $quoteItems = $quoteModel->getAllItems();

        $itemsData = [];

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */

        $parentProductIds = [];

        //Collect all parent ids to identify later which products to show in EDC
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getParentItemId() == null) {
                $parentProductIds[] = $quoteItem->getProduct()->getId();
            }
        }
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getParentItemId() != null) {
                //If a product is a bundle we don't need to show all parts of it.
                if ($quoteItem->getParentItem()->getProductType() == 'bundle') {
                    continue;
                }
                $itemsData[] =  $this->getItemDataForChildProducts($quoteItem);
                //Signal that we added a child product, so its parent must be ignored later.
                if (in_array($quoteItem->getParentItem()->getProduct()->getId(), $parentProductIds)) {
                    $key = array_search($quoteItem->getParentItem()->getProduct()->getId(), $parentProductIds);
                    unset($parentProductIds[$key]);
                }
            }
        }

        foreach ($quoteItems as $quoteItem) {
            //If a child product added already, we must not add it's parent.
            if ($quoteItem->getParentItemId() == null && in_array($quoteItem->getProduct()->getId(), $parentProductIds)) {
                $itemsData[] = $this->getItemDataForParentProducts($quoteItem);
            }
        }

        return $itemsData;
    }

    /**
     * @var \Magento\Quote\Model\Quote\Item $quoteItem
     * Get Item Data For Products who doesn't have child's
     * @return array
     */

    private function getItemDataForParentProducts($quoteItem)
    {
        $_product = $quoteItem->getProduct();
        return $this->getItemsData($quoteItem, $_product, $_parentProduct = null);
    }

    /**
     * @var \Magento\Quote\Model\Quote\Item $quoteItem
     * Get Item Data For Products who do have child's
     * @return array
     */

    private function getItemDataForChildProducts($quoteItem)
    {
        $_product = $quoteItem->getProduct();
        $_parentProduct = $quoteItem->getParentItem()->getProduct();

        return $this->getItemsData($quoteItem, $_product, $_parentProduct);
    }

    /**
     * Returns the itemsData array to be viewed;
     * @var \Magento\Quote\Model\Quote\Item $quoteItem
     * @param Product $_product
     * @param  Product$_parentProduct
     * @return array
     */

    private function getItemsData($quoteItem, $_product, $_parentProduct)
    {
        $totalPrice = (!isset($_parentProduct) ? $quoteItem->getBaseRowTotalInclTax() : $quoteItem->getParentItem()->getBaseRowTotalInclTax());
        $inStock = ($_product->isInStock())
            ? 'In Stock'
            : 'Out of stock';
        $total = $this->priceHelper->currency(
            $totalPrice,
            true,
            false
        );

        $productUrl = (isset($_parentProduct) ? $this->urlFinder->fetchFor($_parentProduct) : $this->urlFinder->fetchFor($_product));
        $grandTotal = $this->priceHelper->currency(
            $this->getGrandTotal(),
            true,
            false
        );
        $itemsData = [
            'grandTotal' => $grandTotal,
            'total' => $total,
            'inStock' => $inStock,
            'productUrl' => $productUrl,
            'product' => (isset($_parentProduct) ? $_parentProduct : $_product),
            'qty' => isset($_parentProduct) ? $quoteItem->getParentItem()->getQty() : $quoteItem->getQty(),
            'product_details' => $_product
        ];

        return $itemsData;
    }
    /**
     * Grand total.
     *
     * @return float
     */
    public function getGrandTotal()
    {
        return $this->quote->getGrandTotal();
    }

    /**
     * Grand total with currency
     *
     * @return string
     */
    public function getGrandTotalWithCurrency()
    {
        return $this->priceHelper->currency($this->getGrandTotal(), true, false);
    }

    /**
     * Url for "take me to basket" link.
     *
     * @return string
     */
    public function getUrlForLink()
    {
        return $this->quote->getStore()->getUrl(
            'connector/email/getbasket',
            ['quote_id' => $this->quote->getId()]
        );
    }

    /**
     * Can show go to basket url.
     *
     * @return bool
     */
    public function canShowUrl()
    {
        return (boolean)$this->quote->getStore()->getWebsite()->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LINK_ENABLED
        );
    }

    /**
     * @return string|boolean
     */
    public function takeMeToCartTextForUrl()
    {
        return $this->quote->getStore()->getWebsite()->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LINK_TEXT
        );
    }
}
