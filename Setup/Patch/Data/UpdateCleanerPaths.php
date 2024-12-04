<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpdateCleanerPaths implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $oldCleanerSchedulePath = 'connector_developer_settings/cron_schedules/cleaner';
        $oldCleanerIntervalPath = 'connector_developer_settings/cron_schedules/table_cleaner_interval';

        $this->moduleDataSetup->getConnection()->startSetup();
        $select = $this->moduleDataSetup->getConnection()->select()->from(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['*']
        )->where('path in (?)', [$oldCleanerSchedulePath, $oldCleanerIntervalPath]);

        foreach ($this->moduleDataSetup->getConnection()->fetchAll($select) as $configRow) {
            switch ($configRow['path']) {
                case $oldCleanerSchedulePath:
                    $path = Config::XML_PATH_CRON_SCHEDULE_CLEANER;
                    break;
                case $oldCleanerIntervalPath:
                    $path = Config::XML_PATH_CRON_SCHEDULE_TABLE_CLEANER_INTERVAL;
                    break;
            }

            if (isset($path)) {
                $this->updateRow($configRow, $path);
            }
        }
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

    /**
     * Update consent record row paths.
     *
     * @param array $configRow
     * @param string $path
     * @return void
     */
    private function updateRow($configRow, $path)
    {
        if ($this->keyAlreadyExists($path)) {
            $this->moduleDataSetup->getConnection()->delete(
                $this->moduleDataSetup->getTable('core_config_data'),
                ['path = ?' => $configRow['path']]
            );
            return;
        }

        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'path' => $path,
            ],
            [
                'path = ?' => $configRow['path']
            ]
        );
    }

    /**
     * Check if newer path name equivalent already exists.
     *
     * @param string $path
     *
     * @return bool
     */
    private function keyAlreadyExists($path)
    {
        $existingKey = $this->moduleDataSetup->getConnection()->select()->from(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['*']
        )->where(
            'path = ?',
            $path
        );

        $result = $this->moduleDataSetup->getConnection()->fetchAll($existingKey);
        return count($result) > 0;
    }
}
