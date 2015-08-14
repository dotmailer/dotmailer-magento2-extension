<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Setupdatafields extends \Magento\Config\Block\System\Config\Form\Field
{

	protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{
		$this->setElement($element);
		return $this->_getAddRowButtonHtml("Run Now");
	}

	protected function _getAddRowButtonHtml($title)
	{
		return $title;
		$website = $this->getRequest()->getParam('website', 0);
		$url = $this->getUrl("*/connector/setupdatafields/website/"  . $website );

		return $this->getLayout()->createBlock('adminhtml/widget_button')
		            ->setType('button')
		            ->setLabel($this->__($title))
		            ->setOnClick("window.location.href='" . $url . "'")
		            ->toHtml();
	}

}