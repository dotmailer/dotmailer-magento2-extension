<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration;

class Colorpicker extends \Magento\Config\Block\System\Config\Form\Field
{

	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\UrlInterface $urlBuilder,
		\Magento\Framework\Data\Form\Element\Text $text
	)
	{
		$this->_urlBuilder = $urlBuilder;
		$this->_text = $text;
		parent::__construct($context);
	}

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Use Varien text element as a basis
        $input = $this->_text;

        // Set data from config element on Varien text element
        $input->setForm($element->getForm())
            ->setElement($element)
            ->setValue($element->getValue())
            ->setHtmlId($element->getHtmlId())
            ->setName($element->getName())
            ->setStyle('width: 60px'); // Update style in order to shrink width

        // Inject updated Varien text element HTML in our current HTML
        $html = $input->getHtml();

        // Inject JS code to display color picker
        $html .= $this->_getJs($element->getHtmlId());

        return $html;
    }

    /**
     * JS code to display color picker
     *
     * @see https://github.com/mrgrain/colpick
     * @param string $htmlId
     * @return string
     */
    protected function _getJs($htmlId)
    {
        return '<script type="text/javascript">
                    require([\'jquery\', \'domReady!\'], function($){
                            $(\'' . '#'.$htmlId . '\').colpick({
                                onChange:function(hsb,hex,rgb,el,bySetColor)
                                {
                                    $(el).val(\'#\'+hex);
                                }
                            });
                        });
                </script>';
    }
}