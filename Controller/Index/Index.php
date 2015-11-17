<?php

namespace Dotdigitalgroup\Email\Controller\Index;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{

	protected $pageFactory;
	protected $_localeDate;
	protected $_scopeConfig;

	/**
	 * Pass arguments for dependency injection
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 */
	public function __construct(Context $context, PageFactory $pageFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate

	)
	{
		$this->_scopeConfig = $scopeConfigInterface;
		$this->_localeDate = $localeDate;
		$this->pageFactory = $pageFactory;
		return parent::__construct($context);
	}

	/**
	 * Sets the content of the response
	 */
	public function execute()
	{

		$fromTime = $this->_localeDate->date();
		$interval = new \DateInterval("PT15M");
		$fromTime->sub($interval);

		$fromTime->getTimestamp();

		$date = $fromTime->format('Y-m-d H:i:s');

		$date = $this->_localeDate->date(null, null, false)->format('Y-m-d H:i:s');

		var_dump($date);die;

//		$dateStart = new \Datetime();
//		$dateStart->setTimezone(new \DateTimeZone($timezoneLocal));
//		$dateEnd->setTimezone(new \DateTimeZone($timezoneLocal));

		//$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->contactSync();
		//$model = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')->emailImporter();
		var_dump(__METHOD__);

		return $this->pageFactory->create();


	}
}