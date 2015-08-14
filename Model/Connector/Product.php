<?php

namespace Dotdigitalgroup\Email\Model\Connector;

class Product
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $sku = '';

    /**
     * @var string
     */
    public $status = '';

    /**
     * @var string
     */
    public $visibility = '';

    /**
     * @var float
     */
    public $price = 0;

    /**
     * @var float
     */
    public $special_price = 0;

    /**
     * @var array
     */
    public $categories = array();

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    public $image_path = '';

    /**
     * @var string
     */
    public $short_description = '';

    /**
     * @var float
     */
    public $stock = 0;

    /**
     * @var array
     */
    public $websites = array();

	protected $_helper;
	protected $_resource;
	protected $_objectManager;
	protected $_scopeConfig;

    public function __construct($product,
        \Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\Data $helper,
	    \Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
	    $this->_objectManager = $objectManager;
	    $this->_helper = $helper;
	    $this->_resource = $resource;

        $this->id                   = $product->getId();
        $this->sku                  = $product->getSku();
        $this->name                 = $product->getName();

	    $statuses = $this->_objectManager->create('Magento\Catalog\Model\Product\Attribute\Source\Status')->getOptionArray();
	    $this->status               = $statuses[$product->getStatus()];

	    $options = $this->_objectManager->create('Magento\Catalog\Model\Product\Visibility')->getOptionsArray();
        $this->visibility           = $options[$product->getVisibility()];
        $this->price                = (float) number_format($product->getPrice(), 2, '.', '' );
        $this->special_price        = (float) number_format($product->getSpecialPrice(), 2, '.', '' );
        $this->url                  = $product->getProductUrl();

	    $this->image_path           = $this->_objectManager->create('Magento\Catalog\Model\Product\Media\Config')
		    ->getMediaUrl($product->getSmallImage());


        $stock = $this->_objectManager->create('Magento\CatalogInventory\Model\Stock\Item')
	        ->loadByProduct($product);
	    $this->stock                = (float) number_format($stock->getQty(), 2, '.', '' );

        $short_description = $product->getShortDescription();
        //limit short description
        if(strlen($short_description) > 250)
            $short_description = substr($short_description, 0 , 250);

        $this->short_description    = $short_description;

        //category data
        $count = 0;
        $categoryCollection = $product->getCategoryCollection()->addNameToResult();
        foreach ($categoryCollection as $cat) {
            $this->categories[$count]['Id'] = $cat->getId();
            $this->categories[$count]['Name'] = $cat->getName();
            $count++;
        }

        //website data
        $count = 0;
        $websiteIds = $product->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            $website = Mage::app()->getWebsite($websiteId);
            $this->websites[$count]['Id'] = $website->getId();
            $this->websites[$count]['Name'] = $website->getName();
            $count++;
        }

        //bundle product options
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE){
            $optionCollection = $product->getTypeInstance()->getOptionsCollection();
            $selectionCollection = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
            $options = $optionCollection->appendSelections($selectionCollection);
            foreach($options as $option)
            {
                $count = 0;
                $title = str_replace(' ', '', $option->getDefaultTitle());
                $selections = $option->getSelections();
                $sOptions = array();
                foreach($selections as $selection)
                {
                    $sOptions[$count]['name'] = $selection->getName();
                    $sOptions[$count]['sku'] = $selection->getSku();
                    $sOptions[$count]['id'] = $selection->getProductId();
                    $sOptions[$count]['price'] = (float) number_format($selection->getPrice(), 2, '.', '' );
                    $count++;
                }
                $this->$title = $sOptions;
            }
        }

        //configurable product options
	    //@todo configurable product option is missing for mage2, cann't find TYPE_CONFIGURABLE
//        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_CONFIGURABLE){
//            $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
//            foreach ($productAttributeOptions as $productAttribute) {
//                $count = 0;
//                $label = strtolower(str_replace(' ', '', $productAttribute['label']));
//                $options = array();
//                foreach ($productAttribute['values'] as $attribute) {
//                    $options[$count]['option'] = $attribute['default_label'];
//                    $options[$count]['price'] = (float) number_format($attribute['pricing_value'], 2, '.', '' );
//                    $count++;
//                }
//                $this->$label = $options;
//            }
//        }
        return true;
    }

    /**
     * exposes the class as an array of objects.
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }

}