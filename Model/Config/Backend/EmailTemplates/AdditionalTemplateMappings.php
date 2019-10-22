<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend\EmailTemplates;

use Dotdigitalgroup\Email\Model\Config\Backend\ArraySerialized;

class AdditionalTemplateMappings extends ArraySerialized
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Email\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @param \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Dotdigitalgroup\Email\Model\Config\Json|null $serializer
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer = null,
        array $data = []
    ) {
        $this->templateFactory = $templateFactory;
        $this->helper = $helper;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data, $serializer);
    }

    /**
     * @return \Dotdigitalgroup\Email\Model\Config\Backend\Serialized
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        if (! $this->isValueChanged()) {
            return parent::beforeSave();
        }

        $dotTemplate = $this->templateFactory->create();

        $values = $this->getValue();
        if (empty(array_filter($values))) {
            return parent::beforeSave();
        }

        // get campaign IDs
        $templateCampaignIds = array_unique(array_column($values, 'campaign'));


        $templateConfigId = $this->getField();
        $scope = $this->getScope();
        $scopeId = $this->getScopeId();

        //email template mapped
        foreach ($templateCampaignIds as $templateCampaignId) {
            $dotTemplate->saveTemplateWithConfigPath(
                $templateConfigId,
                $templateCampaignId,
                $scope,
                $scopeId
            );
        }

        return parent::beforeSave();
    }
}
