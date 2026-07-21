<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
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
     * @var State
     */
    private $appState;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * ProcessPendingAutomations constructor.
     *
     * @param State $appState
     * @param AutomationFactory $automationFactory
     */
    public function __construct(
        State $appState,
        AutomationFactory $automationFactory
    ) {
        $this->appState = $appState;
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
        try {
            $this->appState->setAreaCode(Area::AREA_CRONTAB);
        } catch (LocalizedException $e) {
            // Area code is already set; proceed.
        }

        $this->automationFactory->create()->sync();
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
