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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

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
     * EmailTemplates constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Email\Model\ResourceModel\Template $templateResource
     * @param \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Email\Model\ResourceModel\Template $templateResource,
        \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory
    ) {
        $this->helper         = $data;
        $this->config         = $config;
        $this->context        = $context;
        $this->storeManager   = $storeManager;
        $this->templateFactory = $templateFactory;
        $this->templateResource= $templateResource;
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $dotTemplate = $this->templateFactory->create();
        $website = $observer->getWebsite();
        $store = $observer->getStore();
        $this->websiteId = (empty($website))? '0' : $website;
        $this->storeId = (empty($store))? '0' : $store;
        $groups = $this->context->getRequest()->getPost('groups');

        foreach ($groups['email_templates']['fields'] as $templateConfigId => $campaignId) {
            //remove the config value if the parent inherit was selected and - continue
            if (isset($groups['email_templates']['fields'][$templateConfigId]['inherit'])) {
                $this->removeConfigValue($dotTemplate->templateConfigMapping[$templateConfigId]);
                continue;
            }

            if (isset($campaignId['value'])) {
                //email template mapped
                if ($campaignId = $campaignId['value']) {
                    $templateConfigPath = $dotTemplate->templateConfigMapping[$templateConfigId];
                    $template = $dotTemplate->saveTemplateWithConfigPath(
                        $templateConfigId,
                        $campaignId,
                        $store,
                        $website
                    );
                    //save successful created new email template with the default config value for template.
                    if ($template) {
                        $this->saveConfigValue($templateConfigPath, $template->getId());
                    }
                } else {
                    //remove the config for core email template
                    $this->removeConfigValue($dotTemplate->templateConfigMapping[$templateConfigId]);
                    //remove the config for dotmailer template
                    $this->removeConfigValue(
                        $dotTemplate->templateConfigIdToDotmailerConfigPath[$templateConfigId]
                    );
                }
            }
        }

        //clean only after after all configs changed
        $this->config->reinit();
    }

    /**
     * @param string $configPath
     * @param string $configValue
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
            $scope = 'default';
            $scopeId = '0';
        }

        $this->helper->saveConfigData(
            $configPath,
            $configValue,
            $scope,
            $scopeId
        );
    }

    /**
     * @param string $templateConfigPath
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
            $scope = 'default';
            $scopeId = '0';
        }

        $this->helper->deleteConfigData(
            $templateConfigPath,
            $scope,
            $scopeId
        );
    }
}
