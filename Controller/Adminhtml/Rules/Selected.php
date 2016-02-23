<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;


class Selected extends \Magento\Backend\App\AbstractAction
{

	protected $_http;

	public function __construct(
		Context $context,
		\Magento\Framework\App\Response\Http $http
	) {
		parent::__construct($context);
		$this->_http = $http;
	}

	/**
	 * Check the permission to run it
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed(
			'Dotdigitalgroup_Email::exclusion_rules'
		);
	}

	public function execute()
	{
		$id            = $this->getRequest()->getParam('ruleid');
		$attribute     = $this->getRequest()->getParam('attribute');
		$arrayKey      = $this->getRequest()->getParam('arraykey');
		$conditionName = $this->getRequest()->getParam('condition');
		$valueName     = $this->getRequest()->getParam('value');

		if ($arrayKey && $id && $attribute && $conditionName && $valueName) {

			$rule = $this->_objectManager->create(
				'\Dotdigitalgroup\Email\Model\Rules'
			)->load($id);
			//rule not found
			if ( ! $rule->getId()) {
				$this->_http->getHeaders()->clearHeaders();

				return $this->_http->setHeader(
					'Content-Type', 'application/json'
				)->setBody('Rule not found!');
			}
			$conditions         = $rule->getCondition();
			$condition          = $conditions[$arrayKey];
			$selectedConditions = $condition['conditions'];
			$selectedValues     = $condition['cvalue'];
			$type               = $this->_objectManager->create(
				'\Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type'
			)
				->getInputType($attribute);
			$conditionOptions   = $this->_objectManager->create(
				'Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition'
			)->getInputTypeOptions($type);

			$response['condition'] = str_replace(
				'value="' . $selectedConditions . '"',
				'value="' . $selectedConditions . '"' . 'selected="selected"',
				$this->_getOptionHtml(
					'conditions', $conditionName, $conditionOptions
				)
			);

			$elmType = $this->_objectManager->create(
				'\Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value'
			)->getValueElementType($attribute);

			if ($elmType == 'select' or $selectedConditions == 'null') {
				$is_empty = false;

				if ($selectedConditions == 'null') {
					$is_empty = true;
				}

				$valueOptions = $this->_objectManager->create(
					'\Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value'
				)->getValueSelectOptions($attribute, $is_empty);

				$response['cvalue'] = str_replace(
					'value="' . $selectedValues . '"',
					'value="' . $selectedValues . '"' . 'selected="selected"',
					$this->_getOptionHtml('cvalue', $valueName, $valueOptions)
				);
			} elseif ($elmType == 'text') {
				$html
					                = "<input style='width:160px' title='cvalue' class='' id='' name='$valueName' value='$selectedValues' />";
				$response['cvalue'] = $html;
			}
			$this->_http->getHeaders()->clearHeaders();
			$this->_http->setHeader('Content-Type', 'application/json')
				->setBody(
					$this->_objectManager->create(
						'Magento\Framework\Json\Encoder'
					)->encode($response)
				);
		}
	}

	protected function _getOptionHtml($title, $name, $options)
	{
		$block = $this->_view->getLayout()->createBlock(
			'Magento\Framework\View\Element\Html\Select'
		);
		$block->setOptions($options)
			->setId('')
			->setClass('')
			->setTitle($title)
			->setName($name)
			->setExtraParams('style="width:160px"');

		return $block->toHtml();
	}
}
