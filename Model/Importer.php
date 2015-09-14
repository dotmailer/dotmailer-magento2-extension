<?php

namespace Dotdigitalgroup\Email\Model;

class Importer extends \Magento\Framework\Model\AbstractModel
{
	protected $_dateTime;

	/**
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Framework\Stdlib\DateTime $dateTime
	 * @param \Magento\Framework\Model\Resource\AbstractResource $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
	 * @param array $data
	 */
	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Stdlib\DateTime $dateTime,
		\Magento\Framework\Model\Resource\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	) {
		$this->_dateTime = $dateTime;
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	}

	/**
	 * constructor
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Resource\Importer');
	}

	/**
	 * Prepare data to be saved to database
	 *
	 * @return $this
	 */
	public function beforeSave()
	{
		parent::beforeSave();
		if ($this->isObjectNew()) {
			$this->setCreatedAt($this->_dateTime->formatDate(true));
		}
		$this->setUpdatedAt($this->_dateTime->formatDate(true));
		return $this;
	}

}