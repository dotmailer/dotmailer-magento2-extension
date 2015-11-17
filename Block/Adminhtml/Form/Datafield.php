<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Form;

class Datafield extends \Magento\Framework\View\Element\Html\Select
{


	protected function _optionToHtml($option, $selected = false)
	{
		$selectedHtml = $selected ? ' selected="selected"' : '';

		if ($this->getIsRenderToJsTemplate() === true) {
			$selectedHtml .= ' <%= option_extra_attrs.option_' . self::calcOptionHash($option['value']) . ' %>';
		}

		$params = '';
		if (!empty($option['params']) && is_array($option['params'])) {
			foreach ($option['params'] as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $keyMulti => $valueMulti) {
						$params .= sprintf(' %s="%s" ', $keyMulti, $valueMulti);
					}
				} else {
					$params .= sprintf(' %s="%s" ', $key, $value);
				}
			}
		}

		return sprintf(
			'<option value="%s"%s %s>%s</option>',
			$this->escapeHtml($option['value']),
			$selectedHtml,
			$params,
			$this->escapeHtml($option['label'])
		);
	}



}