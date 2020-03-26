<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;

class TemplatePlugin
{
    /**
     * @var Transactional
     */
    private $transactionalHelper;

    /**
     * @var
     */
    private $templateCode;

    /**
     * @var bool
     */
    private $isSaving = false;

    /**
     * TemplatePlugin constructor.
     * @param Transactional $transactionalHelper
     */
    public function __construct(
        Transactional $transactionalHelper
    ) {
        $this->transactionalHelper = $transactionalHelper;
    }

    /**
     * @param \Magento\Email\Model\Template $subject
     * @param array $result
     * @param array ...$args
     * @return mixed
     */
    public function afterGetData(\Magento\Email\Model\Template $subject, $result, ...$args)
    {
        //get the template code value
        if (! empty($args)) {
            if ($args[0] == 'template_code') {
                $this->templateCode = $result;
            }
        }

        if ($this->isSaving) {
            $result = $this->processTemplateBeforeSave($result, $args);
        } else {
            $result = $this->processTemplateOnLoadPreviewAndSend($result, $args);
        }

        return $result;
    }

    /**
     * Get data before saving
     *
     * @param mixed $result
     * @param array $args
     * @return mixed
     */
    private function processTemplateBeforeSave($result, $args)
    {
        //saving array values
        if (empty($args)) {
            $this->getResultIfArgsEmptyForBeforeSave($result);
        } else {
            //saving string value
            if ($args[0] != 'template_text') {
                return $result;
            }

            //compress the text body when is a dotmailer template
            if (!$this->isStringCompressed($result) &&
                $this->transactionalHelper->isDotmailerTemplate($this->templateCode)
            ) {
                $result = $this->compressString($result);
            }
        }

        return $result;
    }

    /**
     * @param array $result
     *
     * @return mixed
     */
    private function getResultIfArgsEmptyForBeforeSave($result)
    {
        if (isset($result['template_text'])) {
            $templateText = $result['template_text'];
            if (!$this->isStringCompressed($templateText) &&
                $this->transactionalHelper->isDotmailerTemplate($result['template_code'])) {
                $result['template_text'] = $this->compressString($templateText);
            }
        }

        return $result;
    }

    /**
     * preview/other/load
     *
     * @param mixed $result
     * @param array $args
     *
     * @return mixed
     */
    private function processTemplateOnLoadPreviewAndSend($result, $args)
    {
        if (empty($args)) {
            $result = $this->getResultIfArgsEmptyForLoadPreviewAndSend($result);
        } else {
            if ($args[0] != 'template_text') {
                return $result;
            }

            if ($this->isStringCompressed($result)) {
                $result = $this->decompressString($result);
            }
        }

        return $result;
    }

    /**
     * @param mixed $result
     *
     * @return mixed
     */
    private function getResultIfArgsEmptyForLoadPreviewAndSend($result)
    {
        if (isset($result['template_text'])) {
            $templateText = $result['template_text'];
            if ($this->isStringCompressed($templateText)) {
                $result['template_text'] = $this->decompressString($templateText);
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Email\Model\AbstractTemplate $subject
     */
    public function afterBeforeSave(\Magento\Email\Model\AbstractTemplate $subject)
    {
        $this->isSaving = true;
    }

    /**
     * Determine if the supplied string has been compressed,
     * by testing to see if it can be uncompressed.
     *
     * @param string $string
     * @return bool
     */
    private function isStringCompressed($string)
    {
        //@codingStandardsIgnoreLine
        return @gzuncompress(base64_decode($string)) !== false;
    }

    /**
     * @param string $templateText
     * @return string
     */
    private function compressString($templateText)
    {
        //@codingStandardsIgnoreLine
        return base64_encode(gzcompress($templateText, 9));
    }

    /**
     * @param string $templateText
     * @return string
     */
    private function decompressString($templateText)
    {
        //@codingStandardsIgnoreLine
        return gzuncompress(base64_decode($templateText));
    }
}
