<?php

namespace Dotdigitalgroup\Email\Block;

const FEEFO_URL = 'http://www.feefo.com/feefo/xmlfeed.jsp?';

class Feefo extends \Magento\Framework\View\Element\Template
{

    public $helper;
    public $priceHelper;
    public $scopeManager;
    protected $_orderFactory;
    protected $_quoteFactory;
    protected $_productFactory;


    /**
     * Feefo constructor.
     *
     * @param \Magento\Catalog\Model\ProductFactory            $productFactory
     * @param \Magento\Quote\Model\QuoteFactory                $quoteFactory
     * @param \Magento\Sales\Model\OrderFactory                $orderFactory
     * @param \Dotdigitalgroup\Email\Helper\Data               $helper
     * @param \Magento\Framework\Pricing\Helper\Data           $priceHelper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper          = $helper;
        $this->scopeManager    = $scopeConfig;
        $this->priceHelper     = $priceHelper;
        $this->_orderFactory   = $orderFactory;
        $this->storeManager    = $this->_storeManager;
        $this->_productFactory = $productFactory;
        $this->_quoteFactory   = $quoteFactory;
        $this->_orderFactory   = $orderFactory;

    }

    /**
     * get customer's service score logo and output it
     *
     * @return string
     */
    public function getServiceScoreLogo()
    {
        $url      = 'http://www.feefo.com/feefo/feefologo.jsp?logon=';
        $logon    = $this->helper->getFeefoLogon();
        $template = '';
        if ($this->helper->getFeefoLogoTemplate()) {
            $template = '&template=' . $this->helper->getFeefoLogoTemplate();
        }
        $fullUrl   = $url . $logon . $template;
        $vendorUrl = 'http://www.feefo.com/feefo/viewvendor.jsp?logon='
            . $logon;

        return
            "<a href=\"$vendorUrl\" target='_blank'>
                <img alt='Feefo logo' border='0' src=\"$fullUrl\" title='See what our customers say about us'>
             </a>";
    }

    /**
     * get quote products to show feefo reviews
     *
     * @return array
     */
    public function getQuoteProducts()
    {
        $products   = array();
        $quoteId    = $this->_request->getParam('quote_id');
        $quoteModel = $this->_quoteFactory->create()
            ->load($quoteId);

        if ( ! $quoteModel->getId()) {
            return [];
        }

        $quoteItems = $quoteModel->getAllItems();

        if (count($quoteItems) == 0) {
            return array();
        }

        foreach ($quoteItems as $item) {
            $productId    = $item->getProductId();
            $productModel = $this->_productFactory->create()
                ->load($productId);
            if ($productModel->getId()) {
                $products[$productModel->getSku()] = $productModel->getName();
            }
        }

        return $products;
    }

    /**
     * get product reviews from feefo
     *
     * @return array
     */
    public function getProductsReview()
    {
        $check     = true;
        $reviews   = array();
        $feefo_dir = BP . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
            . 'code' . DIRECTORY_SEPARATOR . 'Dotdigitalgroup'
            . DIRECTORY_SEPARATOR .
            'Email' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR
            . 'frontend' . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . 'feefo';
        $logon     = $this->helper->getFeefoLogon();
        $limit     = $this->helper->getFeefoReviewsPerProduct();
        $products  = $this->getQuoteProducts();

        foreach ($products as $sku => $name) {
            $url = "http://www.feefo.com/feefo/xmlfeed.jsp?logon=" . $logon
                . "&limit=" . $limit . "&vendorref=" . $sku
                . "&mode=productonly";
            $doc = new \DOMDocument();
            $xsl = new \XSLTProcessor();
            if ($check) {
                $doc->load($feefo_dir . DIRECTORY_SEPARATOR . "feedback.xsl");
            } else {
                $doc->load(
                    $feefo_dir . DIRECTORY_SEPARATOR . "feedback-no-th.xsl"
                );
            }
            $xsl->importStyleSheet($doc);
            $doc->loadXML(file_get_contents($url));
            $productReview = $xsl->transformToXML($doc);
            if (strpos($productReview, '<td') !== false) {
                $reviews[$name] = $xsl->transformToXML($doc);
            }
            $check = false;
        }

        return $reviews;
    }
}