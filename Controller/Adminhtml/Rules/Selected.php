<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Selected extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    private $http;

    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    private $rulesFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type
     */
    private $ruleType;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition
     */
    private $ruleCondition;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    private $ruleValue;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    public $jsonEncoder;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * Selected constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules              $rulesResource
     * @param \Magento\Backend\App\Action\Context                           $context
     * @param \Dotdigitalgroup\Email\Model\RulesFactory                     $rulesFactory
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type      $ruleType
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value     $ruleValue
     * @param \Magento\Framework\Json\Helper\Data                           $jsonEncoder
     * @param \Magento\Framework\App\Response\Http                          $http
     * @param \Magento\Framework\Escaper                                    $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $ruleType,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Json\Helper\Data $jsonEncoder,
        \Magento\Framework\App\Response\Http $http,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->rulesFactory = $rulesFactory;
        $this->ruleType = $ruleType;
        $this->ruleCondition = $ruleCondition;
        $this->ruleValue = $ruleValue;
        $this->jsonEncoder = $jsonEncoder;
        $this->escaper = $escaper;
        $this->rulesResource = $rulesResource;

        parent::__construct($context);
        $this->http = $http;
    }

    /**
     * Execute method.
     *
     * @return \Magento\Framework\App\Response\HttpInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('ruleid');
        $attribute = $this->getRequest()->getParam('attribute');
        $arrayKey = $this->getRequest()->getParam('arraykey');
        $conditionName = $this->getRequest()->getParam('condition');
        $valueName = $this->getRequest()->getParam('value');

        if ($arrayKey && $id && $attribute && $conditionName && $valueName) {
            $rule = $this->rulesFactory->create();
            $this->rulesResource->load($rule, $id);
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
                    $this->escaper->escapeHtml($conditionName),
                    $conditionOptions
                )
            );

            $elmType = $this->ruleValue->getValueElementType($attribute);

            $this->evaluateElmType($elmType, $selectedConditions, $attribute, $selectedValues, $this->escaper->escapeHtml($valueName), $response);

            $this->http->getHeaders()->clearHeaders();
            $this->http->setHeader('Content-Type', 'application/json')
                ->setBody($this->jsonEncoder->jsonEncode($response));
        }
    }

    /**
     * @param string $elmType
     * @param string $selectedConditions
     * @param string $attribute
     * @param string $selectedValues
     * @param string $valueName
     * @param string $response
     *
     * @return null
     */
    private function evaluateElmType($elmType, $selectedConditions, $attribute, $selectedValues, $valueName, &$response)
    {
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
            $encodedValue = $this->escaper->escapeHtml($selectedValues);
            $html = "<input style='width:160px' title='cvalue' name='$valueName' value='$encodedValue'/>";
            $response['cvalue'] = $html;
        }
    }

    /**
     * @param string $title
     * @param string $name
     * @param array $options
     *
     * @return string
     */
    private function getOptionHtml($title, $name, $options)
    {
        $block = $this->_view->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
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
