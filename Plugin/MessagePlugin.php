<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Email\Model\ResourceModel\Template;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Registry;

class MessagePlugin
{
    /**
     * @var Transactional
     */
    private $transactionalHelper;

    /**
     * @var Template
     */
    private $templateResource;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * MessagePlugin constructor.
     * @param Registry $registry
     * @param Transactional $transactionalHelper
     * @param Template $templateResource
     * @param TemplateFactory $template
     */
    public function __construct(
        Registry $registry,
        Transactional $transactionalHelper,
        Template $templateResource,
        TemplateFactory $template
    ) {
        $this->registry = $registry;
        $this->templateFactory = $template;
        $this->templateResource = $templateResource;
        $this->transactionalHelper = $transactionalHelper;
    }

    /**
     * @param MessageInterface $message
     * @param string $body
     *
     * @return mixed
     */
    public function afterSetBody(MessageInterface $message, $body)
    {
        $templateId = $this->isTemplate();
        if ($templateId && $this->shouldIntercept()) {
            $template = $this->loadTemplate($templateId);
            if ($this->isDotmailerTemplateCode($template->getTemplateCode())) {
                $this->handleZendMailMessage($message);
                $this->setMessageFromAddressFromTemplate($message, $template);
            }
        }
        return $body;
    }

    /**
     * @return int
     */
    private function isTemplate()
    {
        return $this->registry->registry('dotmailer_current_template_id');
    }

    /**
     * @return bool
     */
    private function shouldIntercept()
    {
        $storeId = $this->registry->registry('transportBuilderPluginStoreId');
        return $this->transactionalHelper->isEnabled($storeId);
    }

    /**
     * @param $templateId
     *
     * @return \Magento\Email\Model\Template
     */
    private function loadTemplate($templateId)
    {
        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $templateId);

        return $template;
    }

    /**
     * @param $templateCode
     *
     * @return bool
     */
    private function isDotmailerTemplateCode($templateCode)
    {
        return $this->transactionalHelper->isDotmailerTemplate($templateCode);
    }

    /**
     * @param MessageInterface $message
     */
    private function handleZendMailMessage($message)
    {
        if ($message instanceof \Zend_Mail) {
            $message->clearFrom();
        }
    }

    /**
     * @param MessageInterface $message
     * @param \Magento\Email\Model\Template $template
     */
    private function setMessageFromAddressFromTemplate($message, $template)
    {
        $message->setFrom($template->getTemplateSenderEmail(), $template->getTemplateSenderName());
    }
}
