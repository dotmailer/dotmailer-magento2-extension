<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Contact;

use Dotdigitalgroup\Email\Model\Sync\PendingContact\PendingContactUpdater;
use Dotdigitalgroup\Email\Model\Task\TaskRunInterface;

class PendingContactChecker implements TaskRunInterface
{
    /**
     * @var array
     */
    private $typeList;

    /**
     * @param array $typeList
     */
    public function __construct(
        array $typeList
    ) {
        $this->typeList = $typeList;
    }

    /**
     * Run pending contact checker.
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function run()
    {
        /** @var PendingContactUpdater $contactUpdater */
        foreach ($this->typeList as $contactUpdater) {
            if (is_a($contactUpdater, PendingContactUpdater::class)) {
                $contactUpdater->update();
            }
        }
    }
}
