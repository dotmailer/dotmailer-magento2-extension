<?php

namespace Dotdigitalgroup\Email\Model;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Cron
{
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
     * @var ImporterFactory
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
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    private $fileHelper;

    /**
     * @var ResourceModel\Importer
     */
    private $importerResource;

    /**
     * @var ResourceModel\Cron\CollectionFactory
     */
    private $cronCollection;

    /**
     * @var Cron\CronSub
     */
    private $cronHelper;

    /**
     * Cron constructor.
     *
     * @param Sync\CampaignFactory                     $campaignFactory
     * @param Sync\OrderFactory                        $syncOrderFactory
     * @param Sales\QuoteFactory                       $quoteFactory
     * @param Customer\GuestFactory                    $guestFactory
     * @param Newsletter\SubscriberFactory             $subscriberFactory
     * @param Sync\CatalogFactory                      $catalogFactorty
     * @param ImporterFactory                          $importerFactory
     * @param Sync\AutomationFactory                   $automationFactory
     * @param Apiconnector\ContactFactory              $contact
     * @param \Dotdigitalgroup\Email\Helper\Data       $helper
     * @param \Dotdigitalgroup\Email\Helper\File       $fileHelper
     * @param ResourceModel\Importer                   $importerResource
     * @param ResourceModel\Cron\CollectionFactory     $cronCollection
     * @param Cron\CronSubFactory                      $cronSubFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Sync\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Model\Sync\OrderFactory $syncOrderFactory,
        \Dotdigitalgroup\Email\Model\Sales\QuoteFactory $quoteFactory,
        \Dotdigitalgroup\Email\Model\Customer\GuestFactory $guestFactory,
        \Dotdigitalgroup\Email\Model\Newsletter\SubscriberFactory $subscriberFactory,
        \Dotdigitalgroup\Email\Model\Sync\CatalogFactory $catalogFactorty,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\Sync\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\Apiconnector\ContactFactory $contact,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        \Dotdigitalgroup\Email\Model\Email\TemplateFactory $templateFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory $cronCollection,
        Cron\CronSubFactory $cronSubFactory
    ) {
        $this->campaignFactory   = $campaignFactory;
        $this->syncOrderFactory  = $syncOrderFactory;
        $this->quoteFactory      = $quoteFactory;
        $this->guestFactory      = $guestFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->catalogFactory    = $catalogFactorty;
        $this->importerFactory   = $importerFactory;
        $this->automationFactory = $automationFactory;
        $this->contactFactory    = $contact;
        $this->helper            = $helper;
        $this->fileHelper        = $fileHelper;
        $this->importerResource  = $importerResource;
        $this->cronCollection    = $cronCollection;
        $this->templateFactory   = $templateFactory;
        $this->cronHelper        = $cronSubFactory->create();
    }

    /**
     * CRON FOR CONTACTS SYNC.
     *
     * @return array
     */
    public function contactSync()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_customer_subscriber_guest_sync')) {
            $message = 'Skipping ddg_automation_customer_subscriber_guest_sync job run';
            $this->helper->log($message);
            return ['message' => $message];
        }

        //run the sync for contacts
        $result = $this->contactFactory->create()
            ->sync();
        //run subscribers and guests sync
        $subscriberResult = $this->subscribersAndGuestSync();

        if (isset($subscriberResult['message']) && isset($result['message'])) {
            $result['message'] = $result['message'] . ' - '
                . $subscriberResult['message'];
        }

        return $result;
    }

    /**
     * CRON FOR SUBSCRIBERS AND GUEST CONTACTS.
     *
     * @return array
     */
    public function subscribersAndGuestSync()
    {
        //sync subscribers
        $subscriberModel = $this->subscriberFactory->create();
        $result = $subscriberModel->sync();

        //un-subscribe suppressed contacts
        $subscriberModel->unsubscribe();

        //sync guests
        $this->guestFactory->create()->sync();

        return $result;
    }

    /**
     * CRON FOR CATALOG SYNC.
     *
     * @return array
     */
    public function catalogSync()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_catalog_sync')) {
            $message = 'Skipping ddg_automation_catalog_sync job run';
            $this->helper->log($message);
            return ['message' => $message];
        }

        $result = $this->catalogFactory->create()
            ->sync();

        return $result;
    }

    /**
     * CRON FOR EMAIL IMPORTER PROCESSOR.
     *
     * @return null
     */
    public function emailImporter()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_importer')) {
            $this->helper->log('Skipping ddg_automation_importer job run');
            return;
        }

        $this->importerFactory->create()->processQueue();
    }

    /**
     * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS.
     *
     * @return null
     */
    public function reviewsAndWishlist()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_reviews_and_wishlist')) {
            $this->helper->log('Skipping ddg_automation_reviews_and_wishlist job run');
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
     * CRON FOR ABANDONED CARTS.
     *
     * @return null
     */
    public function abandonedCarts()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_abandonedcarts')) {
            $this->helper->log('Skipping ddg_automation_abandonedcarts job run');
            return;
        }

        $this->quoteFactory->create()->processAbandonedCarts();
    }

    /**
     * CRON FOR AUTOMATION.
     *
     * @return null
     */
    public function syncAutomation()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_status')) {
            $this->helper->log('Skipping ddg_automation_status job run');
            return;
        }

        $this->automationFactory->create()->sync();
    }

    /**
     * Send email campaigns.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null
     */
    public function sendCampaigns()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_campaign')) {
            $this->helper->log('Skipping ddg_automation_campaign job run');
            return;
        }

        $this->campaignFactory->create()->sendCampaigns();
    }

    /**
     * CRON FOR ORDER TRANSACTIONAL DATA.
     *
     * @return array
     */
    public function orderSync()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_order_sync')) {
            $message = 'Skipping ddg_automation_order_sync job run';
            $this->helper->log($message);
            return ['message' => $message];
        }

        // send order
        $orderResult = $this->syncOrderFactory->create()
            ->sync();

        return $orderResult;
    }

    /**
     * Cleaning for csv files and connector tables.
     *
     * @return string
     */
    public function cleaning()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_cleaner')) {
            $message = 'Skipping ddg_automation_cleaner job run';
            $this->helper->log($message);
            return $message;
        }

        //Clean tables
        $tables = [
            'automation' => 'email_automation',
            'importer' => 'email_importer',
            'campaign' => 'email_campaign',
        ];
        $message = 'Cleaning cron job result :';

        foreach ($tables as $key => $table) {
            $result = $this->importerResource->cleanup($table);
            $message .= " $result records removed from $key .";
        }
        $archivedFolder = $this->fileHelper->getArchiveFolder();
        $result = $this->fileHelper->deleteDir($archivedFolder);
        $message .= ' Deleting archived folder result : ' . $result;
        $this->helper->log($message);

        return $message;
    }

    /**
     * Check if already ran for same time
     *
     * @param string $jobCode
     * @return bool
     */
    private function jobHasAlreadyBeenRun($jobCode)
    {
        $currentRunningJob = $this->cronCollection->create()
            ->addFieldToFilter('job_code', $jobCode)
            ->addFieldToFilter('status', 'running')
            ->setPageSize(1);

        if ($currentRunningJob->getSize()) {
            $jobOfSameTypeAndScheduledAtDateAlreadyExecuted =  $this->cronCollection->create()
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter('scheduled_at', $currentRunningJob->getFirstItem()->getScheduledAt())
                ->addFieldToFilter('status', ['in' => ['success', 'failed']]);

            return ($jobOfSameTypeAndScheduledAtDateAlreadyExecuted->getSize()) ? true : false;
        }

        return false;
    }

    /**
     * Sync the email templates from dotmailer.
     */
    public function syncEmailTemplates()
    {
        if ($this->jobHasAlreadyBeenRun('ddg_automation_email_templates')) {
            $message = 'Skipping ddg_automation_email_templates job run';
            $this->helper->log($message);
            return $message;
        }

        return $this->templateFactory->create()
            ->sync();
    }
}
