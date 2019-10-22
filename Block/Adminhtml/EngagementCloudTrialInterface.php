<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

interface EngagementCloudTrialInterface extends EngagementCloudEmbedInterface
{
    /**
     * @return string
     */
    public function getLocalCallbackUrl(): string;

    /**
     * @return string
     */
    public function getTrialSignupHostAndScheme(): string;
}
