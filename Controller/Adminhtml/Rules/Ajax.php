<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action\Context;

class Ajax extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\App\Response\Http
     */
    public $http;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type
     */
    public $ruleType;
    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition
     */
    public $ruleCondition;
    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    public $ruleValue;

    /**
     * @var \Magento\Framework\Json\Encoder
     */
    public $jsonEncoder;

    /**
     * Ajax constructor.
     *
     * @param Context                                                       $context
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type      $ruleType
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value     $ruleValue
     * @param \Magento\Framework\Json\Encoder                               $jsonEncoder
     * @param \Magento\Framework\App\Response\Http                          $http
     */
    public function __construct(
        Context $context,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $ruleType,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Json\Encoder $jsonEncoder,
        \Magento\Framework\App\Response\Http $http
    ) {
        $this->ruleType = $ruleType;
        $this->ruleCondition = $ruleCondition;
        $this->ruleValue = $ruleValue;
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context);
        $this->http = $http;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'Dotdigitalgroup_Email::exclusion_rules'
        );
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $attribute = $this->getRequest()->getParam('attribute');
        $conditionName = $this->getRequest()->getParam('condition');
        $valueName = $this->getRequest()->getParam('value');
        if ($attribute && $conditionName && $valueName) {
            $type = $this->ruleType->getInputType($attribute);
            $conditionOptions = $this->ruleCondition->getInputTypeOptions($type);
            $response['condition'] = $this->_getOptionHtml(
                'conditions',
                $conditionName,
                $conditionOptions
            );

            $elmType = $this->ruleValue->getValueElementType($attribute);
            if ($elmType == 'select') {
                $valueOptions = $this->ruleValue->getValueSelectOptions($attribute);
                $response['cvalue'] = $this->_getOptionHtml(
                    'cvalue',
                    $valueName,
                    $valueOptions
                );
            } elseif ($elmType == 'text') {
                $html = "<input style='width:160px' title='cvalue' class='' id='' name=$valueName />";
                $response['cvalue'] = $html;
            }
            $this->http->getHeaders()->clearHeaders();
            $this->http->setHeader('Content-Type', 'application/json')
                ->setBody(
                    $this->jsonEncoder->encode($response)
                );
        }
    }

    /**
     * Get select options.
     *
     * @param $title
     * @param $name
     * @param $options
     *
     * @return string
     */
    public function _getOptionHtml($title, $name, $options)
    {
        $block = $this->_view->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        );
        $block->setOptions($options)
            ->setId('')
            ->setClass('')
            ->setTitle($title)
            ->setName($name)
            ->setExtraParams('style="width:160px"');

        return $block->toHtml();
    }
}
