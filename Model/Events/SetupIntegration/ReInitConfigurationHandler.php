<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class ReInitConfigurationHandler extends AbstractSetupIntegrationHandler
{
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
