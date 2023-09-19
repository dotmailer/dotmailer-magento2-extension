<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;

class CronCheckHandler extends AbstractSetupIntegrationHandler
{
    /**
     * @var IntegrationSetup
     */
    private $integrationSetup;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param IntegrationSetup $integrationSetup
     */
    public function __construct(
        Logger $logger,
        IntegrationSetup $integrationSetup
    ) {
        $this->logger = $logger;
        $this->integrationSetup = $integrationSetup;
    }

    /**
     * Event Process
     *
     * @return string
     */
    public function update(): string
    {
        try {
            $cronStatus = $this->integrationSetup->checkCrons();
        } catch (\Exception $exception) {
            $this->logger->debug('Error in checkCrons', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Cron check failed - please check the Log Viewer",
            ]);
        }

        $message = $cronStatus ?
            "Cron check" :
            "Cron check failed - there are no pending Dotdigital cron jobs";

        return $this->encode([
            'success' => $cronStatus,
            'data' => $message
        ]);
    }
}
