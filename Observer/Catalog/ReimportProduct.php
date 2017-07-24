<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

/**
 * Product to be marked as modified and reimported.
 */
class ReimportProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    private $catalogFactory;

    /**
     * ReimportProduct constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
    ) {
        $this->helper            = $data;
        $this->catalogResource = $catalogResource;
        $this->catalogFactory    = $catalogFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productModel = $observer->getEvent()->getDataObject();
        $productId = $productModel->getId();

        $emailCatalogModel = $this->catalogFactory->create();
        $emailCatalog = $emailCatalogModel->loadProductById($productId);

        if ($emailCatalog->getId()) {
            //update email catalog item when imported
            if ($emailCatalog->getImported()) {
                $emailCatalog->setModified(1);
                $this->catalogResource->save($emailCatalog);
            }
        } else {
            //create new email catalog item
            $emailCatalogModel->setProductId($productId);
            $this->catalogResource->save($emailCatalogModel);
        }

        return $this;
    }
}
