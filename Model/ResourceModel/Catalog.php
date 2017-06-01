<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Catalog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_catalog', 'id');
    }

    /**
     * Catalog constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->helper = $data;
        parent::__construct($context);
    }

    /**
     * Reset for re-import.
     *
     * @param null $from
     * @param null $to
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetCatalog($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'imported is ?' => new \Zend_Db_Expr('not null')
            ];
        } else {
            $where = $conn->quoteInto(
                'imported is ?',
                new \Zend_Db_Expr('not null')
            );
        }
        try {
            $num = $conn->update(
                $conn->getTableName('email_catalog'),
                [
                    'imported' => new \Zend_Db_Expr('null'),
                    'modified' => new \Zend_Db_Expr('null'),
                ],
                $where
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Set imported in bulk query. If modified true then set modified to null in bulk query.
     *
     * @param      $ids
     * @param bool $modified
     */
    public function setImportedByIds($ids, $modified = false)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $coreResource->getTableName('email_catalog');
            $ids = implode(', ', $ids);

            if ($modified) {
                $coreResource->update(
                    $tableName,
                    [
                        'modified' => 'null',
                        'updated_at' => gmdate('Y-m-d H:i:s'),
                    ],
                    ["product_id IN (?)" => $ids]
                );
            } else {
                $coreResource->update(
                    $tableName,
                    [
                        'imported' => '1',
                        'updated_at' => gmdate(
                            'Y-m-d H:i:s'
                        ),
                    ],
                    ["product_id IN (?)" => $ids]
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Remove product with product id set and no product
     */
    public function removeOrphanProducts()
    {
        $write = $this->getConnection();
        $catalogTable = $write->getTableName('email_catalog');
        $select = $write->select();
        $select->reset()
            ->from(
                ['c' => $catalogTable],
                ['c.product_id']
            )
            ->joinLeft(
                [
                    'e' => $write->getTableName(
                        'catalog_product_entity'
                    ),
                ],
                'c.product_id = e.entity_id'
            )
            ->where('e.entity_id is NULL');

        //delete sql statement
        $deleteSql = $select->deleteFromSelect('c');

        //run query
        $write->query($deleteSql);
    }
}
