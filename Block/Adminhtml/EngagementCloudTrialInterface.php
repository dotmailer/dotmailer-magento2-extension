<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

interface EngagementCloudTrialInterface extends EngagementCloudEmbedInterface
{
    /**
     * Get local callback URL.
     *
     * @return string
     */
    public function getLocalCallbackUrl(): string;

    /**
     * Get trial signup host and scheme.
     *
     * @return string
     */
    public function getTrialSignupHostAndScheme(): string;
}
