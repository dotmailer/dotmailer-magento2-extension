<?php

namespace Dotdigitalgroup\Email\Model;

class Cron
{
    /**
     * @var Apiconnector\ContactFactory
     */
    public $contactFactory;
    /**
     * @var Sync\AutomationFactory
     */
    public $automationFactory;
    /**
     * @var ImporterFactory
     */
    public $importerFactory;
    /**
     * @var Sync\CatalogFactory
     */
    public $catalogFactory;
    /**
     * @var Newsletter\SubscriberFactory
     */
    public $subscriberFactory;
    /**
     * @var Customer\GuestFactory
     */
    public $guestFactory;
    /**
     * @var Sync\Wishlist
     */
    public $wishlistFactory;
    /**
     * @var Sales\OrderFactory
     */
    public $orderFactory;
    /**
     * @var Sync\ReviewFactory
     */
    public $reviewFactory;
    /**
     * @var Sales\QuoteFactory
     */
    public $quoteFactory;
    /**
     * @var Sync\OrderFactory
     */
    public $syncOrderFactory;
    /**
     * @var Sync\CampaignFactory
     */
    public $campaignFactory;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $fileHelper;
    /**
     * @var ResourceModel\Importer
     */
    public $importerResource;

    /**
     * Cron constructor.
     *
     * @param Sync\CampaignFactory               $campaignFactory
     * @param Sync\OrderFactory                  $syncOrderFactory
     * @param Sales\QuoteFactory                 $quoteFactory
     * @param Sync\ReviewFactory                 $reviewFactory
     * @param Sales\OrderFactory                 $orderFactory
     * @param Sync\WishlistFactory               $wishlistFactory
     * @param Customer\GuestFactory              $guestFactory
     * @param Newsletter\SubscriberFactory       $subscriberFactory
     * @param Sync\CatalogFactory                $catalogFactorty
     * @param ImporterFactory                    $importerFactory
     * @param Sync\AutomationFactory             $automationFactory
     * @param Apiconnector\ContactFactory        $contact
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param ResourceModel\Importer             $importerResource
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Sync\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Model\Sync\OrderFactory $syncOrderFactory,
        \Dotdigitalgroup\Email\Model\Sales\QuoteFactory $quoteFactory,
        \Dotdigitalgroup\Email\Model\Sync\ReviewFactory $reviewFactory,
        \Dotdigitalgroup\Email\Model\Sales\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Model\Customer\GuestFactory $guestFactory,
        \Dotdigitalgroup\Email\Model\Newsletter\SubscriberFactory $subscriberFactory,
        \Dotdigitalgroup\Email\Model\Sync\CatalogFactory $catalogFactorty,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\Sync\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\Apiconnector\ContactFactory $contact,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
    ) {
        $this->campaignFactory   = $campaignFactory;
        $this->syncOrderFactory  = $syncOrderFactory;
        $this->quoteFactory      = $quoteFactory;
        $this->reviewFactory     = $reviewFactory;
        $this->orderFactory      = $orderFactory;
        $this->wishlistFactory   = $wishlistFactory;
        $this->guestFactory      = $guestFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->catalogFactory    = $catalogFactorty;
        $this->importerFactory   = $importerFactory;
        $this->automationFactory = $automationFactory;
        $this->contactFactory    = $contact;
        $this->helper           = $helper;
        $this->fileHelper       = $fileHelper;
        $this->importerResource = $importerResource;
    }

    /**
     * CRON FOR CONTACTS SYNC.
     *
     * @return array
     */
    public function contactSync()
    {

        //run the sync for contacts
        /** @var Apiconnector\Contact $result */
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
     * @return mixed
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
     * @return mixed
     */
    public function catalogSync()
    {
        $result = $this->catalogFactory->create()
            ->sync();

        return $result;
    }

    /**
     * CRON FOR EMAIL IMPORTER PROCESSOR.
     *
     * @return mixed
     */
    public function emailImporter()
    {
        return $this->importerFactory->create()->processQueue();
    }

    /**
     * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS.
     */
    public function reviewsAndWishlist()
    {
        //sync reviews
        $this->reviewSync();
        //sync wishlist
        $this->wishlistFactory->create()
            ->sync();
    }

    /**
     * Review sync.
     *
     * @return mixed
     */
    public function reviewSync()
    {
        //find orders to review and register campaign
        $this->orderFactory->create()
            ->createReviewCampaigns();
        //sync reviews
        $result = $this->reviewFactory->create()
            ->sync();

        return $result;
    }

    /**
     * CRON FOR ABANDONED CARTS.
     */
    public function abandonedCarts()
    {
        $this->quoteFactory->create()->proccessAbandonedCarts();
    }

    /**
     * CRON FOR AUTOMATION.
     */
    public function syncAutomation()
    {
        $this->automationFactory->create()->sync();
    }

    /**
     * Send email campaigns.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendCampaigns()
    {
        $this->campaignFactory->create()->sendCampaigns();
    }

    /**
     * CRON FOR ORDER TRANSACTIONAL DATA.
     */
    public function orderSync()
    {
        // send order
        $orderResult = $this->syncOrderFactory->create()->sync();

        return $orderResult;
    }

    /**
     * Cleaning for csv files and connector tables.
     *
     * @return string
     */
    public function cleaning()
    {
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
}
