<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type;
use Dotdigitalgroup\Email\Model\ExclusionRule\RuleValidator;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Element\Html\Select;

/**
 * Class Value
 * If a user selects a different condition for an exclusion rule condition,
 * the value field is dynamically updated.
 */
class Value extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    private $ruleValue;

    /**
     * @var Type
     */
    private $ruleType;

    /**
     * @var RuleValidator
     */
    private $ruleValidator;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Value constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue
     * @param Type $ruleType
     * @param \Magento\Backend\App\Action\Context $context
     * @param RuleValidator $ruleValidator
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        Type $ruleType,
        \Magento\Backend\App\Action\Context $context,
        RuleValidator $ruleValidator,
        JsonFactory $resultJsonFactory
    ) {
        $this->ruleValue = $ruleValue;
        $this->ruleType = $ruleType;
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
        $response = [];
        $valueName = $this->getRequest()->getParam('value');
        $conditionValue = $this->getRequest()->getParam('condValue');
        $attributeValue = $this->getRequest()->getParam('attributeValue');
        $response = [];

        if ($valueName && $attributeValue && $conditionValue) {
            $inputType = $this->ruleType->getInputType($attributeValue);
            if ($conditionValue == 'null') {
                $valueOptions = $this->ruleValue->getValueSelectOptions($attributeValue, true);
                $response['cvalue'] = $this->getOptionHtml('cvalue', $valueName, $valueOptions);
            } else {
                $elmType = $this->ruleValue->getValueElementType($attributeValue);
                if ($elmType == 'select') {
                    $valueOptions = $this->ruleValue->getValueSelectOptions($attributeValue);
                    $response['cvalue'] = $this->getOptionHtml('cvalue', $valueName, $valueOptions);
                } elseif ($elmType == 'text') {
                    $validationType = $this->ruleValidator->setFrontEndValidation(
                        $inputType,
                        $conditionValue
                    );
                    $html = "<input style='width:160px' title='cvalue' class='' id='' name=$valueName ";
                    if ($validationType) {
                        $html .= "data-validate=$validationType";
                    }
                    $html .= " />";
                    $response['cvalue'] = $html;
                }
            }
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }

    /**
     * Get option HTML.
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
