<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    public $contactFactory;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_contact', 'email_contact_id');
    }

    /**
     * Contact constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        $connectionName = null
    ) {
    
        $this->contactFactory = $contactFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Remove all contact_id from the table.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteContactIds()
    {
        $conn = $this->getConnection();
        try {
            $num = $conn->update(
                $this->getTable('email_contact'),
                ['contact_id' => new \Zend_Db_Expr('null')],
                $conn->quoteInto(
                    'contact_id is ?',
                    new \Zend_Db_Expr('not null')
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Reset the imported contacts.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetAllContacts()
    {
        try {
            $conn = $this->getConnection();
            $num = $conn->update(
                $conn->getTableName('email_contact'),
                ['email_imported' => new \Zend_Db_Expr('null')],
                $conn->quoteInto(
                    'email_imported is ?',
                    new \Zend_Db_Expr('not null')
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Set all imported subscribers for reimport.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetSubscribers()
    {
        $conn = $this->getConnection();

        try {
            $num = $conn->update(
                $conn->getTableName('email_contact'),
                ['subscriber_imported' => new \Zend_Db_Expr('null')],
                $conn->quoteInto(
                    'subscriber_imported is ?',
                    new \Zend_Db_Expr('not null')
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Unsubscribe a contact from email_contact/newsletter table.
     *
     * @param $data
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function unsubscribe($data)
    {
        if (empty($data)) {
            return 0;
        }
        $write = $this->getConnection();
        $emails = '"' . implode('","', $data) . '"';

        try {
            //un-subscribe from the email contact table.
            $updated = $write->update(
                $this->getMainTable(),
                [
                    'is_subscriber' => new \Zend_Db_Expr('null'),
                    'subscriber_status' => \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED,
                    'suppressed' => '1',
                ],
                "email IN ($emails)"
            );

            // un-subscribe newsletter subscribers
            $write->update(
                $this->getTable('newsletter_subscriber'),
                ['subscriber_status' => \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED],
                "subscriber_email IN ($emails)"
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $updated;
    }

    /**
     * @param $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertGuest($data)
    {
        $contacts = array_keys($data);
        $contactModel = $this->contactFactory->create();
        $emailsExistInTable = $contactModel->getCollection()
            ->addFieldToFilter('email', ['in' => $contacts])
            ->getColumnValues('email');

        $guests = array_diff_key($data, array_flip($emailsExistInTable));

        if (! empty($guests)) {
            try {
                $write = $this->getConnection();
                $write->insertMultiple($this->getMainTable(), $guests);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }
    }

    /**
     * Set suppressed for contact ids.
     *
     * @param array $suppressedContactIds
     *
     * @return int
     */
    public function setContactSuppressedForContactIds($suppressedContactIds)
    {
        if (empty($suppressedContactIds)) {
            return 0;
        }
        $conn = $this->getConnection();
        //update suppressed for contacts
        $updated = $conn->update(
            $this->getMainTable(),
            ['suppressed' => 1],
            ['email_contact_id IN(?)' => $suppressedContactIds]
        );

        return $updated;
    }

    /**
     * Update subscriber imported.
     *
     * @param $ids array
     * @return int
     */
    public function updateSubscribers($ids)
    {
        if (empty($ids)) {
            return 0;
        }
        $write = $this->getConnection();
        $ids = implode(', ', $ids);
        //update subscribers imported
        $updated = $write->update($this->getMainTable(), ['subscriber_imported' => 1], "email_contact_id IN ($ids)");

        return $updated;
    }
}
