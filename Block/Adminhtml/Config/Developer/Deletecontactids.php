<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Deletecontactids extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var string
     */
    public $buttonLabel = 'Run Now';

    /**
     * @param $buttonLabel
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
     * @codingStandardsIgnoreStart
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //@codingStandardsIgnoreEnd
        $url = $this->_urlBuilder->getUrl(
            'dotdigitalgroup_email/run/deletecontactids'
        );

        return $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setType('button')
            ->setLabel(__($this->buttonLabel))
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }
}
