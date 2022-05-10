<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class InvalidConfigurationHandler extends AbstractSetupIntegrationHandler
{
    /**
     * Event Process
     *
     * @return string
     */
    public function update(): string
    {
        return $this->encode([
            'success' => false,
            'data' => "Dotdigital API is invalid or not configured correctly, please check your connection status",
        ]);
    }
}
