<?php

namespace Dotdigitalgroup\Email\Block;

use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Feefo block
 *
 * @api
 */
class Feefo extends \Magento\Framework\View\Element\Template
{
    const FEEFO_LOGO_ROOT = 'https://api.feefo.com/api/logo?';
    const FEEFO_PRODUCT_REVIEWS_ROOT = 'https://api.feefo.com/api/10/reviews/product?';

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

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
     * @var DriverInterface
     */
    private $driver;

    /**
     * Feefo constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review $review
     * @param Quote $quoteResource
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param DriverInterface $driver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Review $review,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        DriverInterface $driver,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->priceHelper = $priceHelper;
        $this->review = $review;
        $this->assetRepository = $context->getAssetRepository();
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->driver = $driver;
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

        $url = self::FEEFO_LOGO_ROOT . 'merchantidentifier=';
        $logon = $this->helper->getFeefoLogon();
        $template = '';
        if ($this->helper->getFeefoLogoTemplate()) {
            $template = '&template=' . $this->helper->getFeefoLogoTemplate();
        }
        $fullUrl = $url . $logon . $template;
        $vendorUrl = 'https://www.feefo.com/reviews/'
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
     * Get product reviews from Feefo.
     *
     * @return array
     */
    public function getProductsReview()
    {
        $reviews = [];
        $logon = $this->helper->getFeefoLogon();
        $limit = $this->helper->getFeefoReviewsPerProduct();
        $products = $this->getQuoteProducts();

        foreach ($products as $sku => $name) {
            $url = self::FEEFO_PRODUCT_REVIEWS_ROOT . 'merchant_identifier=' . $logon
                . '&page_size=' . $limit . '&product_sku=' . $sku . '&page=1';

            $json = $this->driver->fileGetContents($url);
            $productReviewObject = json_decode($json);

            if (count($productReviewObject->reviews) > 0) {
                $reviewParentObject = reset($productReviewObject->reviews);
                $reviewItemObject = reset($reviewParentObject->products);
                $dateObject = new \DateTime($reviewParentObject->last_updated_date);

                $reviews[$name] = [
                    'url' => $reviewParentObject->url,
                    'rating' => $reviewItemObject->rating->rating,
                    'review' => $reviewItemObject->review,
                    'products_purchased' => $reviewParentObject->products_purchased,
                    'last_updated_date' => $dateObject->format('Y-m-d H:i:s'),
                    'star_image_path' => $this->getStarImagePath(round($reviewItemObject->rating->rating))
                ];
            }
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

    /**
     *
     * @param int $rating
     */
    private function getStarImagePath($rating)
    {
        $fileId = 'Dotdigitalgroup_Email::feefo/' . $rating . 'star.png';
        return $this->getViewFileUrl($fileId);
    }
}
