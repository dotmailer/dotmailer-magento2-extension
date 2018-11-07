<?php

namespace Dotdigitalgroup\Email\Model;

class Campaign extends \Magento\Framework\Model\AbstractModel
{
    //xml path configuration
    const XML_PATH_LOSTBASKET_1_ENABLED = 'abandoned_carts/customers/enabled_1';
    const XML_PATH_LOSTBASKET_2_ENABLED = 'abandoned_carts/customers/enabled_2';
    const XML_PATH_LOSTBASKET_3_ENABLED = 'abandoned_carts/customers/enabled_3';

    const XML_PATH_LOSTBASKET_1_INTERVAL = 'abandoned_carts/customers/send_after_1';
    const XML_PATH_LOSTBASKET_2_INTERVAL = 'abandoned_carts/customers/send_after_2';
    const XML_PATH_LOSTBASKET_3_INTERVAL = 'abandoned_carts/customers/send_after_3';

    const XML_PATH_TRIGGER_1_CAMPAIGN = 'abandoned_carts/customers/campaign_1';
    const XML_PATH_TRIGGER_2_CAMPAIGN = 'abandoned_carts/customers/campaign_2';
    const XML_PATH_TRIGGER_3_CAMPAIGN = 'abandoned_carts/customers/campaign_3';

    const XML_PATH_GUEST_LOSTBASKET_1_ENABLED = 'abandoned_carts/guests/enabled_1';
    const XML_PATH_GUEST_LOSTBASKET_2_ENABLED = 'abandoned_carts/guests/enabled_2';
    const XML_PATH_GUEST_LOSTBASKET_3_ENABLED = 'abandoned_carts/guests/enabled_3';

    const XML_PATH_GUEST_LOSTBASKET_1_INTERVAL = 'abandoned_carts/guests/send_after_1';
    const XML_PATH_GUEST_LOSTBASKET_2_INTERVAL = 'abandoned_carts/guests/send_after_2';
    const XML_PATH_GUEST_LOSTBASKET_3_INTERVAL = 'abandoned_carts/guests/send_after_3';

    const XML_PATH_GUEST_LOSTBASKET_1_CAMPAIGN = 'abandoned_carts/guests/campaign_1';
    const XML_PATH_GUEST_LOSTBASKET_2_CAMPAIGN = 'abandoned_carts/guests/campaign_2';
    const XML_PATH_GUEST_LOSTBASKET_3_CAMPAIGN = 'abandoned_carts/guests/campaign_3';

    //Send Status
    const PENDING = 0;
    const PROCESSING = 1;
    const SENT = 2;
    const FAILED = 3;

    const CAMPAIGN_EVENT_ORDER_REVIEW = 'Order Review';
    const CAMPAIGN_EVENT_LOST_BASKET = 'Lost Basket';

    //error messages
    const SEND_EMAIL_CONTACT_ID_MISSING = 'Error : missing contact id - will try later to send ';

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    public $campaignCollection;

    /**
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Stdlib\DateTime                      $dateTime
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime = $dateTime;
        $this->campaignCollection = $campaignCollection;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Constructor.
     *
     * @return null
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Campaign::class);
    }

    /**
     * Get campaign by quote id.
     *
     * @param int $quoteId
     * @param int $storeId
     *
     * @return $this
     */
    public function loadByQuoteId($quoteId, $storeId)
    {
        $item = $this->campaignCollection->create()
            ->loadByQuoteId($quoteId, $storeId);

        if ($item) {
            return $item;
        } else {
            return $this->setQuoteId($quoteId)
                ->setStoreId($storeId);
        }
    }

    /**
     * Prepare data to be saved to database.
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
}
