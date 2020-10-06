<?php

use Magento\Framework\Serialize\Serializer\Json;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$importerResourceModelForFailed = $objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Importer::class);

$serializer = $objectManager->create(Json::class);

$importedDataForFailed = $serializer->serialize([
    "id" => 1,
    "parent_id" => 2,
    "name" => "Chaz Kangaroo2_",
    "sku" => "chaz-kangaroo_sku2",
    "price" => "125.0"
]);

$importerModelForFailed = $objectManager->create(Dotdigitalgroup\Email\Model\Importer::class);

$importerModelForFailed->setImportType("Catalog_Default");
$importerModelForFailed->setWebsiteId(0);
$importerModelForFailed->setImportStatus(3);
$importerModelForFailed->setImportMode("Bulk");
$importerModelForFailed->setImportData($importedDataForFailed);

$importerResourceModelForFailed->save($importerModelForFailed);
