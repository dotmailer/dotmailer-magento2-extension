<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\Schema;

class Consent extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var Consent\CollectionFactory
     */
    public $consentCollectionFactory;

    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_CONTACT_CONSENT_TABLE, 'id');
    }

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Model\ResourceModel\Consent\CollectionFactory $consentCollectionFactory,
        $connectionName = null
    ) {
        $this->consentCollectionFactory = $consentCollectionFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Delete Consent for contact.
     *
     * @param array $emails
     * @return array
     */
    public function deleteConsentByEmails($emails)
    {
        if (empty($emails)) {
            return [];
        }
        $collection = $this->consentCollectionFactory->create();
        $collection->getSelect()
            ->joinInner(
                ['c' => $this->getTable(Schema::EMAIL_CONTACT_TABLE)],
                "c.email_contact_id = main_table.email_contact_id",
                []
            );

        $collection->addFieldToFilter('c.email', ['in' => $emails]);

        return $collection->walk('delete');
    }
}
