<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Chat\Settings;

class ConfigureWidgetButton extends ButtonField
{
    /**
     * Returns the Url to Configure Chat Widget
     * @return string
     */
    protected function getButtonUrl()
    {
        return $this->config->getConfigureChatWidgetButtonUrl();
    }
}
