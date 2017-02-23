<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class ReimportProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    public $catalogFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    public $catalogCollection;

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
        $this->helper            = $data;
        $this->catalogFactory    = $catalogFactory;
        $this->catalogCollection = $catalogCollectionFactory;
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

            if ($item = $this->loadProduct($productId)) {
                if ($item->getImported()) {
                    $item->setModified(1)
                        ->save();
                }
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Load product. return item otherwise create item.
     *
     * @param int $productId
     *
     * @return bool
     */
    protected function loadProduct($productId)
    {
        $collection = $this->catalogCollection->create()
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->catalogFactory->create()
                ->setProductId($productId)
                ->save();
        }

        return false;
    }
}
