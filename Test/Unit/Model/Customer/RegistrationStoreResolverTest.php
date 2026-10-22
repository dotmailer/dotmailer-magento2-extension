<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Customer;

use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;

class RegistrationStoreResolverTest extends TestCase
{
    /**
     * @var AdapterInterface|\PHPUnit\Framework\MockObject\Stub
     */
    private $connection;

    /**
     * @var RegistrationStoreResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        $this->connection = $this->createStub(AdapterInterface::class);
        $this->connection->method('select')->willReturn($select);

        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $defaultStore = $this->createStub(Store::class);
        $defaultStore->method('getId')->willReturn('1');
        $website = $this->createStub(Website::class);
        $website->method('getDefaultStore')->willReturn($defaultStore);
        $website->method('getStoreIds')->willReturn(['1' => '1', '2' => '2']);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->willReturn($website);

        $this->resolver = new RegistrationStoreResolver($resource, $storeManager);
    }

    public function testCustomerIsPinnedToRegistrationStore(): void
    {
        $this->connection->method('fetchPairs')->willReturn(['10' => '2']);

        $this->assertSame([10 => 2], $this->resolver->resolveMany([10], 1));
        $this->assertSame(2, $this->resolver->resolve(10, 1));
    }

    public function testRegistrationStoreInAnotherWebsiteFallsBackToWebsiteDefaultStore(): void
    {
        $this->connection->method('fetchPairs')->willReturn(['10' => '9']);

        $this->assertSame(1, $this->resolver->resolve(10, 1));
    }

    public function testMissingRegistrationStoreFallsBackToWebsiteDefaultStore(): void
    {
        $this->connection->method('fetchPairs')->willReturn(['10' => null]);

        $this->assertSame([10 => 1, 11 => 1], $this->resolver->resolveMany([10, 11], 1));
    }

    public function testNoCustomersResolvesToNothing(): void
    {
        $this->assertSame([], $this->resolver->resolveMany([], 1));
    }
}
