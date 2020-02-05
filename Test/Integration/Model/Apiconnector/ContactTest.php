<?php

namespace Dotdigitalgroup\Email\Test\Integration\Model\Apiconnector;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Model\Apiconnector\Contact;
use Dotdigitalgroup\Email\Setup\Install\Type\InsertEmailContactTableCustomers;
use Magento\Framework\Serialize\Serializer\Json;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Store\Model\ScopeInterface;

class ContactTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var Contact
     */
    private $contactSync;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var Importer
     */
    private $importer;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var DriverInterface
     */
    private $fileSystem;

    /**
     * @var CollectionFactory
     */
    private $importerCollectionFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->importerCollectionFactory = $this->objectManager->create(CollectionFactory::class);
        $this->contactCollectionFactory = $this->objectManager->create(ContactCollectionFactory::class);

        $this->cleanTables();

        $this->mockClientFactory();
        $this->setApiConfigFlags();

        foreach ([
            Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID => 'customers called chaz',
        ] as $path => $value) {
            $this->getMutableScopeConfig()->setValue($path, $value, ScopeInterface::SCOPE_WEBSITE);
        }

        $this->helper = $this->instantiateDataHelper();

        $this->contactSync = $this->objectManager->create(Contact::class);
        $this->importer = $this->objectManager->create(Importer::class);
        $this->fileHelper = $this->objectManager->create(File::class);
        $this->fileSystem = $this->objectManager->create(DriverInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testContactExportBulkImportQueued()
    {
        $this->contactSync->sync();
        $contactsQueue = $this->getContactImporterQueue();
        $website = $this->helper->getWebsiteById(1);

        $this->assertEquals(1, $contactsQueue['totalRecords']);
        $this->assertContains(
            sprintf(
                '%s_customers_%s',
                $website->getCode(),
                (new \DateTime('now', new \DateTimeZone('UTC')))->format('d_m_Y')
            ),
            end($contactsQueue['items'])['import_file']
        );
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testContactExportCsvFileExists()
    {
        $this->contactSync->sync();
        $contactsQueue = $this->getContactImporterQueue();

        $this->assertFileExists($this->fileHelper->getFilePath(end($contactsQueue['items'])['import_file']));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testContactExportCsvContainsContacts()
    {
        // get the contacts we expect to be exported
        $contactsToExport = $this->contactSync->getContacts($this->helper->getWebsiteById(1))->toArray();

        $this->contactSync->sync();
        $contactsQueue = $this->getContactImporterQueue();
        $csv = $this->getCsvContent(end($contactsQueue['items'])['import_file']);

        $this->assertArraySubset(array_column($contactsToExport, 'email'), array_column($csv, 'Email'));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testCsvExportContainsExpectedColumns()
    {
        $this->contactSync->sync();
        $contactsQueue = $this->getContactImporterQueue();
        $csv = $this->getCsvContent(end($contactsQueue['items'])['import_file']);

        $this->assertArraySubset(
            array_values($this->contactSync->getContactExportColumns($this->helper->getWebsiteById(1))),
            array_keys(reset($csv))
        );
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testCsvContainsCustomAttributeColumns()
    {
        /** @var Json $serializer */
        $serializer = $this->objectManager->create(Json::class);
        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS => $serializer->serialize([[
                'attribute' => 'downward_trend',
                'datafield' => 'DOWNWARD_TREND',
            ], [
                'attribute' => 'chatty_console',
                'datafield' => 'CHATTY_CONSOLE',
            ]]),
        ]);
        $website = $this->helper->getWebsiteById(1);
        $columns = $this->contactSync->getContactExportColumns($website);

        $this->assertArraySubset([
            'downward_trend' => 'DOWNWARD_TREND',
            'chatty_console' => 'CHATTY_CONSOLE',
        ], $columns);
    }

    /**
     * Load the generated CSV
     *
     * @param string $fileName
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getCsvContent(string $fileName)
    {
        $resource = $this->fileSystem->fileOpen(
            $this->fileHelper->getFilePath($fileName),
            'r'
        );

        $csvRow = 0;
        $rowHeaders = [];
        $csv = [];
        while ($result = $this->fileSystem->fileGetCsv($resource)) {
            if ($csvRow++ === 0) {
                $rowHeaders = $result;
                continue;
            }
            $csv[] = array_combine($rowHeaders, $result);
        }
        $this->fileSystem->fileClose($resource);

        return $csv;
    }

    /**
     * @return array
     */
    private function getContactImporterQueue()
    {
        /** @var Collection $importerCollection */
        $importerCollection = $this->importerCollectionFactory->create();
        return $importerCollection->getQueueByTypeAndMode(
            Importer::IMPORT_TYPE_CONTACT,
            Importer::MODE_BULK,
            10,
            [1]
        )->toArray();
    }

    /**
     * Clear all data from contact and importer tables and migrate contacts
     */
    private function cleanTables()
    {
        /** @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource */
        $importerResource = $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Importer::class);
        /** @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource */
        $contactResource = $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Contact::class);

        foreach ($this->importerCollectionFactory->create()->getItems() as $item) {
            $importerResource->delete($item);
        }
        foreach ($this->contactCollectionFactory->create()->getItems() as $item) {
            $contactResource->delete($item);
        }

        ObjectManager::getInstance()->create(InsertEmailContactTableCustomers::class)->execute();
    }
}
