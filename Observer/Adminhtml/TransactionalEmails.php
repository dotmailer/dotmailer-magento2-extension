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
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->context->getRequest();
        $groups = $request->getPost('groups');

        if (isset($groups['ddg_transactional']['fields']) &&
            isset($groups['ddg_transactional']['fields']['enabled']['value']) &&
            $groups['ddg_transactional']['fields']['enabled']['value'] === '1' &&
            isset($groups['ddg_transactional']['fields']['host']['value']) &&
            $groups['ddg_transactional']['fields']['host']['value'] === '0'
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please select a host.')
            );
        }
    }
}
