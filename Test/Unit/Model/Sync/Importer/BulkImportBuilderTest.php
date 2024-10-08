<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkImportBuilder;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkJson;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkJsonFactory;
use PHPUnit\Framework\TestCase;

class BulkImportBuilderTest extends TestCase
{
    public function testBulkImportBuilder()
    {
        $bulkJsonFactoryMock = $this->createMock(BulkJsonFactory::class);
        $bulkJsonMock = $this->createMock(BulkJson::class);
        $bulkJsonFactoryMock->method('create')->willReturn($bulkJsonMock);

        $model = $bulkJsonMock;
        $mode = ImporterModel::MODE_BULK;
        $type = ['testType'];
        $limit = Importer::TOTAL_IMPORT_SYNC_LIMIT;

        $builder = new BulkImportBuilder();
        $config = $builder
            ->setModel($model)
            ->setMode($mode)
            ->setType($type)
            ->setLimit($limit)
            ->build();

        $this->assertSame($model, $config['model']);
        $this->assertSame($mode, $config['mode']);
        $this->assertSame($type, $config['type']);
        $this->assertSame($limit, $config['limit']);
    }

    public function testBulkImportBuilderThrowsExceptionForMissingModel()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Model is required for Bulk Import config set');

        $builder = new BulkImportBuilder();
        $builder
            ->setType(['testType'])
            ->build();
    }

    public function testBulkImportBuilderThrowsExceptionForMissingType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type is required for Bulk Import config set');

        $builder = new BulkImportBuilder();
        $builder
            ->setModel('testModel')
            ->build();
    }
}
