<?php

namespace Dotdigitalgroup\Email\Block;

use DOMDocument;
use XSLTProcessor;

const FEEFO_URL = 'http://www.feefo.com/feefo/xmlfeed.jsp?';

class Feefo extends \Magento\Framework\View\Element\Template
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
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    public $quoteFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;
    /**
     * @var DOMDocument
     */
    public $domDocument;
    /**
     * @var XSLTProcessor
     */
    public $processor;

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
        XSLTProcessor $processor,
        DOMDocument $document,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper         = $helper;
        $this->domDocument = $document;
        $this->processor = $processor;
        $this->priceHelper    = $priceHelper;
        $this->orderFactory   = $orderFactory;
        $this->productFactory = $productFactory;
        $this->quoteFactory   = $quoteFactory;
    }

    /**
     * Get customer's service score logo and output it.
     *
     * @return array
     */
    public function getServiceScoreLogo()
    {
        $url = 'http://www.feefo.com/feefo/feefologo.jsp?logon=';
        $logon = $this->helper->getFeefoLogon();
        $template = '';
        if ($this->helper->getFeefoLogoTemplate()) {
            $template = '&template=' . $this->helper->getFeefoLogoTemplate();
        }
        $fullUrl = $url . $logon . $template;
        $vendorUrl = 'http://www.feefo.com/feefo/viewvendor.jsp?logon='
            . $logon;

        return ['vendorUrl' => $vendorUrl, 'fullUrl' => $fullUrl];
    }

    /**
     * Get quote products to show feefo reviews.
     *
     * @return array
     */
    public function getQuoteProducts()
    {
        $products = [];
        $quoteId = $this->_request->getParam('quote_id');
        /** @var \Magento\Quote\Model\Quote $quoteModel */
        $quoteModel = $this->quoteFactory->create()
            ->load($quoteId);

        if (!$quoteModel->getId()) {
            return [];
        }

        $quoteItems = $quoteModel->getAllItems();

        if (count($quoteItems) == 0) {
            return [];
        }

        foreach ($quoteItems as $item) {
            $productModel = $item->getProduct();
            
            if ($productModel->getId()) {
                $products[$productModel->getSku()] = $productModel->getName();
            }
        }

        return $products;
    }

    /**
     * Get product reviews from feefo.
     *
     * @return array
     */
    public function getProductsReview()
    {
        $check = true;
        $reviews = [];
        $feefoDir = BP . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
            . 'code' . DIRECTORY_SEPARATOR . 'Dotdigitalgroup'
            . DIRECTORY_SEPARATOR .
            'Email' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR
            . 'frontend' . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . 'feefo';
        $logon = $this->helper->getFeefoLogon();
        $limit = $this->helper->getFeefoReviewsPerProduct();
        $products = $this->getQuoteProducts();

        foreach ($products as $sku => $name) {
            $url = 'http://www.feefo.com/feefo/xmlfeed.jsp?logon=' . $logon
                . '&limit=' . $limit . '&vendorref=' . $sku
                . '&mode=productonly';
            $doc = $this->domDocument;
            $xsl = $this->processor;
            //@codingStandardsIgnoreStart
            if ($check) {
                $doc->load($feefoDir . DIRECTORY_SEPARATOR . 'feedback.xsl');
            } else {
                $doc->load(
                    $feefoDir . DIRECTORY_SEPARATOR . 'feedback-no-th.xsl'
                );
            }
            $xsl->importStyleSheet($doc);
            $doc->loadXML(file_get_contents($url));
            //@codingStandardsIgnoreEnd
            $productReview = $xsl->transformToXML($doc);
            if (strpos($productReview, '<td') !== false) {
                $reviews[$name] = $xsl->transformToXML($doc);
            }
            $check = false;
        }

        return $reviews;
    }
}
