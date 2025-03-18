<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use InvalidArgumentException;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime;

class Importer extends AbstractModel
{
    public const NOT_IMPORTED = 0;
    public const IMPORTING = 1;
    public const IMPORTED = 2;
    public const FAILED = 3;

    //import mode
    public const MODE_BULK = 'Bulk';
    public const MODE_BULK_JSON = 'Bulk_JSON';
    public const MODE_SINGLE = 'Single';
    public const MODE_SINGLE_DELETE = 'Single_Delete';
    public const MODE_CONTACT_DELETE = 'Contact_Delete';
    public const MODE_SUBSCRIBER_UNSUBSCRIBE = 'Subscriber_Unsubscribe';
    public const MODE_CONTACT_EMAIL_UPDATE = 'Contact_Email_Update';
    public const MODE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber_Resubscribed';

    //import type
    public const IMPORT_TYPE_GUEST = 'Guest';
    public const IMPORT_TYPE_ORDERS = 'Orders';
    public const IMPORT_TYPE_CUSTOMER = 'Customer';
    public const IMPORT_TYPE_REVIEWS = 'Reviews';
    public const IMPORT_TYPE_WISHLIST = 'Wishlist';
    public const IMPORT_TYPE_CONTACT_UPDATE = 'Contact';
    public const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
    public const IMPORT_TYPE_SUBSCRIBER_UPDATE = 'Subscriber';
    public const IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber';
    public const IMPORT_TYPE_CART_INSIGHT_CART_PHASE = 'CartInsight';
    public const IMPORT_TYPE_CONSENT = 'Consent';

    /**
     * @deprecated
     * @see self::IMPORT_TYPE_CUSTOMER
     */
    public const IMPORT_TYPE_CONTACT = 'Contact';

    /**
     * @deprecated
     * @see self::MODE_SUBSCRIBER_UNSUBSCRIBE
     */
    public const MODE_SUBSCRIBER_UPDATE = 'Subscriber_Update';

    /**
     * @var ResourceModel\Importer
     */
    private $importerResource;

    /**
     * @var ResourceModel\Importer\CollectionFactory
     */
    private $importerCollection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Importer constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ResourceModel\Importer $importerResource
     * @param CollectionFactory $importerCollection
     * @param DateTime $dateTime
     * @param SerializerInterface $serializer
     * @param Data $helper
     * @param array $data
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ResourceModel\Importer $importerResource,
        ResourceModel\Importer\CollectionFactory $importerCollection,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        SerializerInterface $serializer,
        Data $helper,
        array $data = [],
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        $this->dateTime      = $dateTime;
        $this->serializer    = $serializer;
        $this->importerResource = $importerResource;
        $this->importerCollection = $importerCollection;
        $this->helper = $helper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Constructor.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(ResourceModel\Importer::class);
    }

    /**
     * Override core action.
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($this->dateTime->formatDate(true));
        }
        $this->setUpdatedAt($this->dateTime->formatDate(true));

        return $this;
    }

    /**
     * Register import in queue.
     *
     * @param string $importType
     * @param array|string|null $importData
     * @param string $importMode
     * @param int $websiteId
     * @param bool $file
     * @param int $retryCount
     * @param int $importStatus
     * @param string $importId
     * @param string $message
     *
     * @return bool
     *
     * @deprecated See newer method
     * @see addToImporterQueue
     */
    public function registerQueue(
        $importType,
        $importData,
        $importMode,
        $websiteId,
        $file = false,
        int $retryCount = 0,
        int $importStatus = 0,
        string $importId = '',
        string $message = ''
    ) {
        /**
         * Items that failed to imported for two times in a row should be ignored.
         */
        if ($retryCount === 3) {
            return true;
        }

        try {
            if (!empty($importData)) {
                $importData = $this->serializer->serialize($importData);
            }

            if ($file) {
                $this->setImportFile($file);
            }

            if ($importData || $file) {
                $this->setImportType($importType)
                    ->setImportStatus($importStatus)
                    ->setImportId($importId)
                    ->setImportData($importData)
                    ->setWebsiteId($websiteId)
                    ->setImportMode($importMode)
                    ->setMessage($message)
                    ->setRetryCount($retryCount);

                $this->importerResource->save($this);

                return true;
            }
        } catch (\InvalidArgumentException $e) {
            $message = "Json error for import type ($importType) / mode ($importMode) for website ($websiteId): "
                . (string)$e;
            $this->helper->error($message, []);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return false;
    }

    /**
     * Register import in queue.
     *
     * @param string $importType
     * @param array $importData
     * @param string $importMode
     * @param int $websiteId
     * @param int $retryCount
     * @param int $importStatus
     * @param string $importId
     * @param string $message
     * @param string $importStarted
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function addToImporterQueue(
        string $importType,
        array $importData,
        string $importMode,
        int $websiteId,
        int $retryCount = 0,
        int $importStatus = 0,
        string $importId = '',
        string $message = '',
        string $importStarted = ''
    ): void {
        if ($retryCount === 3) {
            return;
        }

        if (empty($importData)) {
            return;
        }

        $serializedData = $this->serializer->serialize($importData);

        $this->setImportType($importType)
            ->setImportStatus($importStatus)
            ->setImportId($importId)
            ->setImportData($serializedData)
            ->setWebsiteId($websiteId)
            ->setImportMode($importMode)
            ->setMessage($message)
            ->setRetryCount($retryCount)
            ->setImportStarted($importStarted);

        $this->importerResource->save($this);
    }

    /**
     * Saves item.
     *
     * @deprecated Use the resource model directly in classes.
     * @see \Dotdigitalgroup\Email\Model\ResourceModel\Importer
     *
     * @param \Dotdigitalgroup\Email\Model\Importer $itemToSave
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveItem($itemToSave)
    {
        $this->importerResource->save($itemToSave);
    }

    /**
     * Get imports marked as importing for one or more websites.
     *
     * @deprecated Use a collection factory directly in classes.
     * @see \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection::getItemsWithImportingStatus()
     *
     * @param array $websiteIds
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection|bool
     */
    public function _getImportingItems($websiteIds)
    {
        return $this->importerCollection->create()
            ->getItemsWithImportingStatus($websiteIds, []);
    }

    /**
     * Get the imports by type.
     *
     * @param string|array $importType
     * @param string $importMode
     * @param int $limit
     * @param array $websiteIds
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection
     */
    public function _getQueue($importType, $importMode, $limit, $websiteIds)
    {
        return $this->importerCollection->create()
            ->getQueueByTypeAndMode(
                $importType,
                $importMode,
                $limit,
                $websiteIds
            );
    }
}
