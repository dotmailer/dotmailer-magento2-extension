<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateApiEndpointPath implements DataPatchInterface
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
        $this->moduleDataSetup->getConnection()->startSetup();
        $select = $this->moduleDataSetup->getConnection()->select()->from(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['config_id', 'scope_id']
        )->where(
            'path = ?',
            'connector/api/endpoint'
        );
        foreach ($this->moduleDataSetup->getConnection()->fetchAll($select) as $configRow) {
            // If there is a row in the same scope that already has the updated path, delete the older one
            if ($this->hasNewerEndpointRow($configRow['scope_id'])) {
                $this->moduleDataSetup->getConnection()->delete(
                    $this->moduleDataSetup->getTable('core_config_data'),
                    ['config_id = ?' => $configRow['config_id']]
                );
            // Otherwise update rows to the newer path
            } else {
                $this->moduleDataSetup->getConnection()->update(
                    $this->moduleDataSetup->getTable('core_config_data'),
                    [
                        'path' => Config::PATH_FOR_API_ENDPOINT
                    ],
                    [
                        'config_id = ?' => $configRow['config_id']
                    ]
                );
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
     * @param string $scopeId
     *
     * @return bool
     */
    private function hasNewerEndpointRow($scopeId)
    {
        $newerRow = $this->moduleDataSetup->getConnection()->select()->from(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['config_id']
        )
        ->where('path = ?', Config::PATH_FOR_API_ENDPOINT)
        ->where('scope_id = ?', $scopeId);

        $result = $this->moduleDataSetup->getConnection()->fetchAll($newerRow);
        return count($result) > 0;
    }
}
