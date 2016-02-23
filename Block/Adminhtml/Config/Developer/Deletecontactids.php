<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Deletecontactids extends \Magento\Config\Block\System\Config\Form\Field
{


    protected $_buttonLabel = 'Run Now';

    /**
     * @param $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {

        $url = $this->_urlBuilder->getUrl(
            'dotdigitalgroup_email/run/deletecontactids'
        );

        return $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )
            ->setType('button')
            ->setLabel(__($this->_buttonLabel))
            ->setOnClick("window.location.href='" . $url . "'")
            ->toHtml();

    }

}
