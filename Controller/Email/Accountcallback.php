<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Accountcallback extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\Trial\TrialSetup
     */
    private $trialSetup;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddress;

    /**
     * Accountcallback constructor.
     *
     * @param \Magento\Framework\App\Action\Context                   $context
     * @param \Dotdigitalgroup\Email\Helper\Data                      $helper
     * @param \Magento\Framework\Json\Helper\Data                     $jsonHelper
     * @param \Magento\Store\Model\StoreManagerInterface              $storeManager
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress    $remoteAddress
     * @param \Dotdigitalgroup\Email\Model\Trial\TrialSetup           $trialSetup
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Dotdigitalgroup\Email\Model\Trial\TrialSetup $trialSetup
    ) {
        $this->helper        = $helper;
        $this->jsonHelper    = $jsonHelper;
        $this->storeManager  = $storeManager;
        $this->remoteAddress = $remoteAddress;
        $this->trialSetup    = $trialSetup;

        parent::__construct($context);
    }

    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        //if no value to any of the required params send error response
        if (empty($params['apiUser']) ||
            empty($params['pass']) ||
            empty($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            $this->sendAjaxResponse(true, $this->getErrorHtml());
        } else {
            //Save api end point
            if (isset($params['apiEndpoint'])) {
                $this->trialSetup->saveApiEndPoint($params['apiEndpoint']);
            } else { //Save empty value to endpoint. New endpoint will be fetched when first api call made.
                $this->trialSetup->saveApiEndPoint('');
            }

            $apiConfigStatus = $this->trialSetup->saveApiCreds($params['apiUser'], $params['pass']);
            $dataFieldsStatus = $this->trialSetup->setupDataFields($params['apiUser'], $params['pass']);
            $addressBookStatus = $this->trialSetup->createAddressBooks($params['apiUser'], $params['pass']);
            $syncStatus = $this->trialSetup->enableSyncForTrial();

            if ($apiConfigStatus && $dataFieldsStatus && $addressBookStatus && $syncStatus) {
                $this->sendAjaxResponse(false, $this->getSuccessHtml());
            } else {
                $this->sendAjaxResponse(true, $this->getErrorHtml());
            }
        }
    }

    /**
     * Send ajax response.
     *
     * @param string $error
     * @param string $msg
     * @return void
     */
    private function sendAjaxResponse($error, $msg)
    {
        $message = [
            'err' => $error,
            'message' => $msg,
        ];
        $callback = $this->getRequest()->getParam('callback');
        $this->getResponse()
            ->setHeader('Content-type', 'application/javascript', true)
            ->setBody(
                $callback . '(' . $this->jsonHelper->jsonEncode($message) . ')'
            )
            ->sendResponse();
    }

    /**
     * Get success html.
     *
     * @return string
     */
    private function getSuccessHtml()
    {
        return
            "<div class='modal-page'>
                <div class='success'></div>
                <h2 class='center'>Congratulations your dotmailer account is now ready,
                 time to make your marketing awesome</h2>
                <div class='center'>
                    <input id='btnStartMakingMoney' type='submit' class='center' value='Start making money'  />
                </div>
            </div>";
    }

    /**
     * Get error html.
     *
     * @return string
     */
    private function getErrorHtml()
    {
        return
            "<div class='modal-page'>
                <div class='fail'></div>
                <h2 class='center'>Sorry, something went wrong whilst trying to create your new dotmailer account</h2>
                <div class='center'>
                    <a class='submit secondary center' href='mailto:support@dotmailer.com'>
                    Contact support@dotmailer.com</a>
                </div>
            </div>";
    }
}
