<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Ui\DataProvider\Importer;

use Dotdigitalgroup\Email\Model\Importer;
use Magento\Framework\Api\Filter;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class ImporterReportDataProvider extends DataProvider
{
    /**
     * Apply filter override for BULK and BULK_JSON merge
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        if ($filter->getField() === 'import_mode' && $filter->getValue() === Importer::MODE_BULK) {
            $newFilter = $this->filterBuilder
                ->setField($filter->getField())
                ->setConditionType('in')
                ->setValue([Importer::MODE_BULK, Importer::MODE_BULK_JSON])
                ->create();
            parent::addFilter($newFilter);
        } else {
            parent::addFilter($filter);
        }
    }

    /**
     * Merge BULK_JSON into BULK for user interface display
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        if (isset($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['import_mode']) && $item['import_mode'] === Importer::MODE_BULK_JSON) {
                    $item['import_mode'] = Importer::MODE_BULK;
                }
            }
        }

        return $data;
    }
}
