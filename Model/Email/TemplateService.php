<?php

namespace Dotdigitalgroup\Email\Model\Email;

class TemplateService
{
    /**
     * @var string
     */
    private $templateId;

    /**
     * Set template id.
     *
     * @param string $templateId
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
    }

    /**
     * Get template id.
     *
     * @return string|null
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }
}
