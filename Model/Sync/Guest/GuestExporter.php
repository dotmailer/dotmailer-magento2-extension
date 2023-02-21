<?php

namespace Dotdigitalgroup\Email\Model\Sync\Guest;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Magento\Framework\DataObject;
use Magento\Store\Api\Data\WebsiteInterface;

class GuestExporter extends AbstractExporter
{
    /**
     * @var ContactData
     */
    private $contactData;

    /**
     * Guest exporter constructor.
     *
     * @param CsvHandler $csvHandler
     * @param ContactData $contactData
     */
    public function __construct(
        CsvHandler $csvHandler,
        ContactData $contactData
    ) {
        $this->contactData = $contactData;
        parent::__construct($csvHandler);
    }

    /**
     * Guest exporter.
     *
     * @param array<DataObject> $guests
     * @param WebsiteInterface|null $website
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function export(array $guests, WebsiteInterface $website = null): array
    {
        $exportedData = [];
        foreach ($guests as $guest) {
            /** @var Contact $guest */
            $exportedData[$guest->getEmailContactId()] = $this->contactData
                ->init($guest, $this->getCsvColumns())
                ->setContactData()
                ->toCSVArray();
        }

        return $exportedData;
    }

    /**
     * Set csv columns.
     *
     * @param WebsiteInterface $website
     * @return void
     */
    public function setCsvColumns(WebsiteInterface $website): void
    {
        /** @var \Magento\Store\Model\Website $website */
        $guestColumns = [
            'store_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME),
            'store_name_additional' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME_ADDITIONAL),
            'website_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME)
        ];

        $this->columns = AbstractExporter::EMAIL_FIELDS + array_filter($guestColumns);
    }
}
