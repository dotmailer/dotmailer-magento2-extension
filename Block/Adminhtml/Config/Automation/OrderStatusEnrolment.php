<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation;

use Dotdigitalgroup\Email\Block\Adminhtml\AbstractCustomSelectTable;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory;
use \Dotdigitalgroup\Email\Model\Config\Source\Automation\ProgramFactory;

class OrderStatusEnrolment extends AbstractCustomSelectTable
{
    /**
     * @var string
     */
    protected $buttonLabel = 'Add New Enrolment';

    /**
     * @var ProgramFactory
     */
    private $programFactory;

    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param ProgramFactory $programFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        ProgramFactory $programFactory,
        array $data = []
    ) {
        $this->programFactory = $programFactory;
        parent::__construct($context, $elementFactory, $data);
    }

    /**
     * @return array
     */
    protected function columnLayout(): array
    {
        return [
            'status' => [
                'label' => 'Order Status',
                'options' => $this->getElement()->getValues(),
            ],
            'automation' => [
                'label' => 'Automation Program',
                'options' => $this->programFactory->create()->toOptionArray(),
            ],
        ];
    }
}
