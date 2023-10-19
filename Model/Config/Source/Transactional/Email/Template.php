<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Transactional\Email;

use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Email\Model\Template\Config;
use Magento\Framework\Registry;

class Template extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\Email\Model\Template\Config
     */
    private $emailConfig;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory
     */
    private $templateCollectionFactory;

    /**
     * @param Registry $coreRegistry
     * @param CollectionFactory $templateCollectionFactory
     * @param Config $emailConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory,
        \Magento\Email\Model\Template\Config $emailConfig,
        array $data = []
    ) {
        parent::__construct($data);
        $this->coreRegistry = $coreRegistry;
        $this->templateCollectionFactory = $templateCollectionFactory;
        $this->emailConfig = $emailConfig;
    }

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!($this->coreRegistry->registry('config_system_email_template'))) {
            $collection = $this->templateCollectionFactory->create();
            $collection->load();
            $this->coreRegistry->register('config_system_email_template', $collection);
        }
        return $this->emailConfig->getAvailableTemplates();
    }
}
