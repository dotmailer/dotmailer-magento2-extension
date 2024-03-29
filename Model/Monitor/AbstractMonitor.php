<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

use Magento\Framework\FlagManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

abstract class AbstractMonitor
{
    /**
     * @var FlagManager
     */
    protected $flagManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * The flag_code for the flag table
     * @var string
     */
    protected $monitorErrorFlagCode;

    /**
     * @var string
     */
    protected $typeName;

    /**
     * AbstractMonitor constructor.
     *
     * @param FlagManager $flagManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        FlagManager $flagManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->flagManager = $flagManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get the flag code for the monitor error flag.
     *
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * Get the flag code for the monitor error flag.
     *
     * @param array $errors
     */
    public function setSystemMessages($errors)
    {
        if (empty($errors['items'])) {
            $this->flagManager->deleteFlag($this->monitorErrorFlagCode);
            return;
        }

        $flagData = $this->filterErrorItems($errors['items']);

        $this->flagManager->saveFlag(
            $this->monitorErrorFlagCode,
            $flagData
        );
    }

    /**
     * Fetch errors for the given time window.
     *
     * @param array $timeWindow
     * @return array
     */
    abstract public function fetchErrors(array $timeWindow);

    /**
     * Filter error items.
     *
     * @param array $items
     * @return mixed
     */
    abstract public function filterErrorItems(array $items);
}
