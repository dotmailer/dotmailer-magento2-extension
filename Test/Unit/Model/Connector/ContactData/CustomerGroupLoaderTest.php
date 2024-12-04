<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector\ContactData;

use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\ResourceModel\Group as GroupResource;
use Magento\Customer\Model\Group;
use Dotdigitalgroup\Email\Model\Connector\ContactData\CustomerGroupLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerGroupLoaderTest extends TestCase
{
    /**
     * @var GroupFactory|MockObject
     */
    private $groupFactoryMock;

    /**
     * @var GroupResource|MockObject
     */
    private $groupResourceMock;

    /**
     * @var CustomerGroupLoader
     */
    private $customerGroupLoader;

    protected function setUp(): void
    {
        $this->groupFactoryMock = $this->getMockBuilder(GroupFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupResourceMock = $this->getMockBuilder(GroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupLoader = new CustomerGroupLoader(
            $this->groupFactoryMock,
            $this->groupResourceMock
        );
    }

    public function testGetCustomerGroup(): void
    {
        $groupId = 1;
        $groupCode = 'General';

        $groupMock = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($groupMock);

        $this->groupResourceMock->expects($this->once())
            ->method('load')
            ->with($groupMock, $groupId);

        $groupMock->expects($this->once())
            ->method('getCode')
            ->willReturn($groupCode);

        $result = $this->customerGroupLoader->getCustomerGroup($groupId);
        $this->assertSame($groupCode, $result);

        // Test caching of loaded customer group
        $resultCached = $this->customerGroupLoader->getCustomerGroup($groupId);
        $this->assertSame($groupCode, $resultCached);
    }
}
