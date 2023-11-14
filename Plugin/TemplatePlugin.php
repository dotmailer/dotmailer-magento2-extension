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
     *
     * @param Transactional $transactionalHelper
     */
    public function __construct(
        Transactional $transactionalHelper
    ) {
        $this->transactionalHelper = $transactionalHelper;
    }

    /**
     * Before call.
     *
     * @param \Magento\Email\Model\Template $subject
     * @param string $method
     * @param array $args
     * @return array|void
     */
    //@codingStandardsIgnoreLine
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
     * After call.
     *
     * @param \Magento\Email\Model\Template $subject
     * @param mixed $result
     * @param string $method
     * @return string
     */
    //@codingStandardsIgnoreLine
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

    /**
     * After get data.
     *
     * @param \Magento\Email\Model\Template $subject
     * @param mixed $result
     * @param mixed $args
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
     * After before save.
     *
     * @param \Magento\Email\Model\AbstractTemplate $subject
     */
    public function afterBeforeSave(\Magento\Email\Model\AbstractTemplate $subject)
    {
        $this->isSaving = true;
    }

    /**
     * After before load.
     *
     * @param \Magento\Email\Model\AbstractTemplate $subject
     */
    public function afterBeforeLoad(\Magento\Email\Model\AbstractTemplate $subject)
    {
        $this->isLoading = true;
    }

    /**
     * After after load.
     *
     * @param \Magento\Email\Model\AbstractTemplate $subject
     */
    public function afterAfterLoad(\Magento\Email\Model\AbstractTemplate $subject)
    {
        $this->isLoading = false;
    }

    /**
     * Compress string.
     *
     * @param string $templateText
     * @return string
     */
    private function compressString($templateText)
    {
        //@codingStandardsIgnoreLine
        return base64_encode(gzcompress($templateText, 9));
    }

    /**
     * Decompress string.
     *
     * @param string $templateText
     * @return string
     */
    private function decompressString($templateText)
    {
        //@codingStandardsIgnoreLine
        return gzuncompress(base64_decode($templateText));
    }
}
