<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
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
     * @var Registry
     */
    private $registry;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * MessagePlugin constructor.
     * @param Registry $registry
     * @param Transactional $transactionalHelper
     * @param TemplateFactory $templateFactory
     */
    public function __construct(
        Registry $registry,
        Transactional $transactionalHelper,
        TemplateFactory $templateFactory
    ) {
        $this->registry = $registry;
        $this->transactionalHelper = $transactionalHelper;
        $this->templateFactory = $templateFactory;
    }

    /**
     * @param MessageInterface $message
     * @param mixed $body
     *
     * @return mixed
     */
    public function beforeSetBody(MessageInterface $message, $body)
    {
        $dotTemplate = $this->templateFactory->create();
        $templateId = $dotTemplate->loadTemplateIdFromRegistry();
        if ($templateId && $this->shouldIntercept()) {
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
     * @return bool
     */
    private function shouldIntercept()
    {
        $storeId = $this->registry->registry('transportBuilderPluginStoreId');
        return $this->transactionalHelper->isEnabled($storeId);
    }
}
