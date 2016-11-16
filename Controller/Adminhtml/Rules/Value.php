<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Value extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\App\Response\Http
     */
    public $http;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    public $ruleValue;
    /**
     * @var \Magento\Framework\Json\Encoder
     */
    public $jsonEncoder;

    /**
     * Value constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue
     * @param \Magento\Framework\Json\Encoder                           $jsonEncoder
     * @param \Magento\Backend\App\Action\Context                       $context
     * @param \Magento\Framework\App\Response\Http                      $http
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Json\Encoder $jsonEncoder,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http $http
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->ruleValue = $ruleValue;
        $this->http = $http;
        parent::__construct($context);
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::exclusion_rules');
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $response = [];
        $valueName = $this->getRequest()->getParam('value');
        $conditionValue = $this->getRequest()->getParam('condValue');
        $attributeValue = $this->getRequest()->getParam('attributeValue');

        if ($valueName && $attributeValue && $conditionValue) {
            if ($conditionValue == 'null') {
                $valueOptions = $this->ruleValue->getValueSelectOptions($attributeValue, true);
                $response['cvalue'] = $this->getOptionHtml('cvalue', $valueName, $valueOptions);
            } else {
                $elmType = $this->ruleValue->getValueElementType($attributeValue);
                if ($elmType == 'select') {
                    $valueOptions = $this->ruleValue->getValueSelectOptions($attributeValue);
                    $response['cvalue'] = $this->getOptionHtml('cvalue', $valueName, $valueOptions);
                } elseif ($elmType == 'text') {
                    $html = "<input style='width:160px' title='cvalue' class='' id='' name=$valueName />";
                    $response['cvalue'] = $html;
                }
            }
            $this->http->getHeaders()->clearHeaders();
            $this->http->setHeader('Content-Type', 'application/json')->setBody(
                $this->jsonEncoder->encode($response)
            );
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
        $block = $this->_view->getLayout()->createBlock('Magento\Framework\View\Element\Html\Select');
        $block->setOptions($options)
            ->setId('')
            ->setClass('')
            ->setTitle($title)
            ->setName($name)
            ->setExtraParams('style="width:160px"');

        return $block->toHtml();
    }
}
