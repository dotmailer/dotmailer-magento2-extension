<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Dotdigitalgroup\Email\Model\Sync\AutomationFactory;

/**
 * With the transition of automation cron to messages queues,
 * it is possible that at point of upgrade a merchant has pending
 * automations. Here we kick off one final sync.
 */
class ProcessPendingAutomations implements DataPatchInterface
{
    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * ProcessPendingAutomations constructor.
     *
     * @param AutomationFactory $automationFactory
     */
    public function __construct(
        AutomationFactory $automationFactory
    ) {
        $this->automationFactory = $automationFactory;
    }

    /**
     * Run automation sync.
     *
     * @return void
     * @throws LocalizedException
     */
    public function apply()
    {
        //$this->automationFactory->create()->sync();
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
