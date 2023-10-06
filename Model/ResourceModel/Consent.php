<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class Consent extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_CONTACT_CONSENT_TABLE, 'id');
    }

    /**
     * Set consent record imported by ids.
     *
     * @param array $consentIds
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setConsentRecordsImportedByIds(array $consentIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['consent_imported' => 1],
            [
                "id IN (?)" => $consentIds
            ]
        );
    }
}
