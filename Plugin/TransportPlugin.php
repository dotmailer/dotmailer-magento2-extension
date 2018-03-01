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
     * @var \Dotdigitalgroup\Email\Model\Mail\AdapterInterface
     */
    private $mailAdapter;

    /**
     * @var int|null
     */
    private $storeId;

    /**
     * TransportPlugin constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Mail\AdapterInterfaceFactory $mailAdapterFactory
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Mail\AdapterInterfaceFactory $mailAdapterFactory,
        \Dotdigitalgroup\Email\Helper\Transactional $helper,
        \Magento\Framework\Registry $registry
    ) {
        $this->storeId = $registry->registry('transportBuilderPluginStoreId');
        $this->helper   = $helper;
        $this->mailAdapter = $mailAdapterFactory->create(
            [
            'host' => $this->helper->getSmtpHost($this->storeId),
            'config' => $this->helper->getTransportConfig($this->storeId)
            ]
        );
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
            // For >= 2.2
            if (method_exists($subject, 'getMessage')) {
                $this->mailAdapter->send($subject->getMessage());
            } else {
                //For < 2.2
                $reflection = new \ReflectionClass($subject);
                $property = $reflection->getProperty('_message');
                $property->setAccessible(true);
                $this->mailAdapter->send($property->getValue($subject));
            }
        } else {
            return $proceed();
        }
    }
}
