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

    protected function _gedddtElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Include Procolor library JS file
        //$html = '<script type="text/javascript" src="' . $jsPath . 'js/procolor-1.0/procolor.compressed.js' .'"></script>';
        $html = '<script type="text/javascript" src="procolor.compressed.js"></script>';

//$html = '<script type="text/javascript">ProColor.prototype.attachButton("connector_configuration_dynamic_content_style_font_color", { imgPath:"http://magentotwo.dev/admin/admin/index/index/key/a35d85a33acddedae2753bf2b47ba63cbc8c768fc1a6740819ceabf2a0691af8/connector/procolor-1.0/img/procolor_win_", showInField: true });</script><script type="text/javascript">';

        // Use Varien text element as a basis
        $input = $this->_text;

        // Set data from config element on Varien text element
        $input->setForm($element->getForm())
            ->setElement($element)
            ->setValue($element->getValue())
            ->setHtmlId($element->getHtmlId())
            ->setName($element->getName())
            ->setStyle('width: 60px') // Update style in order to shrink width
            ->addClass('validate-hex'); // Add some Prototype validation to make sure color code is correct

        // Inject updated Varien text element HTML in our current HTML
        $html .= $input->getHtml();

        // Inject Procolor JS code to display color picker
        $html .= $this->_getProcolorJs($element->getHtmlId());

        // Inject Prototype validation
        $html .= $this->_addHexValidator();

        return $html;
    }

    /**
     * Procolor JS code to display color picker
     *
     * @see http://procolor.sourceforge.net/examples.php
     * @param string $htmlId
     * @return string
     */
    protected function _getProcolorJs($htmlId)
    {
	    $this->_logger->debug('<script type="text/javascript">ProColor.prototype.attachButton(\'' . $htmlId . '\', { imgPath:\'' . $this->_urlBuilder->getUrl() . 'connector/procolor-1.0/' . 'img/procolor_win_\', showInField: true });</script>');
        return '<script type="text/javascript">ProColor.prototype.attachButton(\'' . $htmlId . '\', { imgPath:\'' . $this->_urlBuilder->getUrl() . 'connector/procolor-1.0/' . 'img/procolor_win_\', showInField: true });</script>';
    }

    /**
     * Prototype validation
     *
     * @return string
     */
    protected function _addHexValidator()
    {
        return
            '<script type="text/javascript">
                Validation.add(\'validate-hex\', \'' . 'Please enter a valid hex color code' . '\', function(v) {
                    return /^#(?:[0-9a-fA-F]{3}){1,2}$/.test(v);
                });
            </script>';
    }
}