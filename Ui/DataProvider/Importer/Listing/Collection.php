<?php

namespace Dotdigitalgroup\Email\Ui\DataProvider\Importer\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * @return $this|Collection|void
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->addFieldToSelect([
            'id',
            'import_id',
            'import_type',
            'import_mode',
            'import_status',
            'message',
            'import_started',
            'import_finished',
            'website_id',
            'created_at',
            'updated_at'
        ]);

        return $this;
    }
}
