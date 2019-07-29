<?php

namespace Dotdigitalgroup\Email\Model;

class Importer extends \Magento\Framework\Model\AbstractModel
{
    const NOT_IMPORTED = 0;
    const IMPORTING = 1;
    const IMPORTED = 2;
    const FAILED = 3;

    //import mode
    const MODE_BULK = 'Bulk';
    const MODE_SINGLE = 'Single';
    const MODE_SINGLE_DELETE = 'Single_Delete';
    const MODE_CONTACT_DELETE = 'Contact_Delete';
    const MODE_SUBSCRIBER_UPDATE = 'Subscriber_Update';
    const MODE_CONTACT_EMAIL_UPDATE = 'Contact_Email_Update';
    const MODE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber_Resubscribed';

    //import type
    const IMPORT_TYPE_GUEST = 'Guest';
    const IMPORT_TYPE_ORDERS = 'Orders';
    const IMPORT_TYPE_CONTACT = 'Contact';
    const IMPORT_TYPE_REVIEWS = 'Reviews';
    const IMPORT_TYPE_WISHLIST = 'Wishlist';
    const IMPORT_TYPE_CONTACT_UPDATE = 'Contact';
    const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
    const IMPORT_TYPE_SUBSCRIBER_UPDATE = 'Subscriber';
    const IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber';
    const IMPORT_TYPE_CART_INSIGHT_CART_PHASE = 'CartInsight';

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
     * @var Config\Json
     */
    private $serializer;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * Importer constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\Importer $importerResource
     * @param ResourceModel\Importer\CollectionFactory $importerCollection
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param Config\Json $serializer
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param array $data
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ResourceModel\Importer $importerResource,
        ResourceModel\Importer\CollectionFactory $importerCollection,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        Config\Json $serializer,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        array $data = [],
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
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
     * @return null
     */
    public function _construct()
    {
        $this->_init(ResourceModel\Importer::class);
    }

    /**
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
     *
     * @return bool
     */
    public function registerQueue(
        $importType,
        $importData,
        $importMode,
        $websiteId,
        $file = false
    ) {
        try {
            if (! empty($importData)) {
                $importData = $this->serializer->serialize($importData);
            }

            if ($file) {
                $this->setImportFile($file);
            }

            if ($importData || $file) {
                $this->setImportType($importType)
                    ->setImportData($importData)
                    ->setWebsiteId($websiteId)
                    ->setImportMode($importMode);

                $this->importerResource->save($this);

                return true;
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        if ($this->serializer->jsonError) {
            $jle = $this->serializer->jsonError;
            $format = "Json error ($jle) for Import type ($importType) / mode ($importMode) for website ($websiteId)";
            $this->helper->log($format);
        }

        return false;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Importer $itemToSave
     *
     * @return null
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveItem($itemToSave)
    {
        $this->importerResource->save($itemToSave);
    }

    /**
     * Get imports marked as importing.
     *
     * @param int $limit
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection|bool
     */
    public function _getImportingItems($limit)
    {
        return $this->importerCollection->create()
            ->getItemsWithImportingStatus($limit);
    }

    /**
     * Get the imports by type.
     *
     * @param string $importType
     * @param string $importMode
     * @param int $limit
     * @param array $websiteIds
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection
     */
    public function _getQueue($importType, $importMode, $limit, $websiteIds)
    {
        return $this->importerCollection->create()
            ->getQueueByTypeAndMode($importType, $importMode, $limit, $websiteIds);
    }
}
