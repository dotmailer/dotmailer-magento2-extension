<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetDefaultRoleForSystemAlerts implements DataPatchInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var RoleCollectionFactory
     */
    private $roleCollection;

    /**
     * @var ReinitableConfigInterface
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param Data $helper
     * @param RoleCollectionFactory $roleCollection
     * @param ReinitableConfigInterface $config
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigResource $configResource
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        Data $helper,
        RoleCollectionFactory $roleCollection,
        ReinitableConfigInterface $config,
        ScopeConfigInterface $scopeConfig,
        ConfigResource $configResource,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->helper = $helper;
        $this->roleCollection = $roleCollection;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        if (!$this->scopeConfig->isSetFlag(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_USER_ROLES)) {
            $defaultRole = $this->roleCollection->create()
                ->setRolesFilter()
                ->getFirstItem();

            $this->configResource->saveConfig(
                Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_USER_ROLES,
                $defaultRole->getId(),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );

            //Clear config cache
            $this->config->reinit();
        }
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
