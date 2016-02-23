<?php

namespace Dotdigitalgroup\Email\Model\Resource;


class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('email_contact', 'email_contact_id');
    }


    /**
     * Remove all contact_id from the table.
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteContactIds()
    {

        $conn = $this->getConnection();
        try {
            $num = $conn->update($this->getTable('email_contact'),
                array('contact_id' => new \Zend_Db_Expr('null')),
                $conn->quoteInto('contact_id is ?',
                    new \Zend_Db_Expr('not null'))
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetAllContacts()
    {
        try {
            $conn = $this->getConnection();
            $num  = $conn->update($conn->getTableName('email_contact'),
                array('email_imported' => new \Zend_Db_Expr('null')),
                $conn->quoteInto('email_imported is ?',
                    new \Zend_Db_Expr('not null'))
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetSubscribers()
    {

        $conn = $this->getConnection();

        try {
            $num = $conn->update(
                $conn->getTableName('email_contact'),
                array('subscriber_imported' => new \Zend_Db_Expr('null')),
                $conn->quoteInto('subscriber_imported is ?',
                    new \Zend_Db_Expr('not null')));

        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    public function unsubscribe($data)
    {
        $write  = $this->getConnection();
        $emails = '"' . implode('","', $data) . '"';

        try {
            //un-subscribe from the email contact table.
            $write->update(
                $this->getMainTable(),
                array(
                    'is_subscriber' => new \Zend_Db_Expr('null'),
                    'suppressed'    => '1'
                ),
                "email IN ($emails)"
            );

            // un-subscribe newsletter subscribers
            $write->update(
                $this->getTable('newsletter_subscriber'),
                array('subscriber_status' => \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED),
                "subscriber_email IN ($emails)"
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}