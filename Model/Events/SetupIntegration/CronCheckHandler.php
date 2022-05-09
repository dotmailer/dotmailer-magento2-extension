<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class CronCheckHandler extends AbstractSetupIntegrationHandler
{
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
