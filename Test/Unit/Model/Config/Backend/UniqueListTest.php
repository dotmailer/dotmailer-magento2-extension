<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Config\Backend;

use Dotdigitalgroup\Email\Model\Config\Backend\UniqueList;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class UniqueListTest extends TestCase
{
    private const WEBSITE_VALUES = [
        'customers' => '10',
        'subscribers' => '20',
        'guests' => '30',
        'allow_non_subscribers' => '0',
    ];

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\Stub
     */
    private $storeManager;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $store = $this->createStub(Store::class);
        $store->method('getWebsiteId')->willReturn('5');
        $this->storeManager->method('getStore')->willReturn($store);
    }

    private function model(string $scope, $scopeId, array $fieldsetData): UniqueList
    {
        $context = $this->createStub(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createStub(ManagerInterface::class));

        $model = new UniqueList(
            $context,
            $this->createStub(Registry::class),
            $this->config,
            $this->createStub(TypeListInterface::class),
            $this->storeManager
        );
        $model->setData('scope', $scope);
        $model->setData('scope_id', $scopeId);
        $model->setData('fieldset_data', $fieldsetData);

        return $model;
    }

    public function testStoreScopeReadsInheritedValuesFromParentWebsite(): void
    {
        $this->config->expects($this->once())
            ->method('getValue')
            ->with(UniqueList::DOTDIGITAL_LIST_CONFIG_PATH, 'website', '5')
            ->willReturn(self::WEBSITE_VALUES);

        $this->model('stores', 3, [
            'customers' => '40',
            'subscribers' => null,
            'guests' => null,
            'allow_non_subscribers' => null,
        ])->beforeSave();
        $this->addToAssertionCount(1);
    }

    public function testWebsiteScopeReadsInheritedValuesFromDefault(): void
    {
        $this->config->expects($this->once())
            ->method('getValue')
            ->with(UniqueList::DOTDIGITAL_LIST_CONFIG_PATH, 'default', null)
            ->willReturn(self::WEBSITE_VALUES);

        $this->model('websites', 5, [
            'customers' => '40',
            'subscribers' => null,
            'guests' => null,
            'allow_non_subscribers' => null,
        ])->beforeSave();
        $this->addToAssertionCount(1);
    }

    public function testStoreOverrideClashingWithInheritedListIsRejected(): void
    {
        $this->config->method('getValue')->willReturn(self::WEBSITE_VALUES);

        $this->expectException(ValidatorException::class);

        // Customers overridden to the list the store still inherits for subscribers.
        $this->model('stores', 3, [
            'customers' => '20',
            'subscribers' => null,
            'guests' => null,
            'allow_non_subscribers' => null,
        ])->beforeSave();
    }

    public function testStoreOverridesWithDistinctListsAreAccepted(): void
    {
        $this->config->method('getValue')->willReturn(self::WEBSITE_VALUES);

        $this->model('stores', 3, [
            'customers' => '40',
            'subscribers' => '50',
            'guests' => '60',
            'allow_non_subscribers' => '1',
        ])->beforeSave();
        $this->addToAssertionCount(1);
    }

    public function testExplicitNoneOverrideDoesNotFallBackToInheritedList(): void
    {
        $this->config->method('getValue')->willReturn(self::WEBSITE_VALUES);

        // Guests set to "None" at store scope; customers moved onto the website's guest list is fine.
        $this->model('stores', 3, [
            'customers' => '30',
            'subscribers' => null,
            'guests' => '0',
            'allow_non_subscribers' => null,
        ])->beforeSave();
        $this->addToAssertionCount(1);
    }
}
