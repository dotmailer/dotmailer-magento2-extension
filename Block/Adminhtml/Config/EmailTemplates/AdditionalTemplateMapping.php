<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\EmailTemplates;

use Dotdigitalgroup\Email\Block\Adminhtml\AbstractCustomSelectTable;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory;
use Dotdigitalgroup\Email\Model\Config\Source\Carts\CampaignsFactory;

class AdditionalTemplateMapping extends AbstractCustomSelectTable
{
    /**
     * @var string
     */
    protected $buttonLabel = 'Sync New Campaign';

    /**
     * @var CampaignsFactory
     */
    private $campaignsFactory;

    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param CampaignsFactory $campaignsFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        CampaignsFactory $campaignsFactory,
        array $data = []
    ) {
        $this->campaignsFactory = $campaignsFactory;
        parent::__construct($context, $elementFactory, $data);
    }

    /**
     * @return array
     */
    protected function columnLayout(): array
    {
        return [
            'campaign' => [
                'label' => 'Campaign',
                'style' => 'width: 240px',
                'options' => $this->campaignsFactory->create()->toOptionArray(),
            ],
        ];
    }
}
