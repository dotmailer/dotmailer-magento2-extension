<?php

namespace Dotdigitalgroup\Email\Model\Email;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Email\Model\Template\SenderResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Dotdigitalgroup\Email\Model\Email\Template;

/**
 * Class SenderResolver
 *
 * Set the message from name and email in transactional sends, using data set in email_template.
 */
class DotdigitalSenderResolver extends SenderResolver
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Template
     */
    private $templateModel;

    /**
     * @var Transactional
     */
    private $transactionalHelper;

    /**
     * SenderResolver constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Registry $registry
     * @param Template $templateModel
     * @param Transactional $transactionalHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Registry $registry,
        Template $templateModel,
        Transactional $transactionalHelper
    ) {
        $this->registry = $registry;
        $this->templateModel = $templateModel;
        $this->transactionalHelper = $transactionalHelper;
        parent::__construct(
            $scopeConfig
        );
    }

    /**
     *
     * @param string|array $sender
     * @param int|null $scopeId
     *
     * @return array
     * @throws \Magento\Framework\Exception\MailException
     */
    public function resolve($sender, $scopeId = null)
    {
        $templateId = $this->templateModel->loadTemplateIdFromRegistry();

        if ($templateId && $this->shouldIntercept()) {
            $template = $this->templateModel->loadTemplate($templateId);
            if ($this->isDotmailerTemplateCode($template->getTemplateCode())) {
                return [
                    'email' => $template->getTemplateSenderEmail(),
                    'name' => $template->getTemplateSenderName()
                ];
            }
        }

        return parent::resolve($sender, $scopeId);
    }

    /**
     *
     * @return bool
     */
    private function shouldIntercept()
    {
        $storeId = $this->registry->registry('transportBuilderPluginStoreId');
        return $this->transactionalHelper->isEnabled($storeId);
    }

    /**
     *
     * @param string $templateCode
     *
     * @return bool
     */
    private function isDotmailerTemplateCode($templateCode)
    {
        return $this->transactionalHelper->isDotmailerTemplate($templateCode);
    }
}
