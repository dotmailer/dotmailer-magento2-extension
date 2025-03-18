<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
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
     * @param SerializerInterface $serializer
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * After load.
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if (!is_array($value)) {
            $this->setValue(empty($value) ? false : $this->unserializeMethod($value));
        }
    }

    /**
     * Before save.
     *
     * @return $this
     */
    public function beforeSave()
    {
        if (is_array($this->getValue())) {
            $this->setValue($this->serializeMethod($this->getValue()));
        }
        parent::beforeSave();
        return $this;
    }

    /**
     * Unserialize.
     *
     * @param string $value
     * @return array|string
     */
    private function unserializeMethod($value)
    {
        try {
            return $this->serializer->unserialize($value);
        } catch (\InvalidArgumentException $e) {
            $this->_logger->debug((string) $e);
            return [];
        }
    }

    /**
     * Serialize.
     *
     * @param array $value
     * @return bool|string
     */
    private function serializeMethod($value)
    {
        try {
            return $this->serializer->serialize($value);
        } catch (\InvalidArgumentException $e) {
            $this->_logger->debug((string) $e);
            return '';
        }
    }
}
