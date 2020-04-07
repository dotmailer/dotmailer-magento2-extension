<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

class TransactionalEmails implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    private $context;

    /**
     * TransactionalEmails constructor.
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->context = $context;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $groups = $this->context->getRequest()->getPost('groups');
        $value = $groups['ddg_transactional']['fields']['host']['value'];

        if (!$value) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please select a host.')
            );
        }
    }
}
