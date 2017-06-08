<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

/**
 * Product to be marked as modified and reimported.
 */
class ReimportProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;
    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    private $catalogFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    private $catalogCollection;

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
                    $item->setModified(1);
                    $item->getResource()->save($item);
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
        $item = $this->catalogCollection->create()
            ->loadProductById($productId);

        if ($item) {
            return $item;
        } else {
            $catalog = $this->catalogFactory->create();
            $catalog->setProductId($productId);
            $catalog->getResource()->save($catalog);
        }

        return false;
    }
}
