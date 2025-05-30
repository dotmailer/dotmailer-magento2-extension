<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class CreateOrUpdateGuestsFromPendingOrders
 * Now that guests are not added to the contact table during order sync,
 * we may have unprocessed orders in our table for which we need to capture guest data.
 */
class ResetContactsToAcquireContactIds implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var ContactResourceFactory
     */
    private $contactResourceFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ContactResourceFactory $contactResourceFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ContactResourceFactory $contactResourceFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->contactResourceFactory = $contactResourceFactory;
    }

    /**
     * Apply patch
     *
     * @return void
     * @throws LocalizedException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $contactResource = $this->contactResourceFactory->create();
        $contactResource->resetAllCustomers();
        $contactResource->resetSubscribers();

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
