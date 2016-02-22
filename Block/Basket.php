<?php

namespace Dotdigitalgroup\Email\Block;

class Basket extends \Magento\Catalog\Block\Product\AbstractProduct
{
    protected $_quote;
    public $helper;
	public $priceHelper;
	protected $_quoteFactory;
	protected $_emulationFactory;


	public function __construct(
		\Magento\Store\Model\App\EmulationFactory $emulationFactory,
		\Magento\Quote\Model\QuoteFactory $quoteFactory,
		\Magento\Catalog\Block\Product\Context $context,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		array $data = []
	)
	{
		$this->_quoteFactory = $quoteFactory;
		$this->helper = $helper;
		$this->priceHelper = $priceHelper;
		$this->_emulationFactory = $emulationFactory;

		parent::__construct( $context, $data );
	}

    /**
	 * Basket itmes.
	 *
	 * @return mixed
	 */
    public function getBasketItems()
    {
        $params = $this->getRequest()->getParams();

	    if (! isset($params['quote_id']) || !isset($params['code'])){
            $this->helper->log('Basket no quote id or code is set');
            return false;
        }

        $quoteId = $params['quote_id'];

	    $quoteModel = $this->_quoteFactory->create()
		    ->load($quoteId);

	    //check for any quote for this email, don't want to render further
	    if (! $quoteModel->getId()) {
		    $this->helper->log('no quote found for '. $quoteId);
            return false;
	    }
	    if (! $quoteModel->getIsActive()) {
		    $this->helper->log('Cart is not active : '. $quoteId);
		    return false;
	    }

        $this->_quote = $quoteModel;

	    //Start environment emulation of the specified store
	    $storeId = $quoteModel->getStoreId();
	    $appEmulation = $this->_emulationFactory->create();
	    $appEmulation->startEnvironmentEmulation($storeId);

        return $quoteModel->getAllItems();
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
		return (boolean) $this->_quote->getStore()->getWebsite()->getConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LINK_ENABLED
		);
	}

	public function takeMeToCartTextForUrl()
	{
		return $this->_quote->getStore()->getWebsite()->getConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_LINK_TEXT
		);
	}
}