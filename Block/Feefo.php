<?php

namespace Dotdigitalgroup\Email\Block;

use DOMDocument;
use Magento\Quote\Model\ResourceModel\Quote;
use XSLTProcessor;

const FEEFO_URL = 'http://www.feefo.com/feefo/xmlfeed.jsp?';

/**
 * Feefo block
 *
 * @api
 */
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
     * @var Quote
     */
    private $quoteResource;
    
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review
     */
    private $review;

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
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param XSLTProcessor $processor
     * @param DOMDocument $document
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review $review
     * @param Quote $quoteResource
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        XSLTProcessor $processor,
        DOMDocument $document,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Review $review,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        array $data = []
    ) {
        $this->helper         = $helper;
        $this->domDocument = $document;
        $this->processor = $processor;
        $this->priceHelper    = $priceHelper;
        $this->review = $review;
        $this->assetRepository = $context->getAssetRepository();
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
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
        $this->quoteResource->load($quoteModel, $quoteId);

        if (! $quoteModel->getId()) {
            return $products;
        }

        $productCollection = $this->review->getProductCollection($quoteModel);

        foreach ($productCollection as $product) {
                $products[$product->getSku()] = $product->getName();
        }
        return $products;
    }

    /**
     * Get product reviews from feefo.
     *
     * @param bool $check
     *
     * @return array
     */
    public function getProductsReview($check = true)
    {
        $reviews = [];
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
                $pathToTemplate = $this->getFeefoTemplate('feedback.xsl');
                $doc->load($pathToTemplate);
            } else {
                $pathToTemplate = $this->getFeefoTemplate('feedback-no-th.xsl');
                $doc->load($pathToTemplate);
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
     * @param string $template
     *
     * @return string
     */
    private function getFeefoTemplate($template)
    {
        return $this->assetRepository
            ->createAsset('Dotdigitalgroup_Email::feefo/' . $template)
            ->getUrl();
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
