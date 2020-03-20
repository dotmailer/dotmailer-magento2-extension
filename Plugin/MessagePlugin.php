<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Registry;
use Zend\Mime\Mime;
use Zend\Mime\Part;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransportZend2;
use Dotdigitalgroup\Email\Model\Email\TemplateService;

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
     * @var TemplateService
     */
    private $templateService;

    /**
     * MessagePlugin constructor.
     * @param Registry $registry
     * @param Transactional $transactionalHelper
     * @param TemplateService $templateService
     */
    public function __construct(
        Registry $registry,
        Transactional $transactionalHelper,
        TemplateService $templateService
    ) {
        $this->registry = $registry;
        $this->transactionalHelper = $transactionalHelper;
        $this->templateService = $templateService;
    }

    /**
     * @param MessageInterface $message
     * @param mixed $body
     *
     * @return mixed
     */
    public function beforeSetBody(MessageInterface $message, $body)
    {
        if ($this->shouldIntercept()) {
            if ($body instanceof \Zend\Mime\Message && $body->getParts()) {
                foreach ($body->getParts() as $bodyPart) {
                    if ($bodyPart instanceof Part) {
                        $bodyPart->setEncoding(Mime::ENCODING_QUOTEDPRINTABLE);
                    }
                }
                return [$body];
            }
            $templateId = $this->templateService->getTemplateId();
            if ($templateId && is_string($body) && !$message instanceof \Zend_Mail) {
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
