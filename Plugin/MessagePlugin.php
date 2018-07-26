<?php

namespace Dotdigitalgroup\Email\Plugin;

class MessagePlugin
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    private $transactionalHelper;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template
     */
    private $templateResource;

    /**
     * @var \Magento\Email\Model\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * MessagePlugin constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Transactional $transactionalHelper
     * @param \Magento\Email\Model\ResourceModel\Template $templateResource
     * @param \Magento\Email\Model\TemplateFactory $template
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Transactional $transactionalHelper,
        \Magento\Email\Model\ResourceModel\Template $templateResource,
        \Magento\Email\Model\TemplateFactory $template
    ) {
        $this->registry = $registry;
        $this->templateFactory = $template;
        $this->templateResource = $templateResource;
        $this->transactionalHelper = $transactionalHelper;
    }

    /**
     * Use after body plugin so the required data is alredy present to modify the sender name.
     * Force the sender name to be the one from email template table.
     *
     * @param \Magento\Framework\Mail\Message $message
     * @param string $body
     * @return mixed
     * @throws \Zend_Mail_Exception
     */
    public function afterSetBody(\Magento\Framework\Mail\Message $message, $body)
    {
        if ($templateId = $this->registry->registry('dotmailer_current_template_id')) {
            $template = $this->templateFactory->create();
            $this->templateResource->load($template, $templateId);
            //clear from as it trows an exception if alredy set
            if ($message->getFrom() &&
                $this->transactionalHelper->isDotmailerTemplate($template->getTemplateCode())
            ) {
                $message->clearFrom();
                $message->setFrom($template->getTemplateSenderEmail(), $template->getTemplateSenderName());
            }
        }

        return $body;
    }
}
