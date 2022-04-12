<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Backend;

use Dotdigitalgroup\Email\Model\Cron\CronOffsetter;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CronOffset extends \Magento\Framework\App\Config\Value
{

    /**
     * @var CronOffsetter
     */
    private $cronOffsetter;

    /**
     * CronOffset constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ScopeConfigInterface $config
     * @param CronOffsetter $cronOffsetter
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $config,
        CronOffsetter $cronOffsetter,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->cronOffsetter = $cronOffsetter;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return CronOffset
     */
    protected function _afterLoad()
    {
        if (strpos($this->getValue(), "/") !== false) {
            $parsed = explode("/", $this->getValue());
            $valueToSet = explode("*", $parsed[1]);
            $this->setValue((int) $valueToSet[0]);
        } else {
            $this->setValue('00');
        }
        return parent::_afterLoad();
    }

    /**
     * @return void
     */
    public function beforeSave()
    {
        if ($this->isCronValueChanged()) {
            $this->setValue($this->cronOffsetter->getCronPatternWithOffset($this->getValue()));
        } else {
            $this->setValue($this->getOldValue());
        }
    }

    /**
     * @return bool
     */
    private function isCronValueChanged()
    {
        $oldValue = $this->cronOffsetter->getDecodedCronValue($this->getOldValue());
        return $this->getValue() != $oldValue;
    }
}
