<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateTransactionalHost implements DataPatchInterface
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
            ['config_id', 'value']
        )->where(
            'path = ?',
            \Dotdigitalgroup\Email\Helper\Transactional::XML_PATH_DDG_TRANSACTIONAL_HOST
        );
        foreach ($this->moduleDataSetup->getConnection()->fetchAll($select) as $configRow) {
            preg_match_all('/\d+/', $configRow['value'], $matches);
            $value = $matches[0];
            //Invalid Smtp Host
            if (!count($value) === 1) {
                continue;
            }
            $row = [
                'value' => reset($value)
            ];
            $this->moduleDataSetup->getConnection()->update(
                $this->moduleDataSetup->getTable('core_config_data'),
                $row,
                ['config_id = ?' => $configRow['config_id']]
            );
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
}
