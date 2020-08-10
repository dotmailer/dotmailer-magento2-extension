<?php

use Magento\Framework\Serialize\Serializer\Json;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$importerResourceModel = $objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Importer::class);

$serializer = $objectManager->create(Json::class);

$importerModel = $objectManager->create(Dotdigitalgroup\Email\Model\Importer::class);

$importerModel->setImportType("Reviews");
$importerModel->setWebsiteId(0);
$importerModel->setImportStatus(0);
$importerModel->setImportMode("Bulk");

$importerResourceModel->save($importerModel);
