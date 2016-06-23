<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class ReimportProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    protected $_catalogFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    protected $_catalogCollection;

    /**
     * ReimportProduct constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory                     $catalogFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                              $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_helper = $data;
        $this->_catalogFactory = $catalogFactory;
        $this->_catalogCollection = $catalogCollectionFactory;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $object = $observer->getEvent()->getDataObject();
            $productId = $object->getId();

            if ($item = $this->_loadProduct($productId)) {
                if ($item->getImported()) {
                    $item->setModified(1)
                        ->save();
                }
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
        }
    }

    /**
     * Load product. return item otherwise create item.
     *
     * @param int $productId
     *
     * @return bool
     */
    protected function _loadProduct($productId)
    {
        $collection = $this->_catalogCollection->create()
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->_catalogFactory->create()
                ->setProductId($productId)
                ->save();
        }

        return false;
    }
}
