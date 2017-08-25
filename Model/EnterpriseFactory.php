<?php

namespace Dotdigitalgroup\Email\Model;

/**
 * Factory class to use in class constructor for code to work between CE & EE.
 * Injecting EE class in constructor while in CE will throw "class doesn't exit" error.
 * This factory creates on demand creation of enterprise only class instances.
 */
class EnterpriseFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create model for requested enterprise class
     *
     * @param string $instanceName
     * @param array $data
     * @return mixed
     */
    public function create($instanceName, array $data = [])
    {
        return $this->_objectManager->create($instanceName, $data);
    }
}