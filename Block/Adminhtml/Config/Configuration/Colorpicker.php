<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration;

class Colorpicker extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Text
     */
    public $text;

    /**
     * Colorpicker constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Text $text
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Text $text
    ) {
        $this->text = $text;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        // Use Varien text element as a basis
        $input = $this->text;

        // Set data from config element on Varien text element
        $input->setForm($element->getForm())
            ->setElement($element)
            ->setValue($element->getValue())
            ->setHtmlId($element->getHtmlId())
            ->setClass('ddg-colpicker')
            ->setName($element->getName());

        // Inject updated Varien text element HTML in our current HTML
        $html = $input->getHtml();

        return $html;
    }
}
