<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

/**
 * Class Resetsubscribers
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer
 */
class Resetsubscribers extends \Magento\Config\Block\System\Config\Form\Field
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

        $website = $this->getRequest()->getParam('website', 0);
        $url = $this->_urlBuilder->getUrl('dotdigitalgroup_email/run/subscribersreset/website'
            . $website);

        return $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Button')
            ->setType('button')
            ->setLabel(__($this->buttonLabel))
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();
    }
}
