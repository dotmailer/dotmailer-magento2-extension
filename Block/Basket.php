<?php

namespace Dotdigitalgroup\Email\Block;

class Basket extends \Magento\Catalog\Block\Product\AbstractProduct
{

    public $helper;
    public $priceHelper;
    protected $_quote;
    protected $_quoteFactory;
    protected $_emulationFactory;

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
        $this->_quoteFactory     = $quoteFactory;
        $this->helper            = $helper;
        $this->priceHelper       = $priceHelper;
        $this->_emulationFactory = $emulationFactory;

        parent::__construct($context, $data);
    }

    /**
     * Basket itmes.
     *
     * @return mixed
     */
    public function getBasketItems()
    {
        $params = $this->getRequest()->getParams();

        if ( ! isset($params['quote_id']) || ! isset($params['code'])) {
            $this->helper->log('Basket no quote id or code is set');

            return false;
        }
        $quoteId    = $params['quote_id'];
        $quoteModel = $this->_quoteFactory->create()
            ->load($quoteId);

        //check for any quote for this email, don't want to render further
        if ( ! $quoteModel->getId()) {
            $this->helper->log('no quote found for ' . $quoteId);

            return false;
        }
        if ( ! $quoteModel->getIsActive()) {
            $this->helper->log('Cart is not active : ' . $quoteId);

            return false;
        }

        $this->_quote = $quoteModel;

        //Start environment emulation of the specified store
        $storeId      = $quoteModel->getStoreId();

        $appEmulation = $this->_emulationFactory->create();
        $appEmulation->startEnvironmentEmulation($storeId);

        $quoteItems = $quoteModel->getAllItems();

        $itemsData = array();

        foreach ($quoteItems as $quoteItem) {
            //skip configurable products
            if ($quoteItem->getParentItemId() != null) {
                continue;
            }

            $_product = $quoteItem->getProduct();

            $inStock = ($_product->isInStock())
                ? 'In Stock'
                : 'Out of stock';
            $total   = $this->priceHelper->currency(
                $quoteItem->getPrice()
            );

            $productUrl  = $_product->getProductUrl();
            $grandTotal  = $this->priceHelper->currency(
                $this->getGrandTotal()
            );
            $itemsData[] = array(
                'grandTotal' => $grandTotal,
                'total'      => $total,
                'inStock'    => $inStock,
                'productUrl' => $productUrl,
                'product'    => $_product,
                'qty'        => $quoteItem->getQty()

            );
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
        return $this->_quote->getGrandTotal();

    }

    /**
     * url for "take me to basket" link
     *
     * @return string
     */
    public function getUrlForLink()
    {
        return $this->_quote->getStore()->getUrl(
            'connector/email/getbasket',
            array('quote_id' => $this->_quote->getId())
        );
    }

    /**
     * can show go to basket url
     *
     * @return bool
     */
    public function canShowUrl()
    {
        return (boolean)$this->_quote->getStore()->getWebsite()->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LINK_ENABLED
        );
    }

    public function takeMeToCartTextForUrl()
    {
        return $this->_quote->getStore()->getWebsite()->getConfig(
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