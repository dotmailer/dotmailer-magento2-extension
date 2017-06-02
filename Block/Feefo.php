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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory
     */
    private $reviewFactory;

    /**
     * Feefo constructor.
     *
     * @param XSLTProcessor $processor
     * @param DOMDocument $document
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory
     * @param array $data
     */
    public function __construct(
        XSLTProcessor $processor,
        DOMDocument $document,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory,
        array $data = []
    ) {
        $this->helper         = $helper;
        $this->domDocument = $document;
        $this->processor = $processor;
        $this->priceHelper    = $priceHelper;
        $this->productFactory = $productFactory;
        $this->reviewFactory = $reviewFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get customer's service score logo and output it.
     *
     * @return array
     */
    public function getServiceScoreLogo()
    {
        $params = $this->getRequest()->getParams();
        if (! isset($params['code']) || ! $this->helper->isCodeValid($params['code'])) {
            $this->helper->log('Feefo no valid code is set');
            return [];
        }

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
        $params = $this->_request->getParams();

        if (! isset($params['code']) || ! $this->helper->isCodeValid($params['code'])) {
            $this->helper->log('Feefo no quote id or code is set');

            return $products;
        }

        $quoteId = (int) $params['quote_id'];

        return $this->reviewFactory->create()->getQuoteProducts($quoteId);
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
            if ($check) {
                $doc->load($feefoDir . DIRECTORY_SEPARATOR . 'feedback.xsl');
            } else {
                $doc->load(
                    $feefoDir . DIRECTORY_SEPARATOR . 'feedback-no-th.xsl'
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
