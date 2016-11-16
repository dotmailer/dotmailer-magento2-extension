<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Wishlist extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_wishlist', 'id');
    }

    /**
     * Reset the email reviews for reimport.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetWishlists()
    {
        $conn = $this->getConnection('core_write');
        try {
            $num = $conn->update(
                $conn->getTableName('email_wishlist'),
                [
                    'wishlist_imported' => 'null',
                    'wishlist_modified' => 'null',
                ]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }
}
