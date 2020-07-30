<?php

namespace Dotdigitalgroup\Email\Test\Integration\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;

class ImporterFailedTest extends \PHPUnit\Framework\TestCase
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

    public static function loadFixtureImporterDefaultCatalog()
    {
        $pathToFiles = '/../../../../Integration/_files/Importer/';
        $fileName = 'create_email_importer_catalog_record.php';

        require __DIR__ . $pathToFiles. $fileName;
    }

    /**
     * @magentoDataFixture loadFixtureImporterDefaultCatalog
     */
    public function testFailedCatalogDefaultImportsMarkEmailImporterAsFailed()
    {
        $configurations = [
            self::DEFAULT
        ];

        $this->setupConfigs($configurations);

        $this->mockClient
            ->expects($this->once())
            ->method('postAccountTransactionalDataImport')
            ->willReturn((object) [
                'message' => 'Error Unknown'
            ]);

        $this->importerSync->sync();

        $importedResult = $this->importerCollection->getFirstItem()->getData();
        $this->assertEquals($importedResult['import_type'], "Catalog_Default");
        $this->assertEquals($importedResult['website_id'], 0);
        $this->assertEquals($importedResult['import_status'], 3);
        $this->assertEquals($importedResult['import_mode'], "Bulk");
    }

    private function setupConfigs($configurations)
    {
        foreach ($configurations as $websiteCode) {
            $this->setApiConfigFlags([
            ], $websiteCode, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES);
        }
    }
}
