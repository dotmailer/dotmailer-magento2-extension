<?php

namespace Dotdigitalgroup\Email\Model;

/**
 * Factory class for creating DateInterval object
 */
class DateIntervalFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * @var null|string
     */
    protected $_instanceName  = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = '\\DateInterval'
    ) {
        $this->_instanceName = $instanceName;
        $this->_objectManager = $objectManager;
    }

    /**
     * Create DateInterval object with specified parameters
     *
     * @param array $data
     * @return \DateInterval
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create('\\DateInterval', $data);
    }
}
