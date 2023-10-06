<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition;
use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type;
use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value;
use Dotdigitalgroup\Email\Model\ExclusionRule\RuleValidator;
use Dotdigitalgroup\Email\Model\ResourceModel\Rules;
use Dotdigitalgroup\Email\Model\RulesFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Html\Select;

/**
 * Class Select
 * If an exclusion rule has saved condition data, this is injected after the main block has rendered.
 * This controller handles an AJAX call to update the default block values.
 */
class Selected extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

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
     * @var RuleValidator
     */
    private $ruleValidator;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Selected constructor.
     *
     * @param Rules $rulesResource
     * @param Context $context
     * @param RulesFactory $rulesFactory
     * @param Type $ruleType
     * @param Condition $ruleCondition
     * @param Value $ruleValue
     * @param Escaper $escaper
     * @param RuleValidator $ruleValidator
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $ruleType,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Escaper $escaper,
        RuleValidator $ruleValidator,
        JsonFactory $resultJsonFactory
    ) {
        $this->rulesFactory = $rulesFactory;
        $this->ruleType = $ruleType;
        $this->ruleCondition = $ruleCondition;
        $this->ruleValue = $ruleValue;
        $this->escaper = $escaper;
        $this->rulesResource = $rulesResource;
        $this->ruleValidator = $ruleValidator;
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return Json
     */
    public function execute()
    {
        $id = $this->request->getParam('ruleid');
        $attribute = $this->request->getParam('attribute');
        $arrayKey = $this->request->getParam('arraykey');
        $conditionName = $this->request->getParam('condition');
        $valueName = $this->request->getParam('value');
        $response = [];

        if ($arrayKey && $id && $attribute && $conditionName && $valueName) {
            $rule = $this->rulesFactory->create();
            $this->rulesResource->load($rule, $id);
            //rule not found
            if (!$rule->getId()) {
                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData('Rule not found!');
                return $resultJson;
            }
            $conditions = $rule->getCondition();
            $condition = $conditions[$arrayKey];
            $selectedConditions = $condition['conditions'];
            $selectedValues = $condition['cvalue'];
            $inputType = $this->ruleType->getInputType($attribute);
            $conditionOptions = $this->ruleCondition->getInputTypeOptions($inputType);

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

            $this->setValueHtml(
                $elmType,
                $inputType,
                $selectedConditions,
                $attribute,
                $selectedValues,
                $this->escaper->escapeHtml($valueName),
                $response
            );
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }

    /**
     * Set value HTML.
     *
     * @param string $elmType
     * @param string $inputType
     * @param string $selectedConditions
     * @param string $attribute
     * @param string $selectedValues
     * @param string $valueName
     * @param array $response
     *
     * @return void
     */
    private function setValueHtml(
        $elmType,
        $inputType,
        $selectedConditions,
        $attribute,
        $selectedValues,
        $valueName,
        &$response
    ) {
        if ($elmType == 'select' || $selectedConditions == 'null') {
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
            $validationType = $this->ruleValidator->setFrontEndValidation(
                $inputType,
                $selectedConditions
            );
            $html = "<input style='width:160px' title='cvalue' name='$valueName' value='$encodedValue' ";
            if ($validationType) {
                $html .= "data-validate=$validationType";
            }
            $html .= " />";
            $response['cvalue'] = $html;
        }
    }

    /**
     * Set option HTML.
     *
     * @param string $title
     * @param string $name
     * @param array $options
     *
     * @return string
     */
    private function getOptionHtml($title, $name, $options)
    {
        $block = $this->_view->getLayout()->createBlock(Select::class);
        /** @var Select $block */
        $block->setOptions($options)
            ->setId('')
            ->setClass('')
            ->setTitle($title)
            ->setName($name)
            ->setExtraParams('style="width:160px"');

        return $block->toHtml();
    }
}
