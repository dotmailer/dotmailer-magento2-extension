<?php

namespace Dotdigitalgroup\Email\Model;

/**
 * Factory class for creating a DateTime object
 */
class DateTimeFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager = null;

    /**
     * @var null|string
     */
    private $instanceName  = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = '\\DateTime'
    ) {
        $this->instanceName = $instanceName;
        $this->objectManager = $objectManager;
    }

    /**
     * Create DateTime object with specified parameters
     *
     * @param array $data
     * @return \DateTime
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create('\\DateTime', $data);
    }
}
