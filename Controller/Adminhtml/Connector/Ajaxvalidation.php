<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;

class Ajaxvalidation extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $data;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

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
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $apiUsername = $params['api_username'];
        $apiPassword = base64_decode($params['api_password']);
        //validate api, check against account info.
        if ($this->data->isEnabled()) {
            $client = $this->data->getWebsiteApiClient();
            $result = $client->validate($apiUsername, $apiPassword);

            $responseData['success'] = true;
            //validation failed
            if (!$result) {
                $responseData['success'] = false;
                $responseData['message'] = 'Authorization has been denied for this request.';
            }

            $this->getResponse()->representJson($this->jsonHelper->jsonEncode($responseData));
        }
    }
}
