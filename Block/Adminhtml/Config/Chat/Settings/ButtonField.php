<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Chat\Settings;

use Dotdigitalgroup\Email\Model\Chat\Config;
use Dotdigitalgroup\Email\Helper\Data;

abstract class ButtonField extends \Magento\Config\Block\System\Config\Form\Field
{
    const COMAPI_BASE = 'https://portal.comapi.com/#/apiSpaces/';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Data
     */
    private $helper;

    /**
     * ButtonField constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Config $config
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Config $config,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->config = $config;
        parent::__construct($context, $data);
        $this->config->setScopeAndWebsiteId($this->helper->getWebsiteForSelectedScopeInAdmin());
    }

    /**
     * Returns the class name based on API Creds validation
     * @return string
     */
    public function getCssClass()
    {
        if ($this->config->getApiSpaceId()) {
            return 'ddg-enabled-button';
        }
        return 'ddg-disabled-button';
    }

    /**
     * @return string
     */
    abstract protected function getButtonUrl();


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
            ->setLabel(__('Configure'))
            ->setOnClick("window.location.href='" . $this->getButtonUrl() . "'")
            ->setData('class', $this->getCssClass())
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
}
