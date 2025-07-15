<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Export;

use Dotdigital\V3\Models\InsightData\Record;
use Dotdigital\V3\Models\InsightData\RecordsCollection;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\Product as ConnectorProduct;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory as ConnectorProductFactory;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkCatalogRecordCollectionBuilder;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SdkCatalogRecordCollectionBuilderTest extends TestCase
{
    /** @var ConnectorProductFactory|MockObject */
    private $connectorProductFactoryMock;

    /** @var Logger|MockObject */
    private $loggerMock;

    /** @var SdkCatalogRecordCollectionBuilder */
    private $builder;

    /** @var int */
    private $storeId = 1;

    /** @var int */
    private $customerGroupId = 1;

    protected function setUp(): void
    {
        $this->connectorProductFactoryMock = $this->createMock(ConnectorProductFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->builder = new SdkCatalogRecordCollectionBuilder(
            $this->connectorProductFactoryMock,
            $this->loggerMock,
            $this->storeId,
            $this->customerGroupId
        );
    }

    public function testBuildWithValidProducts(): void
    {
        $product = $this->createConfiguredMock(MagentoProduct::class, ['getId' => 10]);
        $connectorProduct = $this->createMock(ConnectorProduct::class);
        $connectorProduct->method('toArray')->willReturn(['id' => 10, 'name' => 'Test Product']);

        $this->connectorProductFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($connectorProduct);

        $this->builder->setBuildableData([$product]);
        $result = $this->builder->build();

        $this->assertInstanceOf(RecordsCollection::class, $result);
        $this->assertEquals(1, $result->count());

        $record = null;
        foreach ($result as $key => $item) {
            if ($key == '10') {
                $record = $item;
                break;
            }
        }

        $this->assertInstanceOf(Record::class, $record);
        $this->assertEquals(['id' => 10, 'name' => 'Test Product'], $record->getJson());
    }

    public function testBuildSkipsProductWithoutId(): void
    {
        $product = $this->createConfiguredMock(MagentoProduct::class, ['getId' => null]);
        $this->connectorProductFactoryMock->expects($this->never())->method('create');

        $this->builder->setBuildableData([$product]);
        $result = $this->builder->build();

        $this->assertInstanceOf(RecordsCollection::class, $result);
        $this->assertEquals(0, $result->count());
    }

    public function testBuildHandlesSchemaValidationException(): void
    {
        $product = $this->createConfiguredMock(MagentoProduct::class, ['getId' => 20]);
        $connectorProduct = $this->createMock(ConnectorProduct::class);

        $validatorMock = $this->createMock(SchemaValidator::class);
        $exception = new SchemaValidationException($validatorMock, new Phrase('Schema error'));

        $connectorProduct->method('toArray')
            ->willThrowException($exception);

        $this->connectorProductFactoryMock->method('create')->willReturn($connectorProduct);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Product id 20 was not exported'),
                $this->callback(function ($param) use ($exception) {
                    return $param[0] === $exception;
                })
            );

        $this->builder->setBuildableData([$product]);
        $result = $this->builder->build();

        $this->assertInstanceOf(RecordsCollection::class, $result);
        $this->assertEquals(0, $result->count());
    }

    public function testBuildHandlesGenericException(): void
    {
        $product = $this->createConfiguredMock(MagentoProduct::class, ['getId' => 30]);
        $connectorProduct = $this->createMock(ConnectorProduct::class);
        $genericException = new \Exception('Generic error');
        $connectorProduct->method('toArray')
            ->willThrowException($genericException);

        $this->connectorProductFactoryMock->method('create')->willReturn($connectorProduct);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Product id 30 was not exported'),
                $this->callback(function ($param) use ($genericException) {
                    return $param[0] === $genericException;
                })
            );

        $this->builder->setBuildableData([$product]);
        $result = $this->builder->build();

        $this->assertInstanceOf(RecordsCollection::class, $result);
        $this->assertEquals(0, $result->count());
    }

    public function testBuildWithEmptyData(): void
    {
        $this->connectorProductFactoryMock->expects($this->never())->method('create');
        $this->builder->setBuildableData([]);
        $result = $this->builder->build();

        $this->assertInstanceOf(RecordsCollection::class, $result);
        $this->assertEquals(0, $result->count());
    }
}
