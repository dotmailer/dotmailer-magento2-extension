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
	protected $_scopeConfig;
	protected $_storeManager;
	protected $_statusFactory;
	protected $_visibilityFactory;
	protected $_mediaConfigFactory;
	protected $_itemFactory;

	public function __construct(
	    \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Dotdigitalgroup\Email\Helper\Data $helper,
	    \Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\CatalogInventory\Model\Stock\ItemFactory $itemFactory,
		\Magento\Catalog\Model\Product\Media\ConfigFactory $mediaConfigFactory,
		\Magento\Catalog\Model\Product\Attribute\Source\StatusFactory $statusFactory,
		\Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory
    )
    {
	    $this->_itemFactory = $itemFactory;
	    $this->_mediaConfigFactory = $mediaConfigFactory;
	    $this->_visibilityFactory = $visibilityFactory;
	    $this->_statusFactory = $statusFactory;
	    $this->_helper = $helper;
	    $this->_storeManager = $storeManagerInterface;
    }

	/**
	 * Set the product data.
	 * @param $product
	 *
	 * @return $this
	 */
	public function setProduct($product)
	{
		$this->id                   = $product->getId();
		$this->sku                  = $product->getSku();
		$this->name                 = $product->getName();

		$statuses = $this->_statusFactory->create()
			->getAllOptions();
		$this->status               = (string)$statuses[$product->getStatus()]['label'];

		$options = $this->_visibilityFactory->create()
			->getOptionArray();
		$this->visibility           = (string)$options[$product->getVisibility()];
		$this->price                = (float) number_format($product->getPrice(), 2, '.', '' );
		$this->special_price        = (float) number_format($product->getSpecialPrice(), 2, '.', '' );
		$this->url                  = $product->getProductUrl();

		$this->image_path           = $this->_mediaConfigFactory->create()
			->getMediaUrl($product->getSmallImage());

		$stock = $this->_itemFactory->create()->setProduct($product);
		$this->stock = (float) number_format($stock->getQty(), 2, '.', '' );

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
			$website = $this->_storeManager->getWebsite($websiteId);
			$this->websites[$count]['Id'] = $website->getId();
			$this->websites[$count]['Name'] = $website->getName();
			$count++;
		}

		//bundle product options
		if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE){
			$optionCollection       = $product->getTypeInstance()->getOptionsCollection();
			$selectionCollection    = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
			$options = $optionCollection->appendSelections($selectionCollection);
			foreach($options as $option) {

				$count = 0;
				$title = str_replace(' ', '', $option->getDefaultTitle());
				$selections = $option->getSelections();
				$sOptions = array();
				foreach($selections as $selection) {

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

		unset($this->_itemFactory, $this->_mediaConfigFactory, $this->_visibilityFactory, $this->_statusFactory, $this->_helper, $this->_storeManager);

		return $this;
	}

    /**
     * exposes the class as an array of objects.
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }


	/**
	 * @return string[]
	 */
	public function __sleep()
	{
		$properties = array_keys(get_object_vars($this));
		$properties = array_diff($properties, ['_storeManager', '_scopeConfig', '_objectManager', '_storeManager', '_datetime', '_helper', '_customerFactory', '_productFactory', '_attributeCollection', '_setFactory']);

		return $properties;
	}
	/**
	 * Init not serializable fields
	 *
	 * @return void
	 */
	public function __wakeup()
	{

	}
}