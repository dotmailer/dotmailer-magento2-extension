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
     * @var \Dotdigitalgroup\Email\Model\Product\ParentFinder
     */
    private $parentFinder;

    /**
     * UpdateCatalog constructor.
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
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateEmailCatalog($product)
    {
        $productsToUpdate = $this->parentFinder->getConfigurableParentsFromBunchOfProducts([$product]);
        array_push($productsToUpdate, $product->getData());

        $idsToUpdate = array_map(function ($products) {
            return $products['entity_id'];
        }, $productsToUpdate);

        $this->catalogResource->setUnprocessedByIds($idsToUpdate);
    }

    /**
     * create new email catalog item
     * @param $emailCatalogModel
     * @param $productId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function createEmailCatalog($emailCatalogModel, $productId)
    {
        $emailCatalogModel->setProductId($productId);
        $this->catalogResource->save($emailCatalogModel);
    }
}
