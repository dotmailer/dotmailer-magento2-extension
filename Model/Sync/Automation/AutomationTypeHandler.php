<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\Type\AbandonedCartFactory;

class AutomationTypeHandler
{
    const AUTOMATION_TYPE_NEW_CUSTOMER = 'customer_automation';
    const AUTOMATION_TYPE_NEW_SUBSCRIBER = 'subscriber_automation';
    const AUTOMATION_TYPE_NEW_ORDER = 'order_automation';
    const AUTOMATION_TYPE_NEW_GUEST_ORDER = 'guest_order_automation';
    const AUTOMATION_TYPE_NEW_REVIEW = 'review_automation';
    const AUTOMATION_TYPE_NEW_WISHLIST = 'wishlist_automation';
    const AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER = 'first_order_automation';
    const AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT = 'abandoned_cart_automation';
    const ORDER_STATUS_AUTOMATION = 'order_automation_';

    /**
     * @var array
     */
    private $automationTypeToConfigMap = [
        self::AUTOMATION_TYPE_NEW_CUSTOMER => Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER,
        self::AUTOMATION_TYPE_NEW_SUBSCRIBER => Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER,
        self::AUTOMATION_TYPE_NEW_ORDER => Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER,
        self::AUTOMATION_TYPE_NEW_GUEST_ORDER => Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER,
        self::AUTOMATION_TYPE_NEW_REVIEW => Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_REVIEW,
        self::AUTOMATION_TYPE_NEW_WISHLIST => Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST,
        self::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER => Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER,
        self::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT => Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID,
    ];

    /**
     * @var CollectionFactory
     */
    private $automationCollectionFactory;

    /**
     * @var AutomationProcessorFactory
     */
    private $defaultAutomationProcessorFactory;

    /**
     * @var AbandonedCartFactory
     */
    private $abandonedCartAutomationFactory;

    /**
     * AutomationTypeHandler constructor.
     * @param CollectionFactory $automationCollectionFactory
     * @param AutomationProcessorFactory $defaultAutomationProcessorFactory
     * @param AbandonedCartFactory $abandonedCartAutomationFactory
     */
    public function __construct(
        CollectionFactory $automationCollectionFactory,
        AutomationProcessorFactory $defaultAutomationProcessorFactory,
        AbandonedCartFactory $abandonedCartAutomationFactory
    ) {
        $this->automationCollectionFactory = $automationCollectionFactory;
        $this->defaultAutomationProcessorFactory = $defaultAutomationProcessorFactory;
        $this->abandonedCartAutomationFactory = $abandonedCartAutomationFactory;
    }

    /**
     * Fetch automation types
     * @return array
     */
    public function getAutomationTypes()
    {
        $automationTypes = [];
        $pendingAndConfirmedTypes = $this->automationCollectionFactory->create()
            ->getTypesForPendingAndConfirmedAutomations();

        foreach ($pendingAndConfirmedTypes as $type) {
            if ($type === self::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT) {
                $automationTypes[$type]['model'] = $this->abandonedCartAutomationFactory;
            } else {
                $automationTypes[$type]['model'] = $this->defaultAutomationProcessorFactory;
            }
        }

        return $automationTypes;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getPathFromAutomationType($type)
    {
        return $this->automationTypeToConfigMap[$type];
    }
}
