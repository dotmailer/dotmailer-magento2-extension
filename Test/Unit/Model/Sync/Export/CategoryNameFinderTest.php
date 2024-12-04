<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Export;

use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\Sync\Export\CategoryNameFinder;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryNameFinderTest extends TestCase
{
    /**
     * @var CategoryCollectionFactory|MockObject
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteMock;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    /**
     * @var CategoryCollection|MockObject
     */
    private $categoryCollection;

    /**
     * @var CategoryNameFinder
     */
    private $categoryNameFinder;

    protected function setUp(): void
    {
        $this->categoryCollectionFactory = $this->createMock(CategoryCollectionFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->websiteInterfaceMock = $this->createMock(WebsiteInterface::class);
        $this->websiteMock = $this->createMock(Website::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->categoryCollection = $this->createMock(CategoryCollection::class);

        $this->categoryNameFinder = new CategoryNameFinder(
            $this->categoryCollectionFactory,
            $this->storeManager
        );
    }

    public function testGetCategoryNamesByStoreWithNoMappedFields()
    {
        $mappedFields = [];
        $result = $this->categoryNameFinder->getCategoryNamesByStore($this->websiteInterfaceMock, $mappedFields);
        $this->assertEmpty($result);
    }

    public function testGetCategoryNamesByStoreWithMappedFields()
    {
        $mappedFields = [
            Datafield::FIRST_CATEGORY_PUR => 'first_category_pur',
            Datafield::LAST_CATEGORY_PUR => 'last_category_pur',
            Datafield::MOST_PUR_CATEGORY => 'most_pur_category',
        ];

        $this->storeManager->expects($this->once())
            ->method('getWebsite')
            ->with($this->websiteInterfaceMock->getId())
            ->willReturn($this->websiteMock);

        $this->storeMock->method('getId')->willReturn(1);

        $this->websiteMock->method('getStores')
            ->willReturn([$this->storeMock]);

        $this->categoryCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->categoryCollection);

        $this->categoryCollection->expects($this->once())
            ->method('addNameToResult')
            ->willReturn($this->categoryCollection);

        $this->categoryCollection->expects($this->once())
            ->method('setStore')
            ->with($this->storeMock->getId())
            ->willReturn($this->categoryCollection);

        $this->categoryCollection->expects($this->once())
            ->method('setPageSize')
            ->with(10000)
            ->willReturn($this->categoryCollection);

        $mockedCategories = $this->createCategoryMocks();

        $this->categoryCollection->method('getIterator')
            ->willReturn(new \ArrayIterator($mockedCategories));

        $result = $this->categoryNameFinder->getCategoryNamesByStore($this->websiteInterfaceMock, $mappedFields);

        $expectedCategoryNames = [
            1 => [
                1 => 'cat1name',
                2 => 'cat2name',
                3 => 'cat3name',
                4 => 'cat4name',
                5 => 'cat5name',
            ],
        ];
        $this->assertEquals($expectedCategoryNames, $result);
    }

    /**
     * @return array
     */
    private function createCategoryMocks()
    {
        $mocks = [];

        for ($i = 1; $i <= 5; $i++) {
            $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
                ->onlyMethods(['getId', 'getName'])
                ->disableOriginalConstructor()
                ->getMock();
            $categoryMock->method('getId')->willReturn($i);
            $categoryMock->method('getName')->willReturn('cat' . $i . 'name');
            $mocks[] = $categoryMock;
        }

        return $mocks;
    }
}
