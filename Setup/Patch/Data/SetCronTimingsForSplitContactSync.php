<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Cron\CronOffsetter;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;

class SetCronTimingsForSplitContactSync implements DataPatchInterface
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
     * Apply patch.
     *
     * The new split customer, subscriber and guest crons should run at
     * the same frequency as old (retired) combined contact sync.
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
            ->where('path in (?)', Config::XML_PATH_CRON_SCHEDULE_CONTACT);

        $contactSyncConfiguration = $this->moduleDataSetup->getConnection()->fetchAll($query);
        $contactSyncConfiguration = reset($contactSyncConfiguration);

        $this->initializeCronsWithOffset(
            (!empty($contactSyncConfiguration['value'])) ?
            $this->cronOffsetter->getDecodedCronValue($contactSyncConfiguration['value']) :
            '15'
        );
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
     * Initialize new crons with supplied value.
     *
     * @param string $value
     *
     * @return void
     */
    private function initializeCronsWithOffset($value)
    {
        foreach ([
             Config::XML_PATH_CRON_SCHEDULE_CUSTOMER,
             Config::XML_PATH_CRON_SCHEDULE_SUBSCRIBER,
             Config::XML_PATH_CRON_SCHEDULE_GUEST,
        ] as $path) {
            $this->configResource->saveConfig(
                $path,
                $this->cronOffsetter->getCronPatternWithOffset($value),
                'default',
                '0'
            );
        }
    }
}
