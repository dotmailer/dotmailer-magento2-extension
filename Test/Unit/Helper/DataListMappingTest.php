<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Helper;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class DataListMappingTest extends TestCase
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var array
     */
    private $values = [];

    protected function setUp(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(function ($path, $scope, $scopeId) {
            return $this->values[$path][$scope][$scopeId] ?? null;
        });
        $scopeConfig->method('isSetFlag')->willReturnCallback(function ($path, $scope, $scopeId) {
            return (bool) ($this->values[$path][$scope][$scopeId] ?? false);
        });

        $this->helper = (new \ReflectionClass(Data::class))->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty(Data::class, 'scopeConfig');
        $property->setValue($this->helper, $scopeConfig);
    }

    public function testWebsiteGettersIgnoreStoreOverrides(): void
    {
        $this->values['sync_settings/addressbook/customers'] = [
            'website' => [1 => '10'],
            'store' => [2 => '20'],
        ];

        $this->assertSame('10', $this->helper->getCustomerAddressBook(1));
    }

    public function testStoreScopedGettersUseStoreValue(): void
    {
        $this->values['sync_settings/addressbook/customers'] = ['store' => [2 => '20']];
        $this->values['sync_settings/addressbook/subscribers'] = ['website' => [1 => '30']];
        $this->values['sync_settings/addressbook/guests'] = ['store' => [2 => '40']];

        $this->assertSame('20', $this->helper->getCustomerListByStoreId(2));
        $this->assertSame('30', $this->helper->getSubscriberAddressBook(1));
        $this->assertSame('40', $this->helper->getGuestListByStoreId(2));
    }

    public function testAllowNonSubscribersIsWebsiteScoped(): void
    {
        $this->values['sync_settings/addressbook/allow_non_subscribers'] = [
            'website' => [1 => '0'],
            'store' => [2 => '1'],
        ];

        $this->assertTrue($this->helper->isOnlySubscribersForContactSync(1));
    }
}
