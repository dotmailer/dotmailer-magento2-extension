<?php

namespace Dotdigitalgroup\Email\Test\Integration\Model\Sync\Importer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;

class BulkRecordsImportingSuccessTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    const BASE_WEBSITE_CODE = 'base';
    const DEFAULT = 'admin';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection
     */
    private $importerCollection;

    /**
     * @var Importer
     */
    private $importerSync;

    /**
     * @var Catalog
     */
    private $catalog;

    public function setUp()
    {
        $this->mockClientFactory();

        $this->instantiateDataHelper();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->importerCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection::class
        );

        $this->catalog = $this->objectManager->create(
            Catalog::class
        );

        $this->importerSync = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Sync\Importer::class);
    }

    public static function loadFixtureMultipleBulkRecords()
    {
        $pathToFiles = '/../../../../Integration/_files/Importer/';
        $fileName = 'create_multiple_email_importer_records.php';

        require __DIR__ . $pathToFiles. $fileName;
    }

    /**
     * @magentoDataFixture loadFixtureMultipleBulkRecords
     */
    public function testThatInHundredBulkRecordsAllRecordsAreImporting()
    {
        $configurations = [
            self::BASE_WEBSITE_CODE
        ];

        $this->setupConfigs($configurations);

        $this->mockClient
            ->expects($this->atLeastOnce())
            ->method('postAccountTransactionalDataImport')
            ->willReturn((object)[
                'id' => 'Dummy-Id',
                'status' => "NotStarted"
            ]);

        $this->mockClient
            ->expects($this->atLeastOnce())
            ->method('postContactsTransactionalDataImport')
            ->willReturn((object)[
                'id' => 'Dummy-Id',
                'status' => "NotStarted"
            ]);

        $this->importerSync->sync();

        $importedResultNotImported = $this->importerCollection->addFieldToFilter('import_status', 0);

        $this->assertEquals($importedResultNotImported->getSize(), 0);
    }

    private function setupConfigs($configurations)
    {
        foreach ($configurations as $websiteCode) {
            $this->setApiConfigFlags([
                Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID => 1
            ], $websiteCode, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES);
        }
    }
}
