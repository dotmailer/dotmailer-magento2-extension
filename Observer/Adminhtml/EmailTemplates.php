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
    private $websiteId;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template
     */
    public $templateResource;

    /**
     * @var int
     */
    private $storeId;

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
        $template = $this->templateFactory->create();
        $this->websiteId = (empty($observer->getWebsite()))? '0' : $observer->getWebsite();
        $this->storeId = (empty($observer->getStore()))? '0' : $observer->getStore();
        $groups = $this->context->getRequest()->getPost('groups');

        foreach ($groups['email_templates']['fields'] as $templateCode => $emailValue) {
            //inherit option was selected for the child config value - skip
            if (isset($groups['email_templates']['fields'][$templateCode]['inherit'])) {
                //remove the config value if the parent inherit was selected
                $this->removeConfigValue($template->templateConfigMapping[$templateCode]);
            }

            if (isset($emailValue['value'])) {
                $campaignId = $emailValue['value'];
                //email template mapped found
                if ($campaignId) {
                    $this->createOrUpdateNewEmailTemplate($templateCode, $campaignId);
                } else {
                    //remove the config for core email template
                    $this->removeConfigValue($template->templateConfigMapping[$templateCode]);
                    //remove the config for dotmailer template
                    $this->removeConfigValue($template->templateEmailConfigMapping[$templateCode]);
                    //delete the dotmailer template when it's unmapped
                    $template->deleteTemplateByCode(
                        \Dotdigitalgroup\Email\Model\Email\Template::$defaultEmailTemplateCode[
                            $templateCode
                        ] . '__' . $this->storeId
                    );
                }
            }
        }

        return $this;
    }

    /**
     *
     * @param $templateCode
     * @param $campaignId
     */
    private function createOrUpdateNewEmailTemplate($templateCode, $campaignId)
    {

        $templateCodeToName = \Dotdigitalgroup\Email\Model\Email\Template::$defaultEmailTemplateCode[$templateCode];
        $templateCodeWithStoreCode = $templateCodeToName . '__' . $this->storeId;
        $emailTemplate = $this->templateFactory->create();
        $template = $emailTemplate->loadBytemplateCode($templateCodeWithStoreCode);
        $templateConfigPath = $emailTemplate->templateConfigMapping[$templateCode];
        //get the template from api
        $client = $this->helper->getWebsiteApiClient($this->websiteId);
        $dmCampaign = $client->getCampaignByIdWithPreparedContent($campaignId);
        $message = sprintf(
            'Template %s, dm campaign id %s',
            $templateCodeWithStoreCode,
            $campaignId
        );
        if (isset($dmCampaign->message)) {
            $message = 'Failed to get api template : ' . $dmCampaign->message;
            $this->messageManager->addErrorMessage($message);
            return;
        }

        $fromName   = $dmCampaign->fromName;
        $fromEmail  = $dmCampaign->fromAddress->email;
        $templateSubject = utf8_encode($dmCampaign->subject);
        $templateBody   = $emailTemplate->convertContent($dmCampaign->preparedHtmlContent);
        try {
            $template->setOrigTemplateCode($templateCode)
                ->setTemplateCode($templateCodeWithStoreCode)
                ->setTemplateSubject($templateSubject)
                ->setTemplateText($templateBody)
                ->setTemplateType(\Magento\Email\Model\Template::TYPE_HTML)
                ->setTemplateSenderName($fromName)
                ->setTemplateSenderEmail($fromEmail);

            $this->templateResource->save($template);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }

        //save successful created new email template with the default config value for template.
        $this->saveConfigValue($templateConfigPath, $template->getId());
        $this->helper->log($message);

        return;
    }

    /**
     * @param $configPath
     * @param $configValue
     */
    private function saveConfigValue($configPath, $configValue)
    {
        if ($this->storeId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $scopeId = $this->storeId;
        } elseif ($this->websiteId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $this->websiteId;
        } else {
            $scope = \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT;
            $scopeId = '0';
        }

        $this->helper->saveConfigData(
            $configPath,
            $configValue,
            $scope,
            $scopeId
        );

        //clean the config cache
        $this->config->reinit();
    }

    /**
     * @param $templateConfigPath
     */
    private function removeConfigValue($templateConfigPath)
    {
        if ($this->storeId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $scopeId = $this->storeId;
        } elseif ($this->websiteId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $this->websiteId;
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

