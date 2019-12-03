<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

class UpdateCatalog
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    private $catalogFactory;

    /**
     * UpdateCatalog constructor.
     * This class is being using by observers when we add new products manually or via CSV
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
    ) {
        $this->catalogResource = $catalogResource;
        $this->catalogFactory = $catalogFactory;
    }

    public function execute($productId)
    {
        $emailCatalogModel = $this->catalogFactory->create();
        $emailCatalog = $emailCatalogModel->loadProductById($productId);

        if ($emailCatalog->getId()) {
            $this->updateEmailCatalog($emailCatalog);
        } else {
            $this->createEmailCatalog($emailCatalogModel, $productId);
        }
    }

    /**
     * update email catalog item when imported
     * @param $emailCatalog
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateEmailCatalog($emailCatalog)
    {
        if ($emailCatalog->getProcessed()) {
            $emailCatalog->setProcessed(0);
            $this->catalogResource->save($emailCatalog);
        }
    }

    /**
     * create new email catalog item
     * @param $emailCatalogModel
     * @param $productId
     */
    private function createEmailCatalog($emailCatalogModel, $productId)
    {
        $emailCatalogModel->setProductId($productId);
        $this->catalogResource->save($emailCatalogModel);
    }
}
