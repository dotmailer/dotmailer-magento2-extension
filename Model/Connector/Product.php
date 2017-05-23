<?php

namespace Dotdigitalgroup\Email\Model\Connector;

/**
 * Class Product
 * @package Dotdigitalgroup\Email\Model\Connector
 */
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
    public $specialPrice = 0;

    /**
     * @var array
     */
    public $categories = [];

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var string
     */
    public $imagePath = '';

    /**
     * @var string
     */
    public $shortDescription = '';

    /**
     * @var float
     */
    public $stock = 0;

    /**
     * @var array
     */
    public $websites = [];

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory
     */
    public $statusFactory;
    /**
     * @var \Magento\Catalog\Model\Product\VisibilityFactory
     */
    public $visibilityFactory;
    /**
     * @var \Magento\Catalog\Model\Product\Media\ConfigFactory
     */
    public $mediaConfigFactory;
    /**
     * @var \Magento\CatalogInventory\Model\Stock\ItemFactory
     */
    public $itemFactory;

    /**
     * Product constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface                    $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Helper\Data                            $helper
     * @param \Magento\CatalogInventory\Model\Stock\ItemFactory             $itemFactory
     * @param \Magento\Catalog\Model\Product\Media\ConfigFactory            $mediaConfigFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory $statusFactory
     * @param \Magento\Catalog\Model\Product\VisibilityFactory              $visibilityFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\CatalogInventory\Model\Stock\ItemFactory $itemFactory,
        \Magento\Catalog\Model\Product\Media\ConfigFactory $mediaConfigFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\StatusFactory $statusFactory,
        \Magento\Catalog\Model\Product\VisibilityFactory $visibilityFactory
    ) {
        $this->itemFactory        = $itemFactory;
        $this->mediaConfigFactory = $mediaConfigFactory;
        $this->visibilityFactory  = $visibilityFactory;
        $this->statusFactory      = $statusFactory;
        $this->helper             = $helper;
        $this->storeManager       = $storeManagerInterface;
    }

    /**
     * Set the product data.
     *
     * @param $product
     *
     * @return $this
     */
    public function setProduct($product)
    {
        $this->id = $product->getId();
        $this->sku = $product->getSku();
        $this->name = $product->getName();

        $status = $this->statusFactory->create()
            ->getOptionText($product->getStatus());

        $this->status = $status->getText();

        $options = $this->visibilityFactory->create()
            ->getOptionArray();
        $this->visibility = (string)$options[$product->getVisibility()];
        $this->price = (float)number_format(
            $product->getPrice(),
            2,
            '.',
            ''
        );
        $this->specialPrice = (float)number_format(
            $product->getSpecialPrice(),
            2,
            '.',
            ''
        );
        $this->url = $product->getProductUrl();

        $this->imagePath = $this->mediaConfigFactory->create()
            ->getMediaUrl($product->getSmallImage());

        $stock = $this->itemFactory->create()
            ->setProduct($product);

        $this->stock = (float)number_format($stock->getQty(), 2, '.', '');

        $shortDescription = $product->getShortDescription();
        //limit short description
        if (strlen($shortDescription) > 250) {
            $shortDescription = substr($shortDescription, 0, 250);
        }

        $this->shortDescription = $shortDescription;

        //category data
        $count = 0;
        $categoryCollection = $product->getCategoryCollection()
            ->addNameToResult();
        foreach ($categoryCollection as $cat) {
            $this->categories[$count]['Id'] = $cat->getId();
            $this->categories[$count]['Name'] = $cat->getName();
            ++$count;
        }

        //website data
        $count = 0;
        $websiteIds = $product->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            $website = $this->storeManager->getWebsite(
                $websiteId
            );
            $this->websites[$count]['Id'] = $website->getId();
            $this->websites[$count]['Name'] = $website->getName();
            ++$count;
        }

        //bundle product options
        if ($product->getTypeId()
            == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
        ) {
            $optionCollection = $product->getTypeInstance()
                ->getOptionsCollection($product);
            $selectionCollection = $product->getTypeInstance()
                ->getSelectionsCollection(
                    $product->getTypeInstance()->getOptionsIds($product),
                    $product
                );
            $options = $optionCollection->appendSelections(
                $selectionCollection
            );
            foreach ($options as $option) {
                $count = 0;
                $title = str_replace(' ', '', $option->getDefaultTitle());
                $selections = $option->getSelections();
                $sOptions = [];
                foreach ($selections as $selection) {
                    $sOptions[$count]['name'] = $selection->getName();
                    $sOptions[$count]['sku'] = $selection->getSku();
                    $sOptions[$count]['id'] = $selection->getProductId();
                    $sOptions[$count]['price'] = (float)number_format(
                        $selection->getPrice(),
                        2,
                        '.',
                        ''
                    );
                    ++$count;
                }
                $this->$title = $sOptions;
            }
        }

        //configurable product options
        if ($product->getTypeId() == 'configurable') {
            $productAttributeOptions = $product->getTypeInstance(true)
                ->getConfigurableAttributesAsArray($product);

            foreach ($productAttributeOptions as $productAttribute) {
                $count = 0;
                $label = strtolower(
                    str_replace(' ', '', $productAttribute['label'])
                );
                $options = [];
                foreach ($productAttribute['values'] as $attribute) {
                    $options[$count]['option'] = $attribute['default_label'];
                    if (isset($attribute['pricing_value'])) {
                        $options[$count]['price'] = (float)number_format(
                            $attribute['pricing_value'],
                            2,
                            '.',
                            ''
                        );
                    }
                    ++$count;
                }
                $this->$label = $options;
            }
        }

        unset(
            $this->itemFactory,
            $this->mediaConfigFactory,
            $this->visibilityFactory,
            $this->statusFactory,
            $this->helper,
            $this->storeManager
        );

        return $this;
    }

    /**
     * Exposes the class as an array of objects.
     *
     * @return array
     */
    public function expose()
    {
        return array_diff_key(
            get_object_vars($this),
            array_flip([
                'storeManager',
                'helper',
                'itemFactory',
                'mediaConfigFactory',
                'visibilityFactory',
                'statusFactory',
                'storeManager'
            ])
        );
    }

    /**
     * @return string
     */
    public function __sleep()
    {
        $properties = array_keys(get_object_vars($this));
        $properties = array_diff(
            $properties,
            [
                'storeManager',
                'helper',
                'itemFactory',
                'mediaConfigFactory',
                'visibilityFactory',
                'statusFactory',
                'storeManager'
            ]
        );

        return $properties;
    }

    /**
     * Init not serializable fields.
     */
    public function __wakeup()
    {
    }
}
