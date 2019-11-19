<?php

namespace Dotdigitalgroup\Email\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Dotdigitalgroup\Email\Setup\Install\DataMigrationHelper;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var DataMigrationHelper
     */
    private $migrateData;

    /**
     * @var ModuleDataSetupInterface
     */
    private $installer;

    /**
     * InstallData constructor
     * @param ModuleDataSetupInterface $installer
     * @param DataMigrationHelper $migrateData
     */
    public function __construct(
        ModuleDataSetupInterface $installer,
        DataMigrationHelper $migrateData
    ) {
        $this->installer = $installer;
        $this->migrateData = $migrateData;
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $this->installer->startSetup();
        $this->migrateData->run();
        $this->installer->endSetup();
    }
}
