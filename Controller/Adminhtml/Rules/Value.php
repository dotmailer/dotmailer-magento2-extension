<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Value extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $_http;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    protected $ruleValue;
    /**
     * @var \Magento\Framework\Json\Encoder
     */
    protected $jsonEncoder;

    /**
     * Value constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http $http
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Json\Encoder $jsonEncoder,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http $http
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->ruleValue = $ruleValue;
        parent::__construct($context);
        $this->_http = $http;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::exclusion_rules');
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $valueName = $this->getRequest()->getParam('value');
        $conditionValue = $this->getRequest()->getParam('condValue');
        $attributeValue = $this->getRequest()->getParam('attributeValue');

        if ($valueName && $attributeValue && $conditionValue) {
            if ($conditionValue == 'null') {
                $valueOptions = $this->ruleValue->getValueSelectOptions($attributeValue, true);
                $response['cvalue'] = $this->_getOptionHtml('cvalue', $valueName, $valueOptions);
            } else {
                $elmType = $this->ruleValue->getValueElementType($attributeValue);
                if ($elmType == 'select') {
                    $valueOptions = $this->ruleValue->getValueSelectOptions($attributeValue);
                    $response['cvalue'] = $this->_getOptionHtml('cvalue', $valueName, $valueOptions);
                } elseif ($elmType == 'text') {
                    $html = "<input style='width:160px' title='cvalue' class='' id='' name=$valueName />";
                    $response['cvalue'] = $html;
                }
            }
            $this->_http->getHeaders()->clearHeaders();
            $this->_http->setHeader('Content-Type', 'application/json')->setBody(
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
    protected function _getOptionHtml($title, $name, $options)
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
