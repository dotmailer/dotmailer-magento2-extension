<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type;
use Dotdigitalgroup\Email\Model\ExclusionRule\RuleValidator;

/**
 * Class Value
 * If a user selects a different condition for an exclusion rule condition,
 * the value field is dynamically updated.
 */
class Value extends \Magento\Backend\App\AbstractAction
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
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    private $ruleValue;

    /**
     * @var Type
     */
    private $ruleType;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonEncoder;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var RuleValidator
     */
    private $ruleValidator;

    /**
     * Value constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue
     * @param Type $ruleType
     * @param \Magento\Framework\Json\Helper\Data $jsonEncoder
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http $http
     * @param \Magento\Framework\Escaper $escaper
     * @param RuleValidator $ruleValidator
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        Type $ruleType,
        \Magento\Framework\Json\Helper\Data $jsonEncoder,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http $http,
        \Magento\Framework\Escaper $escaper,
        RuleValidator $ruleValidator
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->ruleValue = $ruleValue;
        $this->ruleType = $ruleType;
        $this->http = $http;
        $this->escaper = $escaper;
        $this->ruleValidator = $ruleValidator;
        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return null
     */
    public function execute()
    {
        $response = [];
        $valueName = $this->getRequest()->getParam('value');
        $conditionValue = $this->getRequest()->getParam('condValue');
        $attributeValue = $this->getRequest()->getParam('attributeValue');

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
            $this->http->getHeaders()->clearHeaders();
            $this->http->setHeader('Content-Type', 'application/json')->setBody(
                $this->jsonEncoder->jsonEncode($response)
            );
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
        $block = $this->_view->getLayout()->createBlock(\Magento\Framework\View\Element\Html\Select::class);
        $block->setOptions($options)
            ->setId('')
            ->setClass('')
            ->setTitle($title)
            ->setName($name)
            ->setExtraParams('style="width:160px"');

        return $block->toHtml();
    }
}
