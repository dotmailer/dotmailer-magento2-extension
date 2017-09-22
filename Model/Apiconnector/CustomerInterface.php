<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

interface CustomerInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Dotdigitalgroup\Email\Model\Apiconnector\CustomerExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\CustomerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Dotdigitalgroup\Email\Model\Apiconnector\CustomerExtensionInterface $extensionAttributes
    );
}