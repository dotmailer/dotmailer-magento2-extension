<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\App\Config\ReinitableConfigInterface;

class ReInitConfigurationHandler extends AbstractSetupIntegrationHandler
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @param Logger $logger
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        Logger $logger,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->logger = $logger;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * Event Process
     *
     * @return string
     */
    public function update(): string
    {
        try {
            $this->reinitableConfig->reinit();
        } catch (\Exception $exception) {
            $this->logger->debug('Error message', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => $exception->getMessage(),
            ]);
        }
        return $this->encode([
            'success' => true,
            'data' => "Configurations reinitialised successfully",
        ]);
    }
}
