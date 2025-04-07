<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Backend;

use Dotdigitalgroup\Email\Model\Email\Template as DotdigitalTemplateModel;

class EmailTemplateFieldValue extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Email\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template
     */
    private $templateResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * EmailTemplateFieldValue constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Email\Model\ResourceModel\Template $templateResource
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Email\Model\ResourceModel\Template $templateResource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->templateFactory = $templateFactory;
        $this->storeManager = $storeManager;
        $this->templateResource = $templateResource;
        $this->helper = $helper;
        $this->request = $request;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Before delete.
     *
     * @return \Magento\Framework\App\Config\Value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeDelete()
    {
        $dotTemplate = $this->templateFactory->create();
        $this->helper->deleteConfigData(
            $dotTemplate->templateConfigMapping[$this->getField()],
            $this->getScope(),
            $this->getScopeId()
        );
        return parent::beforeDelete();
    }

    /**
     * Before save.
     *
     * @return \Magento\Framework\App\Config\Value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        if (!$this->isValueChanged()) {
            return parent::beforeSave();
        }
        $dotTemplate = $this->templateFactory->create();
        $templateConfigId = $this->getField();
        $scope = $this->getScope();
        $scopeId = $this->getScopeId();
        //email template mapped
        if ($this->getValue()) {
            $templateConfigPath = $dotTemplate->templateConfigMapping[$templateConfigId];
            $templateId = $dotTemplate->saveTemplateWithConfigPath(
                $templateConfigId,
                $this->getValue(),
                $scope,
                $scopeId
            );
            //save successful created new email template with the default config value for template.
            if ($templateId) {
                $this->helper->saveConfigData(
                    $templateConfigPath,
                    $templateId,
                    $scope,
                    $scopeId
                );
            }
        } else {
            if (!$this->isFirstTimeMapped($dotTemplate, $templateConfigId, $scope, $scopeId) && $this->getOldValue()) {
                //remove the config for core email template
                $this->helper->deleteConfigData(
                    $dotTemplate->templateConfigMapping[$templateConfigId],
                    $scope,
                    $scopeId
                );
                //remove the config for dotmailer template
                $this->helper->deleteConfigData(
                    $dotTemplate->templateConfigIdToDotmailerConfigPath[$templateConfigId],
                    $scope,
                    $scopeId
                );
            }
        }
        return parent::beforeSave();
    }

    /**
     * Check if this is the first time a template has been mapped.
     *
     * @param DotdigitalTemplateModel $dotTemplate
     * @param string $templateConfigId
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    private function isFirstTimeMapped($dotTemplate, $templateConfigId, $scope, $scopeId)
    {
        $configValue =  $this->helper->getConfigValue(
            $dotTemplate->templateConfigMapping[$templateConfigId],
            $scope,
            $scopeId
        );

        if (is_numeric($configValue) && !empty($configValue)) {
            return true;
        }

        return false;
    }
}
