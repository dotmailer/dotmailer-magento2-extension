<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

class EmailTemplates implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Email\Model\Template
     */
    public $templateFactory;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    public $config;

    /**
     * @var int
     */
    private $websiteId = 0;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template
     */
    public $templateResource;

    /**
     * @var int
     */
    private $storeId = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    private $context;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * UpdateTemplates constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Email\Model\ResourceModel\Template $templateResource
     * @param \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Email\Model\ResourceModel\Template $templateResource,
        \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory
    ) {
        $this->helper         = $data;
        $this->config           = $config;
        $this->context        = $context;
        $this->templateFactory = $templateFactory;
        $this->templateResource= $templateResource;
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->websiteId = (empty($observer->getWebsite()))? '0' : $observer->getWebsite();
        $this->storeId = (empty($observer->getStore()))? '0' : $observer->getStore();
        $groups = $this->context->getRequest()->getPost('groups');

        foreach ($groups['email_templates']['fields'] as $templateCode => $emailValue) {
            //inherit option was selected for the child config value - skip
            if (isset($groups['email_templates']['fields'][$templateCode]['inherit'])) {
                continue;
            }

            if (isset($emailValue['value'])) {
                $campaingId = $emailValue['value'];

                //new email template mapped
                if ($campaingId) {
                    $this->createNewEmailTemplate($templateCode, $campaingId);
                } else {
                    $template = $this->templateFactory->create();
                    //reset to default email template
                    $this->resetToDefaultTemplate($template->templateConfigMapping[$templateCode]);
                    $this->resetToDefaultTemplate($template->templateEmailConfigMapping[$templateCode]);
                    $template->deleteTemplateByCode(
                        \Dotdigitalgroup\Email\Model\Email\Template::$defaultEmailTemplateCode[$templateCode]
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @todo strip the unsubscribe link from body;
     *
     *
     * @param $templateCode
     * @param $campaignId
     */
    private function createNewEmailTemplate($templateCode, $campaignId)
    {
        $message = sprintf('Template created %s', $campaignId);
        $templateCodeToName = \Dotdigitalgroup\Email\Model\Email\Template::$defaultEmailTemplateCode[$templateCode];
        $emailTemplate = $this->templateFactory->create();
        $template = $emailTemplate->loadBytemplateCode($templateCodeToName);

        $templateConfigPath = $emailTemplate->templateConfigMapping[$templateCode];

        //get the template from api
        $client = $this->helper->getWebsiteApiClient($this->websiteId);
        $dmCampaign = $client->getCampaignById($campaignId);
        if (isset($dmCampaign->message)) {
            $message = 'Failed to get api template : ' . $dmCampaign->message;
            $this->messageManager->addErrorMessage($message);
            return;
        }

        $fromName = $dmCampaign->fromName;
        $fromEmail = $dmCampaign->fromAddress->email;
        $templateSubject = $dmCampaign->subject;
        $templateBody = $emailTemplate->convertContent($dmCampaign->htmlContent);

        try {
            $template->setOrigTemplateCode($templateCode)
                ->setTemplateCode($templateCodeToName)
                ->setTemplateSubject($templateSubject)
                ->setTemplateText($templateBody)
                ->setTemplateType(\Magento\Email\Model\Template::TYPE_HTML)
                ->setTemplateSenderName($fromName)
                ->setTemplateSenderEmail($fromEmail);
            $this->templateResource->save($template);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }

        //save successul created new email template with the default config value for template.
        $this->saveConfigForEmailTemplate($templateConfigPath, $template->getId());
        $this->helper->log($message);

        return;
    }

    /**
     * @param $templateConfigPath
     * @param $templateId
     */
    private function saveConfigForEmailTemplate($templateConfigPath, $templateId)
    {
        if ($this->websiteId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $this->websiteId;
        } elseif ($this->storeId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $scopeId = $this->storeId;
        } else {
            $scope = \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT;
            $scopeId = '0';
        }

        $this->helper->saveConfigData(
            $templateConfigPath,
            $templateId,
            $scope,
            $scopeId
        );

        //clean the config cache
        $this->config->reinit();
    }

    /**
     * @param $templateConfigPath
     */
    private function resetToDefaultTemplate($templateConfigPath)
    {
        if ($this->websiteId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $this->websiteId;
        } elseif ($this->storeId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $scopeId = $this->storeId;
        } else {
            $scope = \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT;
            $scopeId = '0';
        }

        $this->helper->deleteConfigData(
            $templateConfigPath,
            $scope,
            $scopeId
        );
    }

}

