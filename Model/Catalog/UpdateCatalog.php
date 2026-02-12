<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

use Dotdigitalgroup\Email\Model\Catalog;

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
     * @var \Dotdigitalgroup\Email\Model\Product\ParentFinder
     */
    private $parentFinder;

    /**
     * UpdateCatalog constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
     * @param \Dotdigitalgroup\Email\Model\Product\ParentFinder $parentFinder
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Model\Product\ParentFinder $parentFinder
    ) {
        $this->catalogResource = $catalogResource;
        $this->catalogFactory = $catalogFactory;
        $this->parentFinder = $parentFinder;
    }

    /**
     * Update catalog.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($product)
    {
        $emailCatalogModel = $this->catalogFactory->create();
        $emailCatalog = $emailCatalogModel->loadProductById($product->getId());

        if ($emailCatalog->getId()) {
            $this->updateEmailCatalog($product);
        } else {
            $this->createEmailCatalog($emailCatalogModel, $product->getId());
        }
    }

    /**
     * Update email catalog item.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     */
    private function updateEmailCatalog($product)
    {
        $idsToUpdate = $this->parentFinder->getParentIdsFromProductIds([$product->getId()]);
        array_push($idsToUpdate, $product->getId());

        $this->catalogResource->setUnprocessedByIds($idsToUpdate);
    }

    /**
     * Create new email catalog item.
     *
     * @param Catalog $emailCatalogModel
     * @param string $productId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function createEmailCatalog($emailCatalogModel, $productId)
    {
        $emailCatalogModel->setProductId($productId);
        $this->catalogResource->save($emailCatalogModel);
    }
}
