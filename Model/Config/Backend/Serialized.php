<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;

class Serialized extends \Magento\Framework\App\Config\Value
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Serialized constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        SerializerInterface $serializer = null
    ) {
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return null
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if (!is_array($value)) {
            $this->setValue(empty($value) ? false : $this->serializer->unserialize($value));
        }
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        if (is_array($this->getValue())) {
            $this->setValue($this->serializer->serialize($this->getValue()));
        }
        parent::beforeSave();
        return $this;
    }
}
