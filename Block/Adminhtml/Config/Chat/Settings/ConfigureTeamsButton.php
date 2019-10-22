<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Chat\Settings;

class ConfigureTeamsButton extends ButtonField
{
    /**
     * Returns the URL to Configure Chat Teams
     * @return string
     */
    protected function getButtonUrl()
    {
        return $this->config->getConfigureChatTeamButtonUrl();
    }
}
