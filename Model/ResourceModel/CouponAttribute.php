<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class CouponAttribute extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_COUPON_TABLE, 'id');
    }

    /**
     * Fetch the most recently issued coupon for an email address on a given sales rule.
     *
     * Returns only the three columns the request processor actually consumes, ordered
     * deterministically and bounded to a single row. The previous implementation loaded every
     * matching attribute row (and every column of both tables) into hydrated models and then took
     * getLastItem(), whose selection is undefined for an unordered collection.
     *
     * Ordering is by the auto-increment salesrule_coupon_id (the recency proxy for the coupon
     * itself), with the attribute id as a tie-break.
     *
     * No expiry predicate is applied - is_expired / is_used drive four distinct branches in
     * DotdigitalCouponRequestProcessor::handleExistingCoupon(), so filtering here would change
     * behaviour.
     *
     * @param int $ruleId
     * @param string $email
     * @return array|null
     */
    public function getLatestForEmailAndRule(int $ruleId, string $email): ?array
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(
                ['eca' => $this->getTable(Schema::EMAIL_COUPON_TABLE)],
                ['expires_at']
            )
            ->join(
                ['sc' => $this->getTable('salesrule_coupon')],
                'sc.coupon_id = eca.salesrule_coupon_id',
                ['code', 'times_used']
            )
            ->where('eca.email = ?', $email)
            ->where('sc.rule_id = ?', $ruleId)
            ->order(['eca.salesrule_coupon_id DESC', 'eca.id DESC'])
            ->limit(1);

        $row = $connection->fetchRow($select);

        return $row ?: null;
    }
}
