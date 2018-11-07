<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\Mail\TransportInterface;

/**
 * SMTP mail transport.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TransportPlugin
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter
     */
    private $smtpTransportAdapter;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * TransportPlugin constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter $smtpTransportAdapter
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter $smtpTransportAdapter,
        \Dotdigitalgroup\Email\Helper\Transactional $helper,
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->smtpTransportAdapter = $smtpTransportAdapter;
        $this->helper = $helper;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
    }

    /**
     * @param TransportInterface $subject
     * @param \Closure $proceed
     * @throws \Exception
     *
     * @return null
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        \Closure $proceed
    ) {
        $storeId = $this->registry->registry('transportBuilderPluginStoreId');
        if ($this->helper->isEnabled($storeId)) {
            try {
                $this->smtpTransportAdapter->send($subject, $storeId);

            } catch (\Exception $e) {
                $this->dataHelper->log("TransportPlugin send exception: " . $e->getMessage());
                return $proceed();
            }
        } else {
            return $proceed();
        }
    }
}
