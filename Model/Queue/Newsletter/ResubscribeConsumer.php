<?php

namespace Dotdigitalgroup\Email\Model\Queue\Newsletter;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Queue\Data\ResubscribeData;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Magento\Newsletter\Model\Subscriber;

class ResubscribeConsumer
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        Data $helper,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Process consumer.
     *
     * @param ResubscribeData $resubscribeData
     *
     * @return void
     */
    public function process(ResubscribeData $resubscribeData)
    {
        $client = $this->helper->getWebsiteApiClient($resubscribeData->getWebsiteId());
        $subscribersAddressBook = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            $resubscribeData->getWebsiteId()
        );

        try {
            ($subscribersAddressBook) ?
                $client->postAddressBookContactResubscribe(
                    $subscribersAddressBook,
                    $resubscribeData->getEmail()
                ) :
                $client->resubscribeContactByEmail($resubscribeData->getEmail());
            $this->logger->info('Newsletter resubscribe success', ['email' => $resubscribeData->getEmail()]);
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }
    }
}
