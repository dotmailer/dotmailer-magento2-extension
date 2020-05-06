<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

use Magento\Framework\FlagManager;
use Dotdigitalgroup\Email\Logger\Logger;

abstract class AbstractStatusProvider
{
    /**
     * @var FlagManager
     */
    protected $flagManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $statusProviderExceptionMessage;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode;

    /**
     * AbstractStatusProvider constructor.
     * @param FlagManager $flagManager
     * @param Logger $logger
     */
    public function __construct(
        FlagManager $flagManager,
        Logger $logger
    ) {
        $this->flagManager = $flagManager;
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        try {
            return $this->flagManager->getFlagData($this->monitorErrorFlagCode) !== null;
        } catch (\InvalidArgumentException $e) {
            $this->logger->error((string) $e);
            // A flag exists, but we can't read the data from it
            return true;
        }
    }

    /**
     * @param array $items
     * @return string
     */
    public function getErrorSummary($items = null)
    {
        $items = (empty($items)) ? $this->getErrorItemsFromFlag() : $items;
        return implode(', ', $items);
    }

    /**
     * @return array
     */
    private function getErrorItemsFromFlag()
    {
        try {
            $flagData = $this->flagManager->getFlagData($this->monitorErrorFlagCode);
        } catch (\InvalidArgumentException $e) {
            $flagData = [__($this->statusProviderExceptionMessage)];
        }

        return $flagData;
    }
}
