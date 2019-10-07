<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\Mail\Template\TransportBuilder;
use Dotdigitalgroup\Email\Model\Email\Template;

/**
 * Class TransportBuilderPlugin
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TransportBuilderPlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var Template
     */
    private $templateModel;

    /**
     * TransportBuilderPlugin constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param Template $templateModel
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        Template $templateModel
    ) {
        $this->registry = $registry;
        $this->templateModel = $templateModel;
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
        $this->templateModel->saveTemplateIdInRegistry($templateIdentifier);
        return [$templateIdentifier];
    }
}
