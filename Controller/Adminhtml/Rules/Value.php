<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

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
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonEncoder;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * Value constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue
     * @param \Magento\Framework\Json\Helper\Data                       $jsonEncoder
     * @param \Magento\Backend\App\Action\Context                       $context
     * @param \Magento\Framework\App\Response\Http                      $http
     * @param \Magento\Framework\Escaper                                $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Json\Helper\Data $jsonEncoder,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http $http,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->ruleValue = $ruleValue;
        $this->http = $http;
        $this->escaper = $escaper;
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
