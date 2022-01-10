<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Dotdigitalgroup\Email\Setup\Install\DataMigrationHelper;

class MigrateData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var DataMigrationHelper
     */
    private $migrateData;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param DataMigrationHelper $migrateData
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        DataMigrationHelper $migrateData
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->migrateData = $migrateData;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        foreach ($this->migrateData->getTablesFromAvailableTypes() as $tableFromAvailableType) {
            $emailData = $this->moduleDataSetup
                ->getConnection()
                ->fetchOne(
                    $this->moduleDataSetup
                        ->getConnection()
                        ->select()
                        ->from($this->moduleDataSetup
                            ->getTable($tableFromAvailableType))
                );

            if (!$emailData) {
                $this->migrateData->run($tableFromAvailableType);
            }
        }
        $this->migrateData->generateAndSaveCode();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
