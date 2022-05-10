<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;

trait HandlesMicrositeRequests
{
    /**
     * @var IntegrationSetup
     */
    private $integrationSetup;

    /**
     * Get local callback url.
     *
     * @return string
     */
    public function getLocalCallbackUrl(): string
    {
        return $this->getIntegrationSetup()->getLocalCallbackUrl();
    }

    /**
     * Get trial signup host and scheme.
     *
     * @return string
     */
    public function getTrialSignupHostAndScheme(): string
    {
        return $this->getIntegrationSetup()->getTrialSignupHostAndScheme();
    }

    /**
     * Get integration setup.
     *
     * @return IntegrationSetup
     */
    private function getIntegrationSetup()
    {
        return $this->integrationSetup
            ?: $this->integrationSetup = $this->integrationSetupFactory->create();
    }
}
