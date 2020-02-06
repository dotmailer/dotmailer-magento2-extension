<?php

use Magento\Framework\Serialize\Serializer\Json;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$importerResourceModel = $objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Importer::class);

$serializer = $objectManager->create(Json::class);

$importedData = $serializer->serialize([
    "id" => 1,
    "parent_id" => 2,
    "name" => "Chaz Kangaroo_",
    "sku" => "chaz-kangaroo_sku",
    "price" => "25.0"
]);

$importerModel = $objectManager->create(Dotdigitalgroup\Email\Model\Importer::class);

$importerModel->setImportType("Catalog_Default");
$importerModel->setWebsiteId(0);
$importerModel->setImportStatus(0);
$importerModel->setImportMode("Bulk");
$importerModel->setImportData($importedData);

$importerResourceModel->save($importerModel);
