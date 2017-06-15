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
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepository;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * Feefo constructor.
     *
     * @param XSLTProcessor $processor
     * @param DOMDocument $document
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param array $data
     */
    public function __construct(
        XSLTProcessor $processor,
        DOMDocument $document,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        array $data = []
    ) {
        $this->helper         = $helper;
        $this->domDocument = $document;
        $this->processor = $processor;
        $this->priceHelper    = $priceHelper;
        $this->reviewFactory = $reviewFactory;
        $this->assetRepository = $assetRepository;
        $this->quoteFactory = $quoteFactory;
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
    private function getQuoteProducts()
    {
        $products = [];
        $params = $this->_request->getParams();

        if (! isset($params['quote_id']) || ! isset($params['code']) || ! $this->helper->isCodeValid($params['code'])) {
            $this->helper->log('Feefo no quote id or code is set');

            return $products;
        }

        $quoteId = (int) $params['quote_id'];

        $quoteModel = $this->quoteFactory->create();
        $quoteModel->getResource()
            ->load($quoteModel, $quoteId);

        if (! $quoteModel->getId()) {
            return $products;
        }

        $productCollection = $this->reviewFactory->create()
            ->getProductCollection($quoteModel);

        foreach ($productCollection as $product) {
                $products[$product->getSku()] = $product->getName();
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

    /**
     * @return string
     */
    public function getCssUrl()
    {
        return $this->assetRepository
            ->createAsset('Dotdigitalgroup_Email::css/feefo.css')
            ->getUrl();
    }
}
