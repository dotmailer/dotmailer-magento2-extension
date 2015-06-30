<?php

const FEEFO_URL = 'http://www.feefo.com/feefo/xmlfeed.jsp?';

class Dotdigitalgroup_Email_Block_Feefo extends Mage_Core_Block_Template
{
    /**
     * Prepare layout, set the template.
     * @return Mage_Core_Block_Abstract|void
     */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

    /**
     * get customer's service score logo and output it
     *
     * @return string
     */
    public function getServiceScoreLogo()
    {
        $helper = Mage::helper('ddg');
        $url = 'http://www.feefo.com/feefo/feefologo.jsp?logon=';
        $logon = $helper->getFeefoLogon();
        $template = '';
        if($helper->getFeefoLogoTemplate())
            $template = '&template=' . $helper->getFeefoLogoTemplate();
        $fullUrl =  $url . $logon . $template;
        $vendorUrl = 'http://www.feefo.com/feefo/viewvendor.jsp?logon=' . $logon;

        return
            "<a href=$vendorUrl target='_blank'>
                <img alt='Feefo logo' border='0' src=$fullUrl title='See what our customers say about us'>
             </a>";
    }

    /**
     * get quote products to show feefo reviews
     *
     * @return array
     */
    public function getQuoteProducts()
    {
        $products = array();
        $quoteId = Mage::app()->getRequest()->getParam('quote_id');
        $quoteModel = Mage::getModel('sales/quote')->load($quoteId);
        if(!$quoteModel->getId())
            die;

        $quoteItems = $quoteModel->getAllItems();

        if(count($quoteItems) == 0)
            return array();

        foreach($quoteItems as $item)
        {
            $productId = $item->getProductId();
            $productModel = Mage::getModel('catalog/product')->load($productId);
            if ($productModel->getId())
                $products[$productModel->getSku()] = $productModel->getName();
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
        $check = true;
        $reviews = array();
        $feefo_dir = Mage::getModel('core/config_options')->getLibDir().DS.'connector'.DS.'feefo';
        $helper = Mage::helper('ddg');
        $logon = $helper->getFeefoLogon();
        $limit = $helper->getFeefoReviewsPerProduct();
        $products = $this->getQuoteProducts();

        foreach($products as $sku => $name)
        {
            $url = "http://www.feefo.com/feefo/xmlfeed.jsp?logon=" . $logon . "&limit=".$limit . "&vendorref=" . $sku . "&mode=productonly" ;
            $doc = new DOMDocument();
            $xsl = new XSLTProcessor();
            if($check)
                $doc->load($feefo_dir. DS ."feedback.xsl");
            else
                $doc->load($feefo_dir. DS ."feedback-no-th.xsl");
            $xsl->importStyleSheet($doc);
            $doc->load($url);
            $productReview = $xsl->transformToXML($doc);
            if(strpos($productReview, '<td'))
                $reviews[$name] = $xsl->transformToXML($doc);
            $check = false;
        }
        return $reviews;
    }
}