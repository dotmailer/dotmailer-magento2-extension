<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Guest;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Connector\ContactDataFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\ExporterInterface;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;

class GuestExporter extends AbstractExporter implements ExporterInterface
{
    /**
     * @var ContactDataFactory
     */
    private $contactDataFactory;

    /**
     * @var SdkContactBuilder
     */
    private $sdkContactBuilder;

    /**
     * @var array $fieldMap
     */
    private $fieldMap = [];

    /**
     * Guest exporter constructor.
     *
     * @param CsvHandler $csvHandler
     * @param ContactDataFactory $contactDataFactory
     * @param SdkContactBuilder $sdkContactBuilder
     */
    public function __construct(
        CsvHandler $csvHandler,
        ContactDataFactory $contactDataFactory,
        SdkContactBuilder $sdkContactBuilder
    ) {
        $this->contactDataFactory = $contactDataFactory;
        $this->sdkContactBuilder = $sdkContactBuilder;
        parent::__construct($csvHandler);
    }

    /**
     * Guest exporter.
     *
     * @param array<DataObject> $guests
     * @param WebsiteInterface $website
     * @param int $listId
     *
     * @return array<SdkContact>
     * @throws LocalizedException|\Exception
     */
    public function export(array $guests, WebsiteInterface $website, int $listId): array
    {
        $exportedData = [];
        foreach ($guests as $guest) {
            /** @var Contact $guest */
            $connectorGuest = $this->contactDataFactory->create()
                ->init($guest, $this->fieldMap)
                ->setContactData();

            $exportedData[$guest->getEmailContactId()] = $this->sdkContactBuilder->createSdkContact(
                $connectorGuest,
                $this->fieldMap,
                $listId
            );
        }

        return $exportedData;
    }

    /**
     * Set csv columns.
     *
     * @param WebsiteInterface $website
     *
     * @return void
     *
     * @deprecated We no longer send data using csv files.
     * @see GuestExporter::setFieldMapping
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

    /**
     * Set field mapping.
     *
     * @param WebsiteInterface $website
     * @return void
     */
    public function setFieldMapping(WebsiteInterface $website): void
    {
        /** @var \Magento\Store\Model\Website $website */
        $guestColumns = [
            'store_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME),
            'store_name_additional' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME_ADDITIONAL),
            'website_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME)
        ];

        $this->fieldMap = AbstractExporter::EMAIL_FIELDS + array_filter($guestColumns);
    }

    /**
     * Get field mapping.
     *
     * @return array
     */
    public function getFieldMapping(): array
    {
        return $this->fieldMap;
    }
}
