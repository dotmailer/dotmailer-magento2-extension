<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Trial extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Render.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return <<<EOT
<a href="{$this->getUrl('dotdigitalgroup_email/studio')}">
    <img style="margin-bottom: 15px" src="{$this->getViewFileUrl('Dotdigitalgroup_Email::images/banner.png')}"
        alt="Open a Dotdigital account">
</a>
EOT;
    }
}
