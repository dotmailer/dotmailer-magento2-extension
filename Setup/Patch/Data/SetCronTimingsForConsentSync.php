<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Cron\CronOffsetter;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class SetCronTimingsForConsentSync implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var ConfigResource
     */
    private $config;

    /**
     * @var CronOffsetter
     */
    private $cronOffsetter;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ConfigResource $config
     * @param CronOffsetter $cronOffsetter
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ConfigResource $config,
        CronOffsetter $cronOffsetter,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->config = $config;
        $this->cronOffsetter = $cronOffsetter;
        $this->reinitableConfig = $reinitableConfig;
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
            ->where('path = (?)', Config::XML_PATH_CRON_SCHEDULE_CONSENT);

        $consentCronSettings = $this->moduleDataSetup->getConnection()->fetchAll($query);

        if (empty($consentCronSettings)) {
            $this->config->saveConfig(
                Config::XML_PATH_CRON_SCHEDULE_CONSENT,
                $this->cronOffsetter->getCronPatternWithOffset('15'),
                'default',
                '0'
            );
        }

        $this->reinitableConfig->reinit();
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
