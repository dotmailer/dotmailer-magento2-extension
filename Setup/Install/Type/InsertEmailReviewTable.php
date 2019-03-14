<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\Schema;

class InsertEmailReviewTable extends AbstractDataMigration implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_REVIEW_TABLE;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->installer
            ->getConnection()
            ->select()
            ->from([
                'review' => $this->installer->getTable('review'),
            ], [
                'review_id' => 'review.review_id',
                'created_at' => 'review.created_at',
            ])
            ->joinInner(
                ['review_detail' => $this->installer->getTable('review_detail')],
                'review_detail.review_id = review.review_id',
                ['store_id' => 'review_detail.store_id', 'customer_id' => 'review_detail.customer_id']
            )
            ->where(
                $this->installer
                    ->getConnection()
                    ->prepareSqlCondition('review_detail.customer_id', [
                        'notnull' => true
                    ])
            )
            ->order('review.review_id')
        ;
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'review_id',
            'created_at',
            'store_id',
            'customer_id',
        ];
    }

}