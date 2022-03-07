<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Model\Cron;
use Dotdigitalgroup\Email\Model\Cron\CronOffsetter;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class SetCronsWithOffset implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CronOffsetter
     */
    private $cronOffsetter;

    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @var ReinitableConfigInterface
     */
    private $config;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CronOffsetter $cronOffsetter
     * @param ConfigResource $configResource
     * @param ReinitableConfigInterface $config
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CronOffsetter $cronOffsetter,
        ConfigResource $configResource,
        ReinitableConfigInterface $config
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->cronOffsetter = $cronOffsetter;
        $this->configResource = $configResource;
        $this->config = $config;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        $query = $this->moduleDataSetup
            ->getConnection()
            ->select()
            ->from(
                $configTable,
                ['path','scope_id','value']
            )
            ->where('path in (?)', array_keys(Cron::CRON_PATHS));

        $cronConfigurations = $this->moduleDataSetup->getConnection()->fetchAll($query);

        if (empty($cronConfigurations)) {
            $this->initializeCronsWithOffset();
        } else {
            $this->updateCronsWithOffset($cronConfigurations);
        }

        //Clear config cache
        $this->config->reinit();
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
     * @param $cronConfigurations
     */
    private function updateCronsWithOffset($cronConfigurations)
    {
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        foreach ($cronConfigurations as $configuration) {
            if (strpos($configuration['value'], "-") !== false) {
                continue;
            }

            $slashRemoved = (strpos($configuration['value'], "/") !== false) ?
                explode("/", $configuration["value"])[1] :
                $configuration["value"];

            $offset = trim(explode("*", $slashRemoved)[0]);
            $configuration["value"] = $this->cronOffsetter->getCronPatternWithOffset($offset);

            $this->moduleDataSetup->getConnection()->update(
                $configTable,
                [
                    'value' => $configuration["value"]
                ],
                [
                    'scope_id = ?' => $configuration["scope_id"],
                    'path = ?' => $configuration["path"]
                ]
            );
        }
    }

    /**
     * @return void
     */
    private function initializeCronsWithOffset()
    {
        foreach (Cron::CRON_PATHS as $path => $value) {
            $this->configResource->saveConfig(
                $path,
                $this->cronOffsetter->getCronPatternWithOffset($value),
                'default',
                '0'
            );
        }
    }
}
