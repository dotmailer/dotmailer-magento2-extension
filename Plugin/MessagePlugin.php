<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Email\Model\ResourceModel\Template;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Registry;
use Zend\Mime\Mime;
use Zend\Mime\Part;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransportZend2;

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
     * @param mixed $body
     *
     * @return mixed
     */
    public function beforeSetBody(MessageInterface $message, $body)
    {
        $templateId = $this->isTemplate();
        if ($templateId && $this->shouldIntercept()) {
            $template = $this->loadTemplate($templateId);
            if ($this->isDotmailerTemplateCode($template->getTemplateCode())) {
                $this->handleZendMailMessage($message);
                $this->setMessageFromAddressFromTemplate($message, $template);
            }
            if (is_string($body) && ! $message instanceof \Zend_Mail) {
                return [self::createMimeFromString($body)];
            }
        }
        return null;
    }

    /**
     * @param $string
     * @return bool
     */
    private function isHTML($string)
    {
        return $string != strip_tags($string);
    }

    /**
     * Create HTML mime message from the string.
     *
     * @param string $body
     * @return \Zend\Mime\Message
     */
    private function createMimeFromString($body)
    {
        $bodyPart = new Part($body);
        $bodyPart->setEncoding(Mime::ENCODING_QUOTEDPRINTABLE);
        $bodyPart->setCharset(SmtpTransportZend2::ENCODING);
        ($this->isHTML($body)) ? $bodyPart->setType(Mime::TYPE_HTML) : $bodyPart->setType(Mime::TYPE_TEXT);
        $mimeMessage = new \Zend\Mime\Message();
        $mimeMessage->addPart($bodyPart);
        return $mimeMessage;
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
     * Handle a $message object that has extended \Zend_Mail
     * i.e. for Magento 2.1 > 2.2.7
     *
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
        if (method_exists($message, 'setFromAddress')) {
            $message->setFromAddress($template->getTemplateSenderEmail(), $template->getTemplateSenderName());
        } else {
            $message->setFrom($template->getTemplateSenderEmail(), $template->getTemplateSenderName());
        }
    }
}
