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
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    private $helper;

    /**
     * @var int|null
     */
    private $storeId;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter
     */
    private $smtpTransportAdapter;

    /**
     * TransportPlugin constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter $smtpTransportAdapterFactory
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
        $this->storeId = $registry->registry('transportBuilderPluginStoreId');
        $this->helper = $helper;
        $this->dataHelper = $dataHelper;
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
        if ($this->helper->isEnabled($this->storeId)) {
            try {
                $this->smtpTransportAdapter->send($subject, $this->storeId);

            } catch (\Exception $e) {
                $this->dataHelper->log("TransportPlugin send exception: " . $e->getMessage());
                return $proceed();
            }
        } else {
            return $proceed();
        }
    }
}
