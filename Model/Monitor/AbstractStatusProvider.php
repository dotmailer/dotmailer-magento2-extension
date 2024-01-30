<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

use Magento\Framework\FlagManager;
use Dotdigitalgroup\Email\Logger\Logger;

abstract class AbstractStatusProvider
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var Logger
     */
    private $logger;

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
     *
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
     * Get the flag code for the monitor error flag.
     *
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
     * Get the error summary from the flag.
     *
     * @param array $items
     * @return string
     */
    public function getErrorSummary($items = null)
    {
        $items = (empty($items)) ? $this->getErrorItemsFromFlag() : $items;
        return implode(', ', $items);
    }

    /**
     * Get the error items from the flag.
     *
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
