<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\System;

class Enterprisevalidator extends \Magento\Backend\Block\AbstractBlock
{
	public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element){

		$element->setData('after_element_html', "<script src='//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script><script type=\'text/javascript\'>");
		// Set up additional JavaScript for our validation using jQuery.
		$element->setData('after_element_html', "
	        jQuery.noConflict();
            jQuery(document).ready(function() {
				jQuery('#connector_data_mapping_enterprise_data-head').parent().hide();
            });
            </script>");


		return parent::_getElementHtml($element);

	}
}