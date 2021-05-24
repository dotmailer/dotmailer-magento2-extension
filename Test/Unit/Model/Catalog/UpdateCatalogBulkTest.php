<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog;

use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Stdlib\DateTime;
use PHPUnit\Framework\TestCase;

class UpdateCatalogBulkTest extends TestCase
{
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
     * @var UpdateCatalogBulk
     */
    private $bulkUpdate;

    /**
     * @var Collection
     */
    private $collectionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $parentFinderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $productResourceMock;

    protected function setUp() :void
    {
        $this->resourceCatalogMock = $this->createMock(Catalog::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->bulkUpdate = $this->createMock(UpdateCatalogBulk::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->parentFinderMock = $this->createMock(ParentFinder::class);
        $this->productResourceMock = $this->createMock(ProductResource::class);

        $this->bulkUpdate = new UpdateCatalogBulk(
            $this->resourceCatalogMock,
            $this->collectionFactoryMock,
            $this->dateTimeMock,
            $this->parentFinderMock,
            $this->productResourceMock
        );
    }

    /**
     * @dataProvider getProductCount
     * @param $numberOfProducts
     */
    public function testIfWeHaveOnlyNewProducts($numberOfProducts)
    {
        $scope = 0;
        $this->prepareTests($scope, $numberOfProducts);

        $this->dateTimeMock->expects($this->once())
            ->method('formatDate')
            ->willReturn('randomDate');

        $this->parentFinderMock->expects($this->never())
            ->method('getConfigurableParentsFromProductIds');

        $this->resourceCatalogMock->expects($this->once())
            ->method('bulkProductImport');

        $this->resourceCatalogMock->expects($this->never())
            ->method('setUnprocessedByIds');

        $this->bulkUpdate->execute($this->generateBunches($numberOfProducts));
    }

    /**
     * $numberOfProducts;
     * @dataProvider getProductCount
     */
    public function testIfWeHaveNoNewProducts($numberOfProducts)
    {
        $scope = 1;
        $this->prepareTests($scope, $numberOfProducts);

        $this->dateTimeMock->expects($this->never())
            ->method('formatDate')
            ->willReturn('randomDate');

        $this->parentFinderMock->expects($this->once())
            ->method('getConfigurableParentsFromProductIds')
            ->willReturn(['sku' => 'chaz-kangaroo']);

        $this->resourceCatalogMock->expects($this->never())
            ->method('bulkProductImport');

        $this->resourceCatalogMock->expects($this->once())
            ->method('setUnprocessedByIds');

        $this->bulkUpdate->execute($this->generateBunches($numberOfProducts));
    }

    /**
     * $numberOfProducts;
     * @dataProvider getProductCount
     */
    public function testIfWeHaveBothNewAndAlreadyExistingEntries($numberOfProducts)
    {
        $scope = 2;

        $this->prepareTests($scope, $numberOfProducts);

        $this->dateTimeMock->expects($this->once())
            ->method('formatDate')
            ->willReturn('randomDate');

        $this->parentFinderMock->expects($this->once())
            ->method('getConfigurableParentsFromProductIds')
            ->willReturn(['sku' => 'chaz-kangaroo']);

        $this->resourceCatalogMock->expects($this->once())
            ->method('bulkProductImport');

        $this->resourceCatalogMock->expects($this->once())
            ->method('setUnprocessedByIds');

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

    private function prepareTests($scope, $numberOfProducts)
    {
        $values = $this->getMinMaxValues($scope, $numberOfProducts);

        $this->productResourceMock->expects($this->once())
            ->method('getProductsIdsBySkus')
            ->willReturn($this->getIdsForProductMock($values, $numberOfProducts));

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
            ->willReturn($this->getCatalogIds($values, $numberOfProducts));
    }

    /**
     * Generates random values for product and catalogIds depending on scope
     * @param $scope
     * @return array
     */
    private function getMinMaxValues($scope, $numberOfProducts)
    {
        if ($scope == 1) {
            $values = [
                'productMin' => 1,
                'productMax' => 1,
                'catalogMin' => 1,
                'catalogMax' => 1
            ];
        } elseif ($scope == 0) {
            $values = [
                'productMin' => 1,
                'productMax' => $numberOfProducts,
                'catalogMin' => [],
                'catalogMax' => []
            ];
        } else {
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
     * @param $numberOfProducts
     * @return array
     */
    private function generateBunches($numberOfProducts)
    {
        $bunch = [];

        for ($i = 0; $i < $numberOfProducts; $i++) {
            $bunch[] = [
                'sku' => substr(hash("sha256", random_int(1, 9)), 0, 8)
            ];
        }
        return $bunch;
    }

    /**
     * Generates Random Catalog Id
     * @param array $value
     * @param int $numberOfProducts
     * @return array
     */
    private function getCatalogIds($value, $numberOfProducts)
    {
        if (empty($value['catalogMin'])) {
            return [];
        }
        $catalogIds = [];
        for ($i=0; $i<$numberOfProducts; $i++) {
            $catalogIds[] = rand($value['catalogMin'], $value['catalogMax']);
        }
        return $catalogIds;
    }

    /**
     * @param $value
     * @param $numberOfProducts
     * @return array
     */
    private function getIdsForProductMock($value, $numberOfProducts)
    {
        $productIds = [];
        for ($i = 0; $i < $numberOfProducts; $i++) {
            $productIds[] = (int) ($value['productMin'] === $value['productMax']) ?: $value['productMin'] + $i;
        }
        return $productIds;
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
