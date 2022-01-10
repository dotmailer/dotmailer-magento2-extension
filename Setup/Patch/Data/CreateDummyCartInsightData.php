<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Dotdigitalgroup\Email\Model\Sync\DummyRecordsFactory;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class CreateDummyCartInsightData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var DummyRecordsFactory
     */
    private $dummyRecordsFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        DummyRecordsFactory $dummyRecordsFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->dummyRecordsFactory = $dummyRecordsFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->dummyRecordsFactory
            ->create()
            ->sync();
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
