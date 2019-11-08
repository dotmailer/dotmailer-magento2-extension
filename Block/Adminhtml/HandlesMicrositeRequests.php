<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

use Dotdigitalgroup\Email\Model\Trial\TrialSetup;

trait HandlesMicrositeRequests
{
    /**
     * @var TrialSetup
     */
    private $trialSetup;

    /**
     * @return string
     */
    public function getLocalCallbackUrl(): string
    {
        return $this->getTrialSetup()->getLocalCallbackUrl();
    }

    /**
     * @return string
     */
    public function getTrialSignupHostAndScheme(): string
    {
        return $this->getTrialSetup()->getTrialSignupHostAndScheme();
    }

    /**
     * @return TrialSetup
     */
    private function getTrialSetup()
    {
        return $this->trialSetup
            ?: $this->trialSetup = $this->trialSetupFactory->create();
    }
}
