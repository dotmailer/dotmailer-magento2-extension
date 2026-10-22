<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\Sync\Customer\ListCohortResolver;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;

class ListCohortResolverTest extends TestCase
{
    /**
     * @var Data|\PHPUnit\Framework\MockObject\Stub
     */
    private $helper;

    /**
     * @var RegistrationStoreResolver|\PHPUnit\Framework\MockObject\Stub
     */
    private $storeResolver;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\Stub
     */
    private $website;

    protected function setUp(): void
    {
        $this->helper = $this->createStub(Data::class);
        $this->storeResolver = $this->createStub(RegistrationStoreResolver::class);
        $this->website = $this->createStub(WebsiteInterface::class);
        $this->website->method('getId')->willReturn(1);
    }

    private function resolver(): ListCohortResolver
    {
        return new ListCohortResolver($this->helper, $this->storeResolver);
    }

    private function mapLists(array $listsByStore, bool $onlySubscribers = true): void
    {
        $this->helper->method('getCustomerListByStoreId')
            ->willReturnCallback(static function ($storeId) use ($listsByStore) {
                return $listsByStore[$storeId] ?? 0;
            });
        $this->helper->method('isOnlySubscribersForContactSync')
            ->willReturn($onlySubscribers);
    }

    public function testStoresInheritingOneWebsiteListFormASingleCohort(): void
    {
        $this->storeResolver->method('getWebsiteStoreIds')->willReturn([1, 2, 3]);
        $this->storeResolver->method('getFallbackStoreId')->willReturn(1);
        $this->mapLists([1 => '10', 2 => '10', 3 => '10']);

        $cohorts = $this->resolver()->resolve($this->website);

        $this->assertSame(
            [['listId' => 10, 'onlySubscribers' => true, 'storeIds' => [1, 2, 3], 'includeUnassigned' => true]],
            $cohorts
        );
    }

    public function testDistinctStoreOverridesFormSeparateCohorts(): void
    {
        $this->storeResolver->method('getWebsiteStoreIds')->willReturn([1, 2, 3]);
        $this->storeResolver->method('getFallbackStoreId')->willReturn(1);
        $this->mapLists([1 => '10', 2 => '20', 3 => '10']);

        $cohorts = $this->resolver()->resolve($this->website);

        $this->assertCount(2, $cohorts);
        $this->assertSame(10, $cohorts[0]['listId']);
        $this->assertSame([1, 3], $cohorts[0]['storeIds']);
        $this->assertTrue($cohorts[0]['includeUnassigned']);
        $this->assertSame(20, $cohorts[1]['listId']);
        $this->assertSame([2], $cohorts[1]['storeIds']);
        $this->assertFalse($cohorts[1]['includeUnassigned']);
    }

    public function testStoresWithNoListAreSkipped(): void
    {
        $this->storeResolver->method('getWebsiteStoreIds')->willReturn([1, 2]);
        $this->storeResolver->method('getFallbackStoreId')->willReturn(1);
        $this->mapLists([2 => '20']);

        $cohorts = $this->resolver()->resolve($this->website);

        $this->assertCount(1, $cohorts);
        $this->assertSame([2], $cohorts[0]['storeIds']);
        $this->assertFalse($cohorts[0]['includeUnassigned']);
    }

    public function testNonSubscriberGateIsTheWebsiteValueForEveryCohort(): void
    {
        $this->storeResolver->method('getWebsiteStoreIds')->willReturn([1, 2]);
        $this->storeResolver->method('getFallbackStoreId')->willReturn(1);
        $this->mapLists([1 => '10', 2 => '20'], false);

        $cohorts = $this->resolver()->resolve($this->website);

        $this->assertCount(2, $cohorts);
        $this->assertFalse($cohorts[0]['onlySubscribers']);
        $this->assertFalse($cohorts[1]['onlySubscribers']);
    }
}
