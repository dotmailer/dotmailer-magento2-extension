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
     * @var bool
     */
    private $isSaving = false;

    /**
     * @var bool
     */
    private $isLoading = false;

    /**
     * TemplatePlugin constructor.
     * @param Transactional $transactionalHelper
     */
    public function __construct(
        Transactional $transactionalHelper
    ) {
        $this->transactionalHelper = $transactionalHelper;
    }

    //@codingStandardsIgnoreStart
    /**
     * @param \Magento\Email\Model\Template $subject
     * @param $method
     * @param $args
     * @return array|void
     */
    public function before__call(\Magento\Email\Model\Template $subject, $method, $args)
    {
        if ($method == 'setTemplateText') {
            if (isset($args[0])) {
                if ($this->transactionalHelper->isDotmailerTemplate($subject['template_code'])) {
                    $args[0] = $this->compressString($args[0]);
                }
                return [$method, $args];
            }
        }
    }

    /**
     * @param \Magento\Email\Model\Template $subject
     * @param $result
     * @param $method
     * @return string
     */
    public function after__call(\Magento\Email\Model\Template $subject, $result, $method)
    {
        if ($method == 'getTemplateText') {
            if ($this->transactionalHelper->isDotmailerTemplate($subject['template_code'])) {
                return $this->decompressString($result);
            }
        }
        //leave everything unchanged
        return $result;
    }
    //@codingStandardsIgnoreEnd

    /**
     * @param \Magento\Email\Model\Template $subject
     * @param $result
     * @param mixed ...$args
     * @return mixed
     */
    public function afterGetData(\Magento\Email\Model\Template $subject, $result, ...$args)
    {
        if (empty($args)) {
            if ($this->transactionalHelper->isDotmailerTemplate($subject['template_code']) && $this->isSaving) {
                $result["template_text"] = $this->compressString($result["template_text"]);
            }
            if ($this->transactionalHelper->isDotmailerTemplate($subject['template_code']) && !$this->isLoading) {
                $result["template_text"] = $this->decompressString($result["template_text"]);
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
     * @param \Magento\Email\Model\AbstractTemplate $subject
     */
    public function afterBeforeLoad(\Magento\Email\Model\AbstractTemplate $subject)
    {
        $this->isLoading = true;
    }

    /**
     * @param \Magento\Email\Model\AbstractTemplate $subject
     */
    public function afterAfterLoad(\Magento\Email\Model\AbstractTemplate $subject)
    {
        $this->isLoading = false;
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
