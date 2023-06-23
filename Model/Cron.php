<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Enroller;
use Dotdigitalgroup\Email\Model\Contact\PlatformChangeManagerFactory;
use Dotdigitalgroup\Email\Model\Cron\CronSubFactory;
use Dotdigitalgroup\Email\Model\Cron\JobChecker;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
use Dotdigitalgroup\Email\Model\Newsletter\UnsubscriberFactory;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\AutomationFactory;
use Dotdigitalgroup\Email\Model\Sync\CampaignFactory;
use Dotdigitalgroup\Email\Model\Sync\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\ConsentFactory;
use Dotdigitalgroup\Email\Model\Sync\CustomerFactory;
use Dotdigitalgroup\Email\Model\Sync\GuestFactory;
use Dotdigitalgroup\Email\Model\Sync\ImporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsightsFactory;
use Dotdigitalgroup\Email\Model\Sync\OrderFactory;
use Dotdigitalgroup\Email\Model\Sync\SubscriberFactory;

class Cron
{
    public const CRON_PATHS = [
        Config::XML_PATH_CRON_SCHEDULE_IMPORTER => 5,
        Config::XML_PATH_CRON_SCHEDULE_ORDERS => 15,
        Config::XML_PATH_CRON_SCHEDULE_REVIEWS => 15,
        Config::XML_PATH_CRON_SCHEDULE_CATALOG => 15,
        Config::XML_PATH_CRON_SCHEDULE_CUSTOMER => 15,
        Config::XML_PATH_CRON_SCHEDULE_SUBSCRIBER => 15,
        Config::XML_PATH_CRON_SCHEDULE_GUEST => 15,
        Config::XML_PATH_CRON_SCHEDULE_CONSENT => 15
    ];

    /**
     * @var Email\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var Sync\CustomerFactory
     */
    private $customerFactory;

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
     * @var Sync\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var Newsletter\UnsubscriberFactory
     */
    private $unsubscriberFactory;

    /**
     * @var PlatformChangeManagerFactory
     */
    private $platformChangeManagerFactory;

    /**
     * @var Sync\GuestFactory
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
     * @var Sync\ConsentFactory
     */
    private $consentFactory;

    /**
     * @param CampaignFactory $campaignFactory
     * @param OrderFactory $syncOrderFactory
     * @param QuoteFactory $quoteFactory
     * @param GuestFactory $guestFactory
     * @param SubscriberFactory $subscriberFactory
     * @param UnsubscriberFactory $unsubscriberFactory
     * @param PlatformChangeManagerFactory $platformChangeManagerFactory
     * @param CatalogFactory $catalogFactory
     * @param ImporterFactory $importerFactory
     * @param AutomationFactory $automationFactory
     * @param CustomerFactory $customerFactory
     * @param ConsentFactory $consentFactory
     * @param TemplateFactory $templateFactory
     * @param CronSubFactory $cronSubFactory
     * @param Enroller $abandonedCartProgramEnroller
     * @param IntegrationInsightsFactory $integrationInsightsFactory
     * @param MonitorFactory $monitorFactory
     * @param JobChecker $jobChecker
     */
    public function __construct(
        Sync\CampaignFactory $campaignFactory,
        Sync\OrderFactory $syncOrderFactory,
        Sales\QuoteFactory $quoteFactory,
        Sync\GuestFactory $guestFactory,
        Sync\SubscriberFactory $subscriberFactory,
        Newsletter\UnsubscriberFactory $unsubscriberFactory,
        PlatformChangeManagerFactory $platformChangeManagerFactory,
        Sync\CatalogFactory $catalogFactory,
        Sync\ImporterFactory $importerFactory,
        Sync\AutomationFactory $automationFactory,
        Sync\CustomerFactory $customerFactory,
        Sync\ConsentFactory $consentFactory,
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
        $this->platformChangeManagerFactory = $platformChangeManagerFactory;
        $this->catalogFactory    = $catalogFactory;
        $this->importerFactory   = $importerFactory;
        $this->automationFactory = $automationFactory;
        $this->customerFactory = $customerFactory;
        $this->consentFactory = $consentFactory;
        $this->templateFactory   = $templateFactory;
        $this->cronHelper        = $cronSubFactory->create();
        $this->abandonedCartProgramEnroller = $abandonedCartProgramEnroller;
        $this->integrationInsights = $integrationInsightsFactory;
        $this->monitor = $monitorFactory;
        $this->jobChecker = $jobChecker;
    }

    /**
     * Run customer sync.
     *
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function customerSync()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_customer_sync')) {
            return;
        }

        return $this->customerFactory->create()->sync();
    }

    /**
     * Run guest sync.
     *
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function guestSync()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_guest_sync')) {
            return;
        }
        return $this->guestFactory->create()->sync();
    }

    /**
     * Run subscriber sync.
     *
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function subscriberSync()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_subscriber_sync')) {
            return;
        }
        return $this->subscriberFactory->create()
            ->sync();
    }

    /**
     * Catalog sync cron.
     *
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
     * Importer cron.
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
     * Send integration insight data.
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
     * Reviews and wishlist cron.
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
     * Review sync.
     *
     * @return array
     */
    public function reviewSync()
    {
        return $this->cronHelper->reviewSync();
    }

    /**
     * Abandoned cart cron.
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
     * Automation cron.
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
     * Order sync cron.
     *
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function orderSync()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_order_sync')) {
            return;
        }

        return $this->syncOrderFactory->create()
            ->sync();
    }

    /**
     * Email templates cron.
     *
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
     * Monitor cron.
     *
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
            ->run();
    }

    /**
     * This job checks recently modified contacts on Dotdigital.
     *
     * @return void
     */
    public function checkModifiedContacts()
    {
        $jobCode = 'ddg_automation_platform_modified_contacts';

        if ($this->jobChecker->hasAlreadyBeenRun($jobCode)) {
            return;
        }

        $this->platformChangeManagerFactory
            ->create(
                ['data' => ['fromTime' => $this->jobChecker->getLastJobFinishedAt($jobCode)]]
            )
            ->run();
    }

    /**
     * Run contact consent sync.
     *
     * @return void
     */
    public function consentSync(): void
    {
        $jobCode = 'ddg_automation_consent_sync';

        if ($this->jobChecker->hasAlreadyBeenRun($jobCode)) {
            return;
        }

        $this->consentFactory->create()
            ->sync();
    }
}
