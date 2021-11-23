<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Cron\JobChecker;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsightsFactory;

class Cron
{
    const CRON_PATHS = [
        Config::XML_PATH_CRON_SCHEDULE_IMPORTER => 5,
        Config::XML_PATH_CRON_SCHEDULE_ORDERS => 15,
        Config::XML_PATH_CRON_SCHEDULE_REVIEWS => 15,
        Config::XML_PATH_CRON_SCHEDULE_CONTACT => 15,
        Config::XML_PATH_CRON_SCHEDULE_CATALOG => 15,
    ];

    /**
     * @var Email\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var Apiconnector\ContactFactory
     */
    private $contactFactory;

    /**
     * @var Sync\AutomationFactory
     */
    private $automationFactory;

    /**
     * @var Sync\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var Sync\CatalogFactory
     */
    private $catalogFactory;

    /**
     * @var Newsletter\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var Newsletter\UnsubscriberFactory
     */
    private $unsubscriberFactory;

    /**
     * @var Newsletter\ResubscriberFactory
     */
    private $resubscriberFactory;

    /**
     * @var Customer\GuestFactory
     */
    private $guestFactory;

    /**
     * @var Sales\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var Sync\OrderFactory
     */
    private $syncOrderFactory;

    /**
     * @var Sync\CampaignFactory
     */
    private $campaignFactory;

    /**
     * @var Cron\CronSub
     */
    private $cronHelper;

    /**
     * @var IntegrationInsightsFactory
     */
    private $integrationInsights;

    /**
     * @var AbandonedCart\ProgramEnrolment\Enroller
     */
    private $abandonedCartProgramEnroller;

    /**
     * @var MonitorFactory
     */
    private $monitor;

    /**
     * @var JobChecker
     */
    private $jobChecker;

    /**
     * Cron constructor.
     *
     * @param Sync\CampaignFactory $campaignFactory
     * @param Sync\OrderFactory $syncOrderFactory
     * @param Sales\QuoteFactory $quoteFactory
     * @param Customer\GuestFactory $guestFactory
     * @param Newsletter\SubscriberFactory $subscriberFactory
     * @param Sync\CatalogFactory $catalogFactory
     * @param Sync\ImporterFactory $importerFactory
     * @param Sync\AutomationFactory $automationFactory
     * @param Apiconnector\ContactFactory $contact
     * @param Email\TemplateFactory $templateFactory
     * @param Cron\CronSubFactory $cronSubFactory
     * @param AbandonedCart\ProgramEnrolment\Enroller $abandonedCartProgramEnroller
     * @param IntegrationInsightsFactory $integrationInsightsFactory
     * @param MonitorFactory $monitorFactory
     * @param JobChecker $jobChecker
     */
    public function __construct(
        Sync\CampaignFactory $campaignFactory,
        Sync\OrderFactory $syncOrderFactory,
        Sales\QuoteFactory $quoteFactory,
        Customer\GuestFactory $guestFactory,
        Newsletter\SubscriberFactory $subscriberFactory,
        Newsletter\UnsubscriberFactory $unsubscriberFactory,
        Newsletter\ResubscriberFactory $resubscriberFactory,
        Sync\CatalogFactory $catalogFactory,
        Sync\ImporterFactory $importerFactory,
        Sync\AutomationFactory $automationFactory,
        Apiconnector\ContactFactory $contact,
        Email\TemplateFactory $templateFactory,
        Cron\CronSubFactory $cronSubFactory,
        AbandonedCart\ProgramEnrolment\Enroller $abandonedCartProgramEnroller,
        IntegrationInsightsFactory $integrationInsightsFactory,
        MonitorFactory $monitorFactory,
        JobChecker $jobChecker
    ) {
        $this->campaignFactory   = $campaignFactory;
        $this->syncOrderFactory  = $syncOrderFactory;
        $this->quoteFactory      = $quoteFactory;
        $this->guestFactory      = $guestFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->unsubscriberFactory = $unsubscriberFactory;
        $this->resubscriberFactory = $resubscriberFactory;
        $this->catalogFactory    = $catalogFactory;
        $this->importerFactory   = $importerFactory;
        $this->automationFactory = $automationFactory;
        $this->contactFactory    = $contact;
        $this->templateFactory   = $templateFactory;
        $this->cronHelper        = $cronSubFactory->create();
        $this->abandonedCartProgramEnroller = $abandonedCartProgramEnroller;
        $this->integrationInsights = $integrationInsightsFactory;
        $this->monitor = $monitorFactory;
        $this->jobChecker = $jobChecker;
    }

