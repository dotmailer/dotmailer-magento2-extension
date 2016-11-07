<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Selected extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\App\Response\Http
     */
    public $http;

    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    public $rulesFactory;
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
     * @var
     */
    public $jsonEncoder;

    /**
     * Selected constructor.
     *
     * @param \Magento\Backend\App\Action\Context                           $context
     * @param \Dotdigitalgroup\Email\Model\RulesFactory                     $rulesFactory
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type      $ruleType
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value     $ruleValue
     * @param \Magento\Framework\Json\Encoder                               $jsonEncoder
     * @param \Magento\Framework\App\Response\Http                          $http
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $ruleType,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Json\Encoder $jsonEncoder,
        \Magento\Framework\App\Response\Http $http
    ) {
        $this->rulesFactory = $rulesFactory;
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
     *
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('ruleid');
        $attribute = $this->getRequest()->getParam('attribute');
        $arrayKey = $this->getRequest()->getParam('arraykey');
        $conditionName = $this->getRequest()->getParam('condition');
        $valueName = $this->getRequest()->getParam('value');

        if ($arrayKey && $id && $attribute && $conditionName && $valueName) {
            $rule = $this->rulesFactory->create()
                ->load($id);
            //rule not found
            if (!$rule->getId()) {
                $this->http->getHeaders()->clearHeaders();

                return $this->http->setHeader(
                    'Content-Type',
                    'application/json'
                )->setBody('Rule not found!');
            }
            $conditions = $rule->getCondition();
            $condition = $conditions[$arrayKey];
            $selectedConditions = $condition['conditions'];
            $selectedValues = $condition['cvalue'];
            $type = $this->ruleType->getInputType($attribute);
            $conditionOptions = $this->ruleCondition->getInputTypeOptions($type);

            $response['condition'] = str_replace(
                'value="' . $selectedConditions . '"',
                'value="' . $selectedConditions . '"' . 'selected="selected"',
                $this->getOptionHtml(
                    'conditions',
                    $conditionName,
                    $conditionOptions
                )
            );

            $elmType = $this->ruleValue->getValueElementType($attribute);

            if ($elmType == 'select' or $selectedConditions == 'null') {
                $isEmpty = false;

                if ($selectedConditions == 'null') {
                    $isEmpty = true;
                }

                $valueOptions = $this->ruleValue->getValueSelectOptions($attribute, $isEmpty);

                $response['cvalue'] = str_replace(
                    'value="' . $selectedValues . '"',
                    'value="' . $selectedValues . '"' . 'selected="selected"',
                    $this->getOptionHtml('cvalue', $valueName, $valueOptions)
                );
            } elseif ($elmType == 'text') {
                $html = "<input style='width:160px' title='cvalue' name='$valueName' value='$selectedValues'/>";
                $response['cvalue'] = $html;
            }
            $this->http->getHeaders()->clearHeaders();
            $this->http->setHeader('Content-Type', 'application/json')
                ->setBody($this->jsonEncoder->encode($response));
        }
    }

    /**
     * @param $title
     * @param $name
     * @param $options
     *
     * @return string
     */
    public function getOptionHtml($title, $name, $options)
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
