<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\Mail\Template\TransportBuilder;

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
     * TransportBuilderPlugin constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
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
}
