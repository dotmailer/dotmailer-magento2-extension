<?php

namespace Dotdigitalgroup\Email\Test\Integration\Model\Sync\Importer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;

class BulkRecordsImportedSuccessTest extends \PHPUnit\Framework\TestCase
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

    public function setUp() :void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->importerCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection::class
        );

        $this->catalog = $this->objectManager->create(
            Catalog::class
        );

        $configurations = [
            self::BASE_WEBSITE_CODE
        ];

        $this->setupConfigs($configurations);

        $this->mockClientFactory();

        $this->mockClient
            ->expects($this->atLeastOnce())
            ->method('getContactsTransactionalDataImportByImportId')
            ->willReturn((object) [
                'id' => 'Dummy-Id',
                'status' => "Finished"
            ]);

        $this->instantiateDataHelper();

        $this->importerSync = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Sync\Importer::class);
    }

    public static function loadFixtureMultipleBulkRecords()
    {
        $pathToFiles = '/../../../../Integration/_files/Importer/';
        $fileName = 'create_multiple_email_importer_records_status_processing.php';

        require __DIR__ . $pathToFiles. $fileName;
    }

    /**
     * @magentoDataFixture loadFixtureMultipleBulkRecords
     */
    public function testThatInHundredBulkRecordsAllRecordsAreImported()
    {
        $this->importerSync->sync();

        $importedResultIsImported = $this->importerCollection->addFieldToFilter('import_status', 0);

        $this->assertEquals($importedResultIsImported->getSize(), 0);

        $importedResultImportIsNotFinished = $this->importerCollection->addFieldToFilter('import_finished', '');

        $this->assertEquals($importedResultImportIsNotFinished->getSize(), 0);
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
