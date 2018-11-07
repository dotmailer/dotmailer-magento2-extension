<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;

/**
 * Class TemplatePlugin
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TemplatePlugin
{
    /**
     * @var Transactional
     */
    public $transactionalHelper;

    /**
     * @var
     */
    private $templateCode;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * TemplatePlugin constructor.
     * @param Transactional $transactionalHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Transactional $transactionalHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->transactionalHelper = $transactionalHelper;
        $this->registry = $registry;
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

        if ($this->registry->registry('dotmailer_saving_data')) {
            $result = $this->getProcessedTemplateBeforeSave($result);
        } else {
            $result = $this->getProcessedTemplatePreviewAndOther($result, $args);
        }

        return $result;
    }

    /**
     * Get data before saving
     *
     * @param mixed $result
     *
     * @return mixed
     */
    private function getProcessedTemplateBeforeSave($result)
    {
        //saving array values
        if (empty($args)) {
            $this->getResultIfArgsEmptyForBeforeSave($result);
        } else {
            //saving string value
            $field = $args[0];

            //compress the text body when is a dotmailer template
            if ($field == 'template_text' && ! $this->isStringCompressed($result) &&
                $this->transactionalHelper->isDotmailerTemplate($this->templateCode)
            ) {
                $result = $this->compressString($result);
            }
            if ($field == 'template_id') {
                $this->saveTemplateIdInRegistry($result);
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
        //save template id for email sending to update the sender name and sender email saved on template level.
        if (isset($result['template_id'])) {
            $result = $this->saveTemplateIdInRegistry($result['template_id']);
        }
        if (isset($result['template_text'])) {
            $templateText = $result['template_text'];
            //compress text
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
    private function getProcessedTemplatePreviewAndOther($result, $args)
    {
        if (empty($args)) {
            $result = $this->getResultIfArgsEmptyForPreviewAndOther($result);
        } else {
            if (isset($args[0])) {
                $field = $args[0];
                //check for correct field
                if ($field == 'template_text' && $this->isStringCompressed($result)) {
                    $result = $this->decompressString($result);
                }
                if ($field == 'template_id') {
                    $this->saveTemplateIdInRegistry($result);
                }
            }
        }

        return $result;
    }

    /**
     * @param array $result
     *
     * @return mixed
     */
    private function getResultIfArgsEmptyForPreviewAndOther($result)
    {
        if (isset($result['template_id'])) {
            $this->saveTemplateIdInRegistry($result['template_id']);
        }
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
     * @param array $result
     */
    public function afterBeforeSave(\Magento\Email\Model\AbstractTemplate $subject, $result)
    {
        //dotmailer key for saving compressed data
        if (! $this->registry->registry('dotmailer_saving_data')) {
            $this->registry->register('dotmailer_saving_data', 'saving');
        }
    }

    /**
     * @param string $string
     * @return bool
     */
    private function isStringCompressed($string)
    {
        //check if the data is compressed
        if (substr($string, 0, 1) == 'e' && substr_count($string, ' ') == 0) {
            return true;
        }

        return false;
    }

    /**
     * @param string $templateText
     * @return string
     */
    private function compressString($templateText)
    {
        return base64_encode(gzcompress($templateText, 9));
    }

    /**
     * @param string $templateText
     * @return string
     */
    private function decompressString($templateText)
    {
        return gzuncompress(base64_decode($templateText));
    }

    /**
     * Template id register for email sending.
     * @param int $templateId
     */
    private function saveTemplateIdInRegistry($templateId)
    {
        if (! $this->registry->registry('dotmailer_current_template_id')) {
             $this->registry->register('dotmailer_current_template_id', $templateId);
        }
    }
}
