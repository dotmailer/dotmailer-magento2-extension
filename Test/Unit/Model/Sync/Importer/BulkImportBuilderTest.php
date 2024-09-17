<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkImportBuilder;
use PHPUnit\Framework\TestCase;

class BulkImportBuilderTest extends TestCase
{
    public function testBulkImportBuilder()
    {
        $model = 'testModel';
        $mode = ImporterModel::MODE_BULK;
        $type = ['testType'];
        $limit = Importer::TOTAL_IMPORT_SYNC_LIMIT;
        $useFile = false;

        $builder = new BulkImportBuilder();
        $config = $builder
            ->setModel($model)
            ->setMode($mode)
            ->setType($type)
            ->setLimit($limit)
            ->setUseFile($useFile)
            ->build();

        $this->assertSame($model, $config['model']);
        $this->assertSame($mode, $config['mode']);
        $this->assertSame($type, $config['type']);
        $this->assertSame($limit, $config['limit']);
        $this->assertSame($useFile, $config['useFile']);
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
