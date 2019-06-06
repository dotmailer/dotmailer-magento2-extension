<?php

namespace Dotdigitalgroup\Email\Console\Command\Provider;

use Dotdigitalgroup\Email\Model\Sync\AutomationFactory;
use Dotdigitalgroup\Email\Model\Sync\CampaignFactory;
use Dotdigitalgroup\Email\Model\Sync\CatalogFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\ContactFactory;
use Dotdigitalgroup\Email\Model\Sync\OrderFactory;
use Dotdigitalgroup\Email\Model\Sync\AbandonedCartFactory;
use Dotdigitalgroup\Email\Model\Newsletter\SubscriberFactory;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
use Dotdigitalgroup\Email\Model\Sync\ImporterFactory;

/**
 * Provides factories for all available sync models, and exposes it's properties to show what's available
 */
class SyncProvider
{
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
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var AbandonedCartFactory
     */
    private $abandonedCartFactory;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * SyncProvider constructor
     * @param AutomationFactory $automationFactory
     * @param CampaignFactory $campaignFactory
     * @param CatalogFactory $catalogFactory
     * @param ContactFactory $contactFactory
     * @param OrderFactory $orderFactory
     * @param SubscriberFactory $subscriberFactory
     * @param TemplateFactory $templateFactory
     * @param AbandonedCartFactory $abandonedCartFactory
     * @param ImporterFactory $importerFactory
     */
    public function __construct(
        AutomationFactory $automationFactory,
        CampaignFactory $campaignFactory,
        CatalogFactory $catalogFactory,
        ContactFactory $contactFactory,
        OrderFactory $orderFactory,
        SubscriberFactory $subscriberFactory,
        TemplateFactory $templateFactory,
        AbandonedCartFactory $abandonedCartFactory,
        ImporterFactory $importerFactory
    ) {
        $this->automationFactory = $automationFactory;
        $this->campaignFactory = $campaignFactory;
        $this->catalogFactory = $catalogFactory;
        $this->contactFactory = $contactFactory;
        $this->orderFactory = $orderFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->templateFactory = $templateFactory;
        $this->abandonedCartFactory = $abandonedCartFactory;
        $this->importerFactory = $importerFactory;
    }

    /**
     * Get names of available sync objects
     * @param bool $concreteName    Get the concrete class name (not it's factory)
     * @return array
     */
    public function getAvailableSyncs($concreteName = true)
    {
        return array_map(function ($class) use ($concreteName) {
            $classBasename = substr(get_class($class), strrpos(get_class($class), '\\') + 1);
            return $concreteName ? str_replace('Factory', '', $classBasename) : $classBasename;
        }, get_object_vars($this));
    }

    /**
     * Get a sync object from those available
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $name .= 'Factory';
        $availableSyncs = $this->getAvailableSyncs(false);

        if (in_array($name, $availableSyncs)) {
            return $this->{array_search($name, $availableSyncs)}->create();
        }
        return null;
    }
}
