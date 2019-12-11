<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Trial\TrialSetup;
use Dotdigitalgroup\Email\Model\Trial\TrialSetupFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\DateTime\Timezone;

class Accountcallback extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Timezone
     */
    private $timezone;

    /**
     * @var TrialSetup
     */
    private $trialSetup;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * Accountcallback constructor.
     * @param Context $context
     * @param Timezone $timezone
     * @param TrialSetupFactory $trialSetupFactory
     * @param Data $helper
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        Context $context,
        Timezone $timezone,
        TrialSetupFactory $trialSetupFactory,
        Data $helper,
        \Magento\Framework\Module\Manager $moduleManager
    ) {

        $this->timezone = $timezone;
        $this->trialSetup = $trialSetupFactory->create();
        $this->helper = $helper;
        $this->moduleManager = $moduleManager;
        parent::__construct($context);
    }

    /**
     * Process the callback
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

        $this->helper->debug('Account callback request', $params);

        if (!isset($params['code']) || !$this->trialSetup->isCodeValid($params['code'])) {
            return $this->sendErrorResponse();
        }

        // save credentials and reinit cache
        $this->helper->saveApiCredentials(
            $params['apiusername'],
            $params['apipassword'],
            $params['apiendpoint'] ?? null,
            $website
        );

        if ($chatAccountCreated = (!empty($params['apispaceid']) && !empty($params['token']))) {
            $this->helper->saveChatApiSpaceIdAndToken($params['apispaceid'], $params['token'], $website);
        }

        // enable EC in Magento
        $this->helper->enableEngagementCloud($website)
            ->reinitialiseConfig();

        // set up EC account
        $dataFieldsStatus = $this->trialSetup->setupDataFields();
        $addressBookStatus = $this->trialSetup->createAddressBooks();
        $syncStatus = $this->trialSetup->enableSyncForTrial();

        $this->helper->log('Engagement Cloud account creation', [
            'api_username' => $params['apiusername'],
            'api_endpoint' => $params['apiendpoint'],
            'chat_account' => $chatAccountCreated
                ? ['api_space_id' => $params['apispaceid']]
                : false,
            'data_field_set_up' => $dataFieldsStatus,
            'address_books_set_up' => $addressBookStatus,
            'syncs_enabled_for_trial' => $syncStatus,
        ]);

        return $this->getResponse()
            ->setHttpResponseCode(201)
            ->sendHeaders();
    }

    /**
     * Send error response
     *
     * @return ResponseInterface
     */
    private function sendErrorResponse()
    {
        return $this->getResponse()
            ->setHttpResponseCode(401)
            ->sendHeaders();
    }
}
