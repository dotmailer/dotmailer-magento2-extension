<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class ButtonReset extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    public $resetType;

    public function __construct(
        Context $context,
        $resetType,
        array $data = []
    ) {
        $this->resetType = $resetType;
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setLabel(__('Run Now'))
            ->setClass('ddg-reset')
            ->setDataAttribute(['ddg-url' => $this->getButtonUrl()])
            ->toHtml();
    }

    /**
     * Removes use Default Checkbox
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @return string
     */
    protected function getButtonUrl()
    {
        $query = [
            '_query' => [
                'reset-type' => $this->resetType
            ]
        ];
        return  $this->_urlBuilder->getUrl('dotdigitalgroup_email/run/reset', $query);
    }
}
