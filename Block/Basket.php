<?php

namespace Dotdigitalgroup\Email\Block;

class Basket extends \Magento\Catalog\Block\Product\AbstractProduct
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
     * @var
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
     * @param \Magento\Store\Model\App\EmulationFactory $emulationFactory
     * @param \Magento\Quote\Model\QuoteFactory         $quoteFactory
     * @param \Magento\Catalog\Block\Product\Context    $context
     * @param \Dotdigitalgroup\Email\Helper\Data        $helper
     * @param \Magento\Framework\Pricing\Helper\Data    $priceHelper
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Store\Model\App\EmulationFactory $emulationFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        array $data = []
    ) {
        $this->quoteFactory     = $quoteFactory;
        $this->helper           = $helper;
        $this->priceHelper      = $priceHelper;
        $this->emulationFactory = $emulationFactory;

        parent::__construct($context, $data);
    }

    /**
     * Basket items.
     *
     * @return mixed
     */
    public function getBasketItems()
    {
        $params = $this->getRequest()->getParams();

        if (!isset($params['quote_id']) || !isset($params['code'])) {
            $this->helper->log('Basket no quote id or code is set');

            return false;
        }
        $quoteId = $params['quote_id'];
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
        foreach ($quoteItems as $quoteItem) {
            //skip configurable products
            if ($quoteItem->getParentItemId() != null) {
                continue;
            }

            $_product = $quoteItem->getProduct();

            $inStock = ($_product->isInStock())
                ? 'In Stock'
                : 'Out of stock';
            $total = $this->priceHelper->currency(
                $quoteItem->getBaseRowTotalInclTax()
            );

            $productUrl = $_product->getProductUrl();
            $grandTotal = $this->priceHelper->currency(
                $this->getGrandTotal()
            );
            $itemsData[] = [
                'grandTotal' => $grandTotal,
                'total' => $total,
                'inStock' => $inStock,
                'productUrl' => $productUrl,
                'product' => $_product,
                'qty' => $quoteItem->getQty(),

            ];
        }

        return $itemsData;
    }

    /**
     * Grand total.
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        return $this->quote->getGrandTotal();
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
     * @return mixed
     */
    public function takeMeToCartTextForUrl()
    {
        return $this->quote->getStore()->getWebsite()->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LINK_TEXT
        );
    }

    /**
     * Get dynamic style configuration.
     *
     * @return array
     */
    public function getDynamicStyle()
    {
        $dynamicStyle = $this->helper->getDynamicStyles();

        return $dynamicStyle;
    }
}
