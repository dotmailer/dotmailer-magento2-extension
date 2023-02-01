<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Dotdigitalgroup\Email\Model\ExclusionRule\RuleValidator;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Element\Html\Select;

/**
 * Class Ajax
 * If a user selects a different attribute for an exclusion rule condition,
 * the condition and value fields are dynamically updated.
 */
class Ajax extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

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
     * @var RuleValidator
     */
    private $ruleValidator;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Ajax constructor.
     *
     * @param Context $context
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $ruleType
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue
     * @param RuleValidator $ruleValidator
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $ruleType,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        RuleValidator $ruleValidator,
        JsonFactory $resultJsonFactory
    ) {
        $this->ruleType = $ruleType;
        $this->ruleCondition = $ruleCondition;
        $this->ruleValue = $ruleValue;
        $this->ruleValidator = $ruleValidator;
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
        $attribute = $this->getRequest()->getParam('attribute');
        $conditionName = $this->getRequest()->getParam('condition');
        $valueName = $this->getRequest()->getParam('value');
        $response = [];

        if ($attribute && $conditionName && $valueName) {
            $inputType = $this->ruleType->getInputType($attribute);
            $conditionOptions = $this->ruleCondition->getInputTypeOptions($inputType);
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
                $validationType = $this->ruleValidator->setFrontEndValidation(
                    $inputType,
                    $conditionName
                );
                $html = "<input title='cvalue' class='ddg-rules-conditions' id='' name=$valueName ";
                if ($validationType) {
                    $html .= "data-validate=$validationType";
                }
                $html .= " />";
                $response['cvalue'] = $html;
            }
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }

    /**
     * Get select options.
     *
     * @param string $title
     * @param string $name
     * @param array $options
     *
     * @return string
     */
    private function _getOptionHtml($title, $name, $options)
    {
        $block = $this->_view->getLayout()->createBlock(Select::class);
        /** @var Select $block */
        $block->setOptions($options)
            ->setId('')
            ->setClass('ddg-rules-conditions')
            ->setTitle($title)
            ->setName($name);

        return $block->toHtml();
    }
}
