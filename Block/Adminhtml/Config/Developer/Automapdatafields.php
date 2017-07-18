<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Automapdatafields extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var string
     */
    public $buttonLabel = 'Run Now';

    /**
     * @param string $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $website = $this->getRequest()->getParam('website', 0);
        $params = ['website' => $website];
        $url = $this->_urlBuilder->getUrl('dotdigitalgroup_email/run/automapdatafields', $params);

        return $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setLabel($this->buttonLabel)
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }
}
