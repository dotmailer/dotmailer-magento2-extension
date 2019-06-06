<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

/**
 * Validate API endpoint.
 * Note: this observer is added as a security measure and the event it listens for will not normally be dispatched.
 */
class ApiEndpointValidate implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    private $context;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Test
     */
    private $test;

    /**
     * ApiEndpointValidate constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Test $test
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\Apiconnector\Test $test
    ) {
        $this->test           = $test;
        $this->context        = $context;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $groups = $this->context->getRequest()->getPost('groups');

        $apiEndpoint = isset($groups['api']['fields']['endpoint']['value'])
            ? $groups['api']['fields']['endpoint']['value'] : false;

        if ($apiEndpoint) {
            $this->test->validateEndpoint($apiEndpoint);
        }

        return $this;
    }
}
