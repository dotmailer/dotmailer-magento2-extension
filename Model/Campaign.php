<?php

namespace Dotdigitalgroup\Email\Model;

class Campaign extends \Magento\Framework\Model\AbstractModel
{
    //xml path configuration
    const XML_PATH_LOSTBASKET_1_ENABLED = 'dotdigitalgroup_abandoned_carts/customers/enabled_1';
    const XML_PATH_LOSTBASKET_2_ENABLED = 'dotdigitalgroup_abandoned_carts/customers/enabled_2';
    const XML_PATH_LOSTBASKET_3_ENABLED = 'dotdigitalgroup_abandoned_carts/customers/enabled_3';

    const XML_PATH_LOSTBASKET_1_INTERVAL = 'dotdigitalgroup_abandoned_carts/customers/send_after_1';
    const XML_PATH_LOSTBASKET_2_INTERVAL = 'dotdigitalgroup_abandoned_carts/customers/send_after_2';
    const XML_PATH_LOSTBASKET_3_INTERVAL = 'dotdigitalgroup_abandoned_carts/customers/send_after_3';

    const XML_PATH_TRIGGER_1_CAMPAIGN = 'dotdigitalgroup_abandoned_carts/customers/campaign_1';
    const XML_PATH_TRIGGER_2_CAMPAIGN = 'dotdigitalgroup_abandoned_carts/customers/campaign_2';
    const XML_PATH_TRIGGER_3_CAMPAIGN = 'dotdigitalgroup_abandoned_carts/customers/campaign_3';

    const XML_PATH_GUEST_LOSTBASKET_1_ENABLED = 'dotdigitalgroup_abandoned_carts/guests/enabled_1';
    const XML_PATH_GUEST_LOSTBASKET_2_ENABLED = 'dotdigitalgroup_abandoned_carts/guests/enabled_2';
    const XML_PATH_GUEST_LOSTBASKET_3_ENABLED = 'dotdigitalgroup_abandoned_carts/guests/enabled_3';

    const XML_PATH_GUEST_LOSTBASKET_1_INTERVAL = 'dotdigitalgroup_abandoned_carts/guests/send_after_1';
    const XML_PATH_GUEST_LOSTBASKET_2_INTERVAL = 'dotdigitalgroup_abandoned_carts/guests/send_after_2';
    const XML_PATH_GUEST_LOSTBASKET_3_INTERVAL = 'dotdigitalgroup_abandoned_carts/guests/send_after_3';

    const XML_PATH_GUEST_LOSTBASKET_1_CAMPAIGN = 'dotdigitalgroup_abandoned_carts/guests/campaign_1';
    const XML_PATH_GUEST_LOSTBASKET_2_CAMPAIGN = 'dotdigitalgroup_abandoned_carts/guests/campaign_2';
    const XML_PATH_GUEST_LOSTBASKET_3_CAMPAIGN = 'dotdigitalgroup_abandoned_carts/guests/campaign_3';

    //Send Status
    const PENDING = 0;
    const PROCESSING = 1;
    const SENT = 2;
    const FAILED = 3;

    //error messages
    const SEND_EMAIL_CONTACT_ID_MISSING = 'Error : missing contact id - will try later to send ';

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Stdlib\DateTime                      $dateTime
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime = $dateTime;
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
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\ResourceModel\Campaign');
    }

    /**
     * Get campaign by quote id.
     *
     * @param int $quoteId
     * @param int $storeId
     *
     * @return mixed
     */
    public function loadByQuoteId($quoteId, $storeId)
    {
        $item = $this->getCollection()
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
