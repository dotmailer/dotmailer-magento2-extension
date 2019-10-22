<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

trait HandlesMicrositeRequests
{
    /**
     * @return string
     */
    public function getLocalCallbackUrl(): string
    {
        return $this->trialSetup->getLocalCallbackUrl();
    }

    /**
     * @return string
     */
    public function getTrialSignupHostAndScheme(): string
    {
        return $this->trialSetup->getTrialSignupHostAndScheme();
    }
}
