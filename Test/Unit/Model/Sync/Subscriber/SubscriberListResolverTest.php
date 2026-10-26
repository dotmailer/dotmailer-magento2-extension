<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberListResolver;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class SubscriberListResolverTest extends TestCase
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
     * @var SubscriberListResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->helper = $this->createStub(Data::class);
        $this->storeResolver = $this->createStub(RegistrationStoreResolver::class);
        $this->storeResolver->method('getWebsiteStoreIds')->willReturn([1, 2]);
        $this->resolver = new SubscriberListResolver($this->helper, $this->storeResolver);
    }

    public function testExplicitStoreTakesPrecedenceOverContactStore(): void
    {
        $this->assertSame(2, $this->resolver->resolveStoreId(1, 2, 1));
    }

    public function testContactStoreIsUsedForMessagesQueuedBeforeTheUpgrade(): void
    {
        $this->assertSame(1, $this->resolver->resolveStoreId(1, null, 1));
    }

    public function testFailsWhenNoStoreIsAvailable(): void
    {
        $this->expectException(LocalizedException::class);

        $this->resolver->resolveStoreId(1, null, 0);
    }

    public function testFailsWhenStoreIsOutsideTheWebsite(): void
    {
        $this->expectException(LocalizedException::class);

        $this->resolver->resolveStoreId(1, 9, 1);
    }

    public function testListIsResolvedAtStoreScope(): void
    {
        $this->helper->method('getSubscriberListByStoreId')
            ->willReturnCallback(static function ($storeId) {
                return $storeId === 2 ? '222' : '111';
            });

        $this->assertSame(222, $this->resolver->getListId(1, 2));
        $this->assertSame(111, $this->resolver->getListId(1, 1));
    }
}
