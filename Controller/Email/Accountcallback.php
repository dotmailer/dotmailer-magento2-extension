<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Accountcallback extends \Magento\Framework\App\Action\Action
{
    protected $_helper;
    protected $_jsonHelper;

    /**
     * Accountcallback constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Trial $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dotdigitalgroup\Email\Helper\Trial $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->_helper = $helper;
        $this->_jsonHelper = $jsonHelper;

        parent::__construct($context);
    }

    
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $error = false;
        if (!empty($params['accountId']) && !empty($params['apiUser']) && !empty($params['pass']) && !empty($params['secret'])) {
            if ($params['secret'] == \Dotdigitalgroup\Email\Helper\Config::API_CONNECTOR_TRIAL_FORM_SECRET) {
                $apiConfigStatus = $this->_helper->saveApiCreds($params['apiUser'], $params['pass']);
                $dataFieldsStatus = $this->_helper->setupDataFields($params['apiUser'], $params['pass']);
                $addressBookStatus = $this->_helper->createAddressBooks($params['apiUser'], $params['pass']);
                $syncStatus = $this->_helper->enableSyncForTrial();
                if (isset($params['apiEndpoint'])) {
                    $this->_helper->saveApiEndPoint($params['apiEndpoint']);
                }
                if ($apiConfigStatus && $dataFieldsStatus && $addressBookStatus && $syncStatus) {
                    $this->sendAjaxResponse(false, $this->_getSuccessHtml());
                } else {
                    $error = true;
                }
            } else {
                $error = true;
            }
        } else {
            $error = true;
        }

        //If error true then send error html
        if ($error) {
            $this->sendAjaxResponse(true, $this->_getErrorHtml());
        }
    }

    /**
     * send ajax response
     *
     * @param $error
     *
     * @param $msg
     */
    public function sendAjaxResponse($error, $msg)
    {
        $message = array(
            'err' => $error,
            'message' => $msg
        );
        $this->getResponse()->setBody(
            $this->getRequest()->getParam('callback') . "(" . $this->_jsonHelper->jsonEncode($message) . ")"
        )->sendResponse();

    }

    /**
     * get success html
     *
     * @return string
     */
    protected function _getSuccessHtml()
    {
        return
            "<div class='modal-page'>
                <div class='success'></div>
                <h2 class='center'>Congratulations your dotmailer account is now ready, time to make your marketing awesome</h2>
                <div class='center'>
                    <input type='submit' class='center' value='Start making money' />
                </div>
            </div>";
    }

    /**
     * get error html
     *
     * @return string
     */
    protected function _getErrorHtml()
    {
        return
            "<div class='modal-page'>
                <div class='fail'></div>
                <h2 class='center'>Sorry, something went wrong whilst trying to create your new dotmailer account</h2>
                <div class='center'>
                    <a class='submit secondary center' href='mailto:support@dotmailer.com'>Contact support@dotmailer.com</a>
                </div>
            </div>";
    }
}