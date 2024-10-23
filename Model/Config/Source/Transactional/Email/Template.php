<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Transactional\Email;

use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Email\Model\Template\Config;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;

class Template extends DataObject implements OptionSourceInterface
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var Config
     */
    private $emailConfig;

    /**
     * @var CollectionFactory
     */
    private $templateCollectionFactory;

    /**
     * @param Registry $coreRegistry
     * @param CollectionFactory $templateCollectionFactory
     * @param Config $emailConfig
     * @param array $data
     */
    public function __construct(
        Registry $coreRegistry,
        CollectionFactory $templateCollectionFactory,
        Config $emailConfig,
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
