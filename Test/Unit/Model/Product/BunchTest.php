<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\FilterBuilder;
use Dotdigitalgroup\Email\Model\Product\Bunch;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Model\Product;

use PHPUnit\Framework\TestCase;

class BunchTest extends TestCase
{
    /**
     * @var FilterGroup
     */
    private $filterGroupMock;

    /**
     * @var FilterBuilder
     */
    private $filterBuilderMock;

    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteriaMock;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepositoryMock;

    /**
     * @var Bunch
     */
    private $bunch;

    /**
     * @var ProductSearchResultsInterface
     */
    private $productSearchResultsMock;

    /**
     * @var Product
     */
    private $productMock;

    protected function setUp()
    {
        $this->filterGroupMock = $this->createMock(FilterGroup::class);
        $this->filterBuilderMock = $this->createMock(FilterBuilder::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->productSearchResultsMock = $this->createMock(ProductSearchResultsInterface::class);
        $this->productMock = $this->createMock(Product::class);

        $this->prepareTests();

        $this->bunch = new Bunch(
            $this->productRepositoryMock,
            $this->searchCriteriaMock,
            $this->filterGroupMock,
            $this->filterBuilderMock
        );
    }

    private function prepareTests()
    {
        $numberOfProducts = 200;
        $values = [
            'productMin' => 1,
            'productMax' => $numberOfProducts / 2,
            'catalogMin' => 1,
            'catalogMax' => $numberOfProducts / 2
        ];

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

    }

    public function testThatWeGetIdsFromSkus()
    {
        $numberOfProducts = 200;
        $this->bunch->getProductIdsBySkuInBunch($this->generateBunches($numberOfProducts));
    }

    /**
     * Generates Random Product Mocks Array
     * @param int $numberOfProducts
     * @return array
     */
    private function getProductItems($numberOfProducts)
    {
        return array_fill(0, $numberOfProducts, $this->productMock);
    }

    /**
     * Generates Random Skus
     * @param int $numberOfProducts
     * @return array
     */
    private function generateBunches($numberOfProducts)
    {
        $bunch = [];
        for ($i = 0; $i < $numberOfProducts; $i++) {
            $bunch[] = [
                'sku' => substr(md5(mt_rand()), 0, 8)
            ];
        }

        return $bunch;
    }
}
