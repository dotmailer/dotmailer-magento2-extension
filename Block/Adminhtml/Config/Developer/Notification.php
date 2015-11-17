<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

class Notification extends \Magento\Config\Block\System\Config\Form\Field
{

	protected $dateTimeFormatter;

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param DateTimeFormatterInterface $dateTimeFormatter
	 * @param array $data
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		DateTimeFormatterInterface $dateTimeFormatter,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->dateTimeFormatter = $dateTimeFormatter;
	}


	protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{
		$element->setValue($this->_cache->load(\Dotdigitalgroup\Email\Helper\Config::CONNECTOR_FEED_LAST_CHECK_TIME));
		$format = $this->_localeDate->getDateTimeFormat(
			\IntlDateFormatter::MEDIUM
		);

		return $this->dateTimeFormatter->formatObject($this->_localeDate->date(intval($element->getValue())), $format);
	}
}
