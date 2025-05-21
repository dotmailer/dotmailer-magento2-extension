<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Catalog;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Queue\Catalog\PriceRuleUpdateConsumer;
use Dotdigitalgroup\Email\Model\Queue\Data\PriceRuleData;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;

class PriceRuleUpdateConsumerTest extends TestCase
{
    /**
     * @var PriceRuleUpdateConsumer
     */
    private $priceRuleUpdateConsumer;

    /**
     * @var CatalogRuleRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $catalogRuleRepositoryMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var CatalogFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $catalogResourceFactoryMock;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;

    /**
     * @var RuleFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ruleFactoryMock;

    protected function setUp(): void
    {
    }

    public function testProcess(): void
    {
    }
}
