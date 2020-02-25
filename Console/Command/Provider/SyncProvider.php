<?php

namespace Dotdigitalgroup\Email\Console\Command\Provider;

use Dotdigitalgroup\Email\Model\Sync\AbandonedCartFactory;
use Dotdigitalgroup\Email\Model\Sync\AutomationFactory;
use Dotdigitalgroup\Email\Model\Sync\CampaignFactory;
use Dotdigitalgroup\Email\Model\Sync\CatalogFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\ContactFactory;
use Dotdigitalgroup\Email\Model\Customer\GuestFactory;
use Dotdigitalgroup\Email\Model\Sync\ImporterFactory;
use Dotdigitalgroup\Email\Model\Sync\IntegrationInsightsFactory;
use Dotdigitalgroup\Email\Model\Sync\OrderFactory;
use Dotdigitalgroup\Email\Model\Sync\ReviewFactory;
use Dotdigitalgroup\Email\Model\Newsletter\SubscriberFactory;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
use Dotdigitalgroup\Email\Model\Sync\WishlistFactory;

/**
 * Provides factories for all available sync models, and exposes it's properties to show what's available
 */
class SyncProvider
{
    /**
     * @var AbandonedCartFactory
     */
    private $abandonedCartFactory;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * @var CampaignFactory
     */
    private $campaignFactory;

    /**
     * @var CatalogFactory
     */
    private $catalogFactory;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var GuestFactory
     */
    private $guestFactory;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var IntegrationInsightsFactory
     */
    private $integrationInsightsFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @param AbandonedCartFactory $abandonedCartFactory
     * @param AutomationFactory $automationFactory
     * @param CampaignFactory $campaignFactory
     * @param CatalogFactory $catalogFactory
     * @param ContactFactory $contactFactory
     * @param GuestFactory $guestFactory
     * @param OrderFactory $orderFactory
     * @param SubscriberFactory $subscriberFactory
     * @param TemplateFactory $templateFactory
     * @param ImporterFactory $importerFactory
     * @param IntegrationInsightsFactory $integrationInsightsFactory
     * @param ReviewFactory $reviewFactory
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(
        AbandonedCartFactory $abandonedCartFactory,
        AutomationFactory $automationFactory,
        CampaignFactory $campaignFactory,
        CatalogFactory $catalogFactory,
        ContactFactory $contactFactory,
        GuestFactory $guestFactory,
        ImporterFactory $importerFactory,
        IntegrationInsightsFactory $integrationInsightsFactory,
        OrderFactory $orderFactory,
        ReviewFactory $reviewFactory,
        SubscriberFactory $subscriberFactory,
        TemplateFactory $templateFactory,
        WishlistFactory $wishlistFactory
    ) {
        $this->automationFactory = $automationFactory;
        $this->abandonedCartFactory = $abandonedCartFactory;
        $this->campaignFactory = $campaignFactory;
        $this->catalogFactory = $catalogFactory;
        $this->contactFactory = $contactFactory;
        $this->guestFactory = $guestFactory;
        $this->importerFactory = $importerFactory;
        $this->integrationInsightsFactory = $integrationInsightsFactory;
        $this->orderFactory = $orderFactory;
        $this->reviewFactory = $reviewFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->templateFactory = $templateFactory;
        $this->wishlistFactory = $wishlistFactory;
    }

    /**
     * Get available sync factories
     *
     * @param array $additionalSyncs
     * @return array
     */
    public function getAvailableSyncs(array $additionalSyncs = [])
    {
        static $availableSyncs;

        return $availableSyncs ?: $availableSyncs = array_map(function ($class) {
            $classBasename = substr(get_class($class), strrpos(get_class($class), '\\') + 1);
            return [
                'title' => str_replace('Factory', '', $classBasename),
                'factory' => $class,
            ];
        }, get_object_vars($this) + $additionalSyncs);
    }

    /**
     * Get a sync object from those available
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $name = lcfirst($name) . 'Factory';
        $availableSyncs = $this->getAvailableSyncs();

        if (isset($availableSyncs[$name])) {
            return $availableSyncs[$name]['factory']->create();
        }
        return null;
    }
}