    /**
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function contactSync()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_customer_subscriber_guest_sync')) {
            return;
        }

        //run the sync for contacts
        $result = $this->contactFactory->create()
            ->sync();
        //run subscribers and guests sync
        $subscriberResult = $this->subscribersAndGuestSync();

        $result['message'] .= ' - ' . $subscriberResult['message'];

        return $result;
    }

    /**
     * CRON FOR SUBSCRIBERS AND GUEST CONTACTS.
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function subscribersAndGuestSync()
    {
        //sync subscribers
        $subscriberModel = $this->subscriberFactory->create();
        $result = $subscriberModel->runExport();

        //sync guests
        $this->guestFactory->create()->sync();

        return $result;
    }

    /**
     * @return void
     */
    public function catalogSync()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_catalog_sync')) {
            return;
        }

        $this->catalogFactory->create()
            ->sync();
    }

    /**
     * CRON FOR EMAIL IMPORTER PROCESSOR.
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function emailImporter()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_importer')) {
            return;
        }

        $this->importerFactory->create()
            ->sync();
    }

    /**
     * Send integration insight data
     */
    public function sendIntegrationInsights()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_integration_insights')) {
            return;
        }

        $this->integrationInsights->create()
            ->sync();
    }

    /**
     * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS.
     *
     * @return void
     */
    public function reviewsAndWishlist()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_reviews_and_wishlist')) {
            return;
        }

        //sync reviews
        $this->reviewSync();
        //sync wishlist
        $this->cronHelper->wishlistSync();
    }

    /**
     * @return array
     */
    public function reviewSync()
    {
        return $this->cronHelper->reviewSync();
    }

    /**
     * CRON FOR ABANDONED CARTS.
     *
     * @return void
     */
    public function abandonedCarts()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_abandonedcarts')) {
            return;
        }

        $this->quoteFactory->create()->processAbandonedCarts();
        $this->abandonedCartProgramEnroller->process();
    }

    /**
     * CRON FOR AUTOMATION.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncAutomation()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_status')) {
            return;
        }

        $this->automationFactory->create()->sync();
    }

    /**
     * Send email campaigns.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendCampaigns()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_campaign')) {
            return;
        }

        $this->campaignFactory->create()->sendCampaigns();
    }

    /**
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function orderSync()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_order_sync')) {
            return;
        }

        // send order
        return $this->syncOrderFactory->create()
            ->sync();
    }

    /**
     * @return void
     */
    public function syncEmailTemplates()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_email_templates')) {
            return;
        }

        $this->templateFactory->create()
            ->sync();
    }

    /**
     * @return void
     */
    public function monitor()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_monitor')) {
            return;
        }

        $this->monitor->create()
            ->run();
    }

    /**
     * Unsubscribe suppressions from Dotdigital.
     *
     * @return void
     */
    public function unsubscribe()
    {
        $jobCode = 'ddg_automation_unsubscribe';

        if ($this->jobChecker->hasAlreadyBeenRun($jobCode)) {
            return;
        }

        $this->unsubscriberFactory
            ->create(
                ['data' => ['fromTime' => $this->jobChecker->getLastJobFinishedAt($jobCode)]]
            )
            ->unsubscribe();
    }

    /**
     * Resubscribe recent subscribers from Dotdigital.
     *
     * @return void
     */
    public function resubscribe()
    {
        $jobCode = 'ddg_automation_resubscribe';

        if ($this->jobChecker->hasAlreadyBeenRun($jobCode)) {
            return;
        }

        $this->resubscriberFactory
            ->create(
                ['data' => ['fromTime' => $this->jobChecker->getLastJobFinishedAt($jobCode)]]
            )
            ->subscribe();
    }
}
