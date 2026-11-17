<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Catalog;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Queue\Catalog\PriceRuleUpdateConsumer;
use Dotdigitalgroup\Email\Model\Queue\Data\PriceRuleData;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class PriceRuleUpdateConsumerTest extends TestCase
{
    public function testProcess(): void
    {
        $priceRuleData = new PriceRuleData();
        $priceRuleData->setOldRule('{"name":"old"}');
        $priceRuleData->setNewRule('{"name":"new"}');

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('unserialize')->willReturnMap([
            ['{"name":"old"}', ['name' => 'old']],
            ['{"name":"new"}', ['name' => 'new']],
        ]);

        $oldRule = $this->createMock(Rule::class);
        $oldRule->expects($this->once())->method('setData')->with(['name' => 'old'])->willReturnSelf();
        $oldRule->method('getMatchingProductIds')->willReturn([1 => [1 => true], 2 => [1 => true]]);
        $newRule = $this->createMock(Rule::class);
        $newRule->expects($this->once())->method('setData')->with(['name' => 'new'])->willReturnSelf();
        $newRule->method('getMatchingProductIds')->willReturn([2 => [1 => true], 3 => [1 => true]]);
        $ruleFactory = $this->createMock(RuleFactory::class);
        $ruleFactory->expects($this->exactly(2))->method('create')->willReturnOnConsecutiveCalls($oldRule, $newRule);

        $catalogResource = $this->createMock(Catalog::class);
        $catalogResource->expects($this->once())->method('setUnprocessedByIds')->with([1, 2, 3]);
        $catalogResourceFactory = $this->createMock(CatalogFactory::class);
        $catalogResourceFactory->method('create')->willReturn($catalogResource);
        $helper = $this->createMock(Data::class);
        $helper->method('isDebugEnabled')->willReturn(false);

        $consumer = new PriceRuleUpdateConsumer(
            $this->createMock(Logger::class),
            $catalogResourceFactory,
            $serializer,
            $ruleFactory,
            $helper
        );
        $consumer->process($priceRuleData);
    }
}
