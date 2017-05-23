<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;

/**
 * Class Ajaxvalidation
 * @package Dotdigitalgroup\Email\Controller\Adminhtml\Connector
 */
class Ajaxvalidation extends \Magento\Backend\App\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $data;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    public $jsonHelper;

    /**
     * Ajaxvalidation constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->data = $data;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    /**
     * Validate api user.
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $apiUsername = $params['api_username'];
        //@codingStandardsIgnoreStart
        $apiPassword = base64_decode($params['api_password']);
        //@codingStandardsIgnoreEnd
        //validate api, check against account info.
        if ($this->data->isEnabled()) {
            $client = $this->data->getWebsiteApiClient();
            $result = $client->validate($apiUsername, $apiPassword);

            $resonseData['success'] = true;
            //validation failed
            if (!$result) {
                $resonseData['success'] = false;
                $resonseData['message'] = 'Authorization has been denied for this request.';
            }

            $this->getResponse()->representJson($this->jsonHelper->jsonEncode($resonseData));
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::config');
    }
}
