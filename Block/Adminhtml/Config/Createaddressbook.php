<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Createaddressbook extends \Magento\Config\Block\System\Config\Form\Field
{
	protected $_vatButtonLabel = 'Create New Addressbook';

	/**
	 * Set Validate VAT Button Label
	 *
	 * @param string $vatButtonLabel
	 * @return \Magento\Customer\Block\Adminhtml\System\Config\Validatevat
	 */
	public function setVatButtonLabel($vatButtonLabel)
	{
		$this->_vatButtonLabel = $vatButtonLabel;
		return $this;
	}

	/**
	 * Set template to itself
	 *
	 * @return \Magento\Customer\Block\Adminhtml\System\Config\Validatevat
	 */
	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		if (!$this->getTemplate()) {
			$this->setTemplate('system/config/createaddressbook.phtml');
		}
		return $this;
	}

	/**
	 * Unset some non-related element parameters
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
	 * Get the button and scripts contents
	 *
	 * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
	 * @return string
	 */
	protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{
		$originalData = $element->getOriginalData();
		$buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_vatButtonLabel;
		$url = $this->_urlBuilder->getUrl('*/connector/createnewaddressbook');
		$this->addData(
			[
				'button_label' => __($buttonLabel),
				'html_id' => $element->getHtmlId(),
				'ajax_url' => $url,
			]
		);

		return $this->_toHtml();
	}

	/**
	 * Ajax Create the addressbooks.
	 * @param Varien_Data_Form_Element_Abstract $element
	 *
	 * @return string
	 */
	protected function _gedtElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$url = Mage::helper('adminhtml')->getUrl('*/connector/createnewaddressbook');
		$website = Mage::app()->getRequest()->getParam('website', 0);

		$element->setData('after_element_html',
			"<script>
                function createAddressbook(form, element) {
                    var name       = $('connector_sync_settings_dynamic_addressbook_addressbook_name').value;
                    var visibility = $('connector_sync_settings_dynamic_addressbook_visibility').value;
                    var reloadurl  = '{$url}';
                    if(name && visibility){
                        new Ajax.Request(reloadurl, {
                            method: 'post',
                            parameters: {'name' : name, 'visibility' : visibility, 'website': '$website'},
                            onComplete: function(transport) {
                                window.location.reload();
                            }
                        });
                    }
                    return false;
                }
            </script>"
		);

		return parent::_getElementHtml($element);
	}


}
