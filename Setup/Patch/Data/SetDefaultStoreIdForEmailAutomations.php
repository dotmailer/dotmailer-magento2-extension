<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class SetDefaultStoreIdForEmailAutomations implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->storeManager = $storeManager;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $emailAutomationTable = $this->moduleDataSetup->getTable(SchemaInterface::EMAIL_AUTOMATION_TABLE);

        do {
            $query = $this->moduleDataSetup
                ->getConnection()
                ->select()
                ->from(
                    $emailAutomationTable
                )
                ->where('enrolment_status IN (?)', [StatusInterface::PENDING, StatusInterface::CONFIRMED])
                ->where('store_id IS NULL')
                ->limit(100);

            $automations = $this->moduleDataSetup->getConnection()->fetchAll($query);

            $bulkUpdateByWebsite = [];
            foreach ($automations as $automation) {
                if (array_key_exists($automation["website_id"], $bulkUpdateByWebsite)) {
                    continue;
                }
                $storeId = $this->storeManager->getWebsite($automation['website_id'])->getDefaultStore()->getId();
                $bulkUpdateByWebsite[$automation['website_id']] = $storeId;
            }

            foreach ($bulkUpdateByWebsite as $websiteId => $storeId) {
                $this->moduleDataSetup->getConnection()->update(
                    $emailAutomationTable,
                    [
                        'store_id' => $storeId
                    ],
                    [
                        'website_id = ?' => $websiteId,
                        'enrolment_status IN (?)' => [StatusInterface::PENDING, StatusInterface::CONFIRMED],
                    ]
                );
            }
        } while (count($automations));
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
