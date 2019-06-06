<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\FilterBuilder;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class UpdateCatalogBulkTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepositoryMock;

    /**
     * @var Catalog
     */
    private $resourceCatalogMock;

    /**
     * @var CollectionFactory
     */
    private $collectionFactoryMock;

    /**
     * @var DateTime
     */
    private $dateTimeMock;

    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteriaMock;

    /**
     * @var FilterGroup
     */
    private $filterGroupMock;

    /**
     * @var FilterBuilder
     */
    private $filterBuilderMock;

    /**
     * @var UpdateCatalogBulk
     */
    private $bulkUpdate;

    /**
     * @var ProductSearchResultsInterface
     */
    private $productSearchResultsMock;

    /**
     * @var Product
     */
    private $productMock;

    /**
     * @var Collection
     */
    private $collectionMock;

    protected function setUp()
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->resourceCatalogMock = $this->createMock(Catalog::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->filterGroupMock = $this->createMock(FilterGroup::class);
        $this->filterBuilderMock = $this->createMock(FilterBuilder::class);
        $this->bulkUpdate = $this->createMock(UpdateCatalogBulk::class);
        $this->productSearchResultsMock = $this->createMock(ProductSearchResultsInterface::class);
        $this->productMock = $this->createMock(Product::class);
        $this->collectionMock = $this->createMock(Collection::class);

        $this->bulkUpdate = new UpdateCatalogBulk(
            $this->productRepositoryMock,
            $this->resourceCatalogMock,
            $this->collectionFactoryMock,
            $this->dateTimeMock,
            $this->searchCriteriaMock,
            $this->filterGroupMock,
            $this->filterBuilderMock
        );
    }


    /**
     * @dataProvider getProductCount
     * @param $numberOfProducts
     */
    public function testThatifWeHaveOnlyNewProducts($numberOfProducts)
    {

        $scope = 0;
        $this->prepareTests($scope,$numberOfProducts);

        $this->resourceCatalogMock->expects($this->atLeastOnce())
            ->method('bulkProductImport');

        $this->resourceCatalogMock->expects($this->never())
            ->method('setModified');

        $this->bulkUpdate->execute($this->generateBunches($numberOfProducts));
    }

    /**
     * $numberOfProducts;
     * @dataProvider getProductCount
     */
    public function testThatifWeHaveNotNewProducts($numberOfProducts)
    {
        $scope = 1;
        $this->prepareTests($scope,$numberOfProducts);

        $this->resourceCatalogMock->expects($this->never())
            ->method('bulkProductImport');

        $this->resourceCatalogMock->expects($this->atLeastOnce())
            ->method('setModified');

        $this->bulkUpdate->execute($this->generateBunches($numberOfProducts));
    }

    /**
     * $numberOfProducts;
     * @dataProvider getProductCount
     */
    public function testThatWeHaveBothNewAndAlreadyExistingEntries($numberOfProducts)
    {
        $scope = 2;

        $this->prepareTests($scope,$numberOfProducts);

        $this->resourceCatalogMock->expects($this->atLeastOnce())
            ->method('bulkProductImport');

        $this->resourceCatalogMock->expects($this->atLeastOnce())
            ->method('setModified');

        $this->bulkUpdate->execute($this->generateBunches($numberOfProducts));
    }

    /**
     * Scope Explanation
     * 0 => Only New Entries
     * 1 => No New Entries
     * 2 => Both new and already existing Entries
     * Prepares and Generates test scenarios based on scope
     * @param $scope
     */

    private function prepareTests($scope,$numberOfProducts)
    {
        $values = $this->getMinMaxValues($scope,$numberOfProducts);

        $this->filterGroupMock->expects($this->atLeastOnce())
            ->method('setFilters')
            ->willReturn($this->filterGroupMock);

        $this->filterBuilderMock->expects($this->atLeastOnce())
            ->method('setField')
            ->willReturn($this->filterBuilderMock);

        $this->filterBuilderMock->expects($this->atLeastOnce())
            ->method('setConditionType')
            ->willReturn($this->filterBuilderMock);

        $this->filterBuilderMock->expects($this->atLeastOnce())
            ->method('setValue')
            ->willReturn($this->filterBuilderMock);

        $this->filterBuilderMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->filterBuilderMock);

        $this->searchCriteriaMock->expects($this->atLeastOnce())
            ->method('setFilterGroups')
            ->with([$this->filterGroupMock])
            ->willReturn($this->searchCriteriaMock);

        $this->productRepositoryMock->expects($this->atLeastOnce())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->productSearchResultsMock);

        $this->productSearchResultsMock->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn($this->getProductItems($numberOfProducts));

        $this->setRandomIdsForProductMock($values,$numberOfProducts);

        $this->collectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('addFieldToSelect')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('getColumnValues')
            ->with('product_id')
            ->willReturn($this->getCatalogIds($values,$numberOfProducts));

        $this->dateTimeMock->expects($this->atLeastOnce())
            ->method('formatDate')
            ->willReturn('randomDate');
    }
    /**
     * Generates random values for product and catalogIds depending on scope
     * @param $scope
     * @return array
     */
    private function getMinMaxValues($scope,$numberOfProducts)
    {
        if ($scope == 1)
        {
            $values = [
                'productMin' => 1,
                'productMax' => 1,
                'catalogMin' => 1,
                'catalogMax' => 1
            ];
        }elseif ($scope == 0)
        {
            $values = [
                'productMin' => 1,
                'productMax' => $numberOfProducts,
                'catalogMin' => [],
                'catalogMax' => []
            ];
        }else {
            $values = [
                'productMin' => 1,
                'productMax' => $numberOfProducts/2,
                'catalogMin' => 1,
                'catalogMax' => $numberOfProducts/2
            ];
        }

        return $values;
    }
    /**
     * Generates Random Sku's
     * @return array
     */
    private function generateBunches($numberOfProducts)
    {
        $bunch = [];

        for ($i=0; $i<$numberOfProducts; $i++) {
            $bunch[] = [
              'sku' => substr(md5(mt_rand()), 0, 8)
            ];
        }
        return$bunch;
    }

    /**
     * Generates Random Product Mocks Array
     * @return array
     */
    private function getProductItems($numberOfProducts)
    {
        $products = [];
        for ($i=0; $i<$numberOfProducts; $i++)
        {
            $products[] = $this->productMock;
        }
        return $products;
    }

    /**
     * Generates Random Catalog Id
     * @return array
     */
    private function getCatalogIds($value,$numberOfProducts)
    {
        if(empty($value['catalogMin'])) {
            return [];
        }
        $catalogIds = [];
        for ($i=0; $i<$numberOfProducts; $i++)
        {
            $catalogIds[] = rand($value['catalogMin'],$value['catalogMax']);
        }
        return $catalogIds;
    }

    private function setRandomIdsForProductMock($value,$numberOfProducts)
    {
        for($i=0; $i<$numberOfProducts; $i++) {
            $this->productMock->expects($this->at($i))
                ->method('getId')
                ->willReturn(rand($value['productMin'],$value['productMax']));
        }
    }

    /**
     * Returns Possible Product amounts
     * @return array
     */
    public function getProductCount()
    {
        return [
            [200],
            [50],
            [400],
            [250]
        ];
    }
}