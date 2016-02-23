<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Condition
{

	/**
	 * options array
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$options = array(
			array('value' => 'eq', 'label' => __('is')),
			array('value' => 'neq', 'label' => __('is not')),
			array('value' => 'null', 'label' => __('is empty')),
		);

		return $options;
	}

	/**
	 * get condition options according to type
	 *
	 * @param $type
	 *
	 * @return array
	 */
	public function getInputTypeOptions($type)
	{
		switch ($type) {
			case 'numeric':
				return $this->optionsForNumericType();

			case 'select':
				return $this->toOptionArray();

			case 'string':
				return $this->optionsForStringType();
		}

		return $this->optionsForStringType();
	}

	/**
	 * condition options for numeric type
	 *
	 * @return array
	 */
	public function optionsForNumericType()
	{
		$options   = $this->toOptionArray();
		$options[] = array('value' => 'gteq',
		                   'label' => __('equals or greater than'));
		$options[] = array('value' => 'lteq',
		                   'label' => __('equals or less then'));
		$options[] = array('value' => 'gt', 'label' => __('greater than'));
		$options[] = array('value' => 'lt', 'label' => __('less than'));

		return $options;
	}

	/**
	 * condition options for string type
	 *
	 * @return array
	 */
	public function optionsForStringType()
	{
		$options   = $this->toOptionArray();
		$options[] = array('value' => 'like', 'label' => __('contains'));
		$options[] = array('value' => 'nlike',
		                   'label' => __('does not contains'));

		return $options;
	}
}