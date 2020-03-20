<?php

namespace Dotdigitalgroup\Email\Model\Email;

class TemplateService
{
    /**
     * @var string
     */
    private $templateId;

    /**
     * @param $templateId
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
    }

    /**
     * @return string|null
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }
}
