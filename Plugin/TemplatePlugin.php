<?php

namespace Dotdigitalgroup\Email\Plugin;

class TemplatePlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * TemplatePlugin constructor.
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Email\Model\Template $subject
     * @param $result
     * @param array ...$args
     * @return mixed
     */
    public function afterGetData(\Magento\Email\Model\Template $subject, $result, ...$args)
    {
        //get data before saving
        if ($this->registry->registry('dotmailer_saving_data')) {
            //saving array values
            if (empty($args)) {
                $templateText = $result['template_text'];
                //compress text
                if (! $this->isStringCompressed($templateText)) {
                    $result['template_text'] = $this->compresString($templateText);
                }
            } else {
                //saving string value
                $templateText = $result;
                $field = $args[0];
                //check for correct field
                if ($field == 'template_text' && ! $this->isStringCompressed($templateText)) {
                    $result = $this->compresString($templateText);
                }
            }
        } else {
            //preview/other/load
            if (empty($args)) {
                $templateText = $result['template_text'];
                if ( $this->isStringCompressed($templateText)) {
                    $result['template_text'] = $this->decompresString($templateText);
                }
            } else {
                $field = $args[0];
                //check for correct field
                if ($field == 'template_text' && $this->isStringCompressed($result)) {
                    $result = $this->decompresString($result);
                }
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Email\Model\AbstractTemplate $subject
     * @param $result
     */
    public function afterBeforeSave(\Magento\Email\Model\AbstractTemplate $subject, $result)
    {
        //dotmailer key for saving compressed data
        if (! $this->registry->registry('dotmailer_saving_data')) {
            $this->registry->register('dotmailer_saving_data', 'saving');
        }
    }


    /**
     * @param $string
     * @return bool
     */
    private function isStringCompressed($string)
    {
        //check if the data is compressed
        if (substr_count($string, '%9') > 20)
            return true;
        return false;
    }

    /**
     * @param $templateText
     * @return string
     */
    private function compresString($templateText): string
    {
        return urlencode(gzcompress($templateText));
    }

    /**
     * @param $templateText
     * @return string
     */
    private function decompresString($templateText): string
    {
        return gzuncompress(urldecode($templateText));
    }
}

