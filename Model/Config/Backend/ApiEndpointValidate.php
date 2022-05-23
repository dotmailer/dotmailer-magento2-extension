<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Dotdigitalgroup\Email\Model\Apiconnector\Test;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;

class ApiEndpointValidate extends \Magento\Framework\App\Config\Value
{
    /**
     * @var Test
     */
    private $validator;

    /**
     * ApiEndpointValidate constructor
     *
     * @param Test $validator
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Test $validator,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->validator = $validator;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate API endpoints before storage
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->validator->validateEndpoint($this->getValue());
        return parent::beforeSave();
    }
}
