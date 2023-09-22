<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend\EmailTemplates;

use Dotdigitalgroup\Email\Model\Config\Backend\ArraySerialized;
use Dotdigitalgroup\Email\Model\Config\Backend\Serialized;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class AdditionalTemplateMappings extends ArraySerialized
{
    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @param TemplateFactory $templateFactory
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SerializerInterface $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        TemplateFactory $templateFactory,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->templateFactory = $templateFactory;

        parent::__construct(
            $serializer,
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Before save.
     *
     * @return Serialized
     * @throws LocalizedException
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
