<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Trial extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return <<<EOT
<a href="{$this->getUrl('dotdigitalgroup_email/studio')}">
    <img style="margin-bottom: 15px" src="{$this->getViewFileUrl('Dotdigitalgroup_Email::images/banner.png')}" 
        alt="Open an Engagement Cloud account">
</a>
EOT;
    }
}
