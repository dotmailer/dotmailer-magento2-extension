<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class ButtonReset extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    public $resetType;

    /**
     * @var string
     */
    public $modulePath;

    /**
     * @param Context $context
     * @param string $resetType
     * @param string $modulePath
     * @param array $data
     */
    public function __construct(
        Context $context,
        string $resetType,
        string $modulePath = 'dotdigitalgroup_email',
        array $data = []
    ) {
        $this->modulePath = $modulePath;
        $this->resetType = $resetType;
        parent::__construct($context, $data);
    }

    /**
     * Get element HTML.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $block = $this->getLayout()
            ->createBlock(Button::class);

        /** @var Button $block */
        return $block->setType('button')
            ->setLabel(__('Run Now'))
            ->setClass('ddg-reset')
            ->setDataAttribute(['ddg-url' => $this->getButtonUrl()])
            ->toHtml();
    }

    /**
     * Removes use Default Checkbox.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get button URL.
     *
     * @return string
     */
    protected function getButtonUrl()
    {
        $query = [
            '_query' => [
                'reset-type' => $this->resetType
            ]
        ];
        return $this->_urlBuilder->getUrl(
            sprintf('%s/run/reset', $this->modulePath),
            $query
        );
    }
}
