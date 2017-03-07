<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_contact', 'email_contact_id');
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
     * Unsubscribe a contact.
     *
     * @param $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function unsubscribe($data)
    {
        $write = $this->getConnection();
        $emails = '"' . implode('","', $data) . '"';

        try {
            //un-subscribe from the email contact table.
            $write->update(
                $this->getMainTable(),
                [
                    'is_subscriber' => new \Zend_Db_Expr('null'),
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
    }

    /**
     * @param $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insert($data)
    {
        if (!empty($data)) {
            try {
                $write = $this->getConnection();
                $write->insertMultiple($this->getMainTable(), $data);
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
        $conn = $this->getConnection();

        return $conn->update(
            $this->getMainTable(),
            ['suppressed' => 1],
            ['email_contact_id IN(?)' => $suppressedContactIds]
        );
    }

    /**
     * Update subscriber imported
     *
     * @param $subscribers
     */
    public function updateSubscribers($subscribers)
    {
        $write = $this->getConnection();
        $ids = implode(', ', $subscribers);
        $write->update(
            $this->getMainTable(),
            ['subscriber_imported' => 1],
            "email_contact_id IN ($ids)"
        );
    }
}
