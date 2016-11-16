<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Review extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_review', 'id');
    }

    /**
     * Reset the email reviews for reimport.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetReviews()
    {
        $conn = $this->getConnection();
        try {
            $num = $conn->update(
                $conn->getTableName('email_review'),
                ['review_imported' => 'null'],
                $conn->quoteInto(
                    'review_imported is ?',
                    'not null'
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }
}
