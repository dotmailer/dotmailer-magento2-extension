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
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    private $timezone;

    /**
     * Accountcallback constructor.
     *
     * @param \Magento\Framework\App\Action\Context                   $context
     * @param \Dotdigitalgroup\Email\Helper\Data                      $helper
     * @param \Magento\Framework\Json\Helper\Data                     $jsonHelper
     * @param \Magento\Store\Model\StoreManagerInterface              $storeManager
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress    $remoteAddress
     * @param \Dotdigitalgroup\Email\Model\Trial\TrialSetup           $trialSetup
     * @param \Magento\Framework\Stdlib\DateTime\Timezone             $timezone
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Dotdigitalgroup\Email\Model\Trial\TrialSetup $trialSetup,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    ) {
        $this->timezone      = $timezone;
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
            ! $this->isCodeValid($params['code'])
        ) {
            $this->sendAjaxResponse(true);
        } else {
            $this->processAccountCallback($params);
        }
    }

    /**
     * @param array $params
     */
    private function processAccountCallback($params)
    {
        //Remove temporary passcode
        $this->helper->resourceConfig->deleteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE,
            'default',
            0
        );

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
            $this->sendAjaxResponse(false);
        } else {
            $this->sendAjaxResponse(true);
        }
    }

    /**
     * Send ajax response.
     *
     * @param string $error
     * @param string $msg
     * @return void
     */
    private function sendAjaxResponse($error)
    {
        $message = [
            'err' => $error
        ];

        $this->getResponse()
            ->setHeader('Content-type', 'application/javascript', true)
            ->setBody(
                'signupCallback(' . $this->jsonHelper->jsonEncode($message) . ')'
            )
            ->sendResponse();
    }

    /**
     * Validate code
     *
     * @param string $code
     * @return bool
     */
    public function isCodeValid($code)
    {
        $now = $this->timezone->date()->format(\DateTime::ATOM);
        $expiryDateString = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE_EXPIRY
        );

        if ($now >= $expiryDateString) {
            return false;
        }

        $codeFromConfig = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_TRIAL_TEMPORARY_PASSCODE
        );

        return $codeFromConfig === $code;
    }
}
