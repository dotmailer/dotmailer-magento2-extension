<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\Mail\Template\TransportBuilder;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
use Dotdigitalgroup\Email\Model\Email\TemplateService;

class TransportBuilderPlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var TemplateService
     */
    private $templateService;

    /**
     * TransportBuilderPlugin constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param TemplateFactory $templateFactory
     * @param TemplateService $templateService
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        TemplateFactory $templateFactory,
        TemplateService $templateService
    ) {
        $this->registry = $registry;
        $this->templateFactory = $templateFactory;
        $this->templateService = $templateService;
    }

    /**
     * @param TransportBuilder $transportBuilder
     * @param array $templateOptions
     *
     * @return null
     */
    public function beforeSetTemplateOptions(TransportBuilder $transportBuilder, $templateOptions)
    {
        //If registry already exist for key then un-register first before registering
        if ($this->registry->registry('transportBuilderPluginStoreId') !== null) {
            $this->registry->unregister('transportBuilderPluginStoreId');
        }

        $this->registry->register('transportBuilderPluginStoreId', $templateOptions['store']);
        return null;
    }

    /**
     * @param TransportBuilder $transportBuilder
     * @param $templateIdentifier
     *
     * @return array
     */
    public function beforeSetTemplateIdentifier(TransportBuilder $transportBuilder, $templateIdentifier)
    {
        $this->templateService->setTemplateId($templateIdentifier);
        return [$templateIdentifier];
    }
}
