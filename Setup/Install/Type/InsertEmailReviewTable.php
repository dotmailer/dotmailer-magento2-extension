<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

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
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from([
                'review' => $this->resourceConnection->getTableName('review'),
            ], [
                'review_id' => 'review.review_id',
                'created_at' => 'review.created_at',
            ])
            ->joinInner(
                ['review_detail' => $this->resourceConnection->getTableName('review_detail')],
                'review_detail.review_id = review.review_id',
                ['store_id' => 'review_detail.store_id', 'customer_id' => 'review_detail.customer_id']
            )
            ->where(
                $this->resourceConnection
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
