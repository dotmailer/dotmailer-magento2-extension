<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;

interface V2TransactionalConfigInterface
{
    public const HANDLER = 'v2_transactional';
    public const IMPORT_MODE = ImporterModel::MODE_BULK;
    public const METHOD = 'getContactsTransactionalDataImportByImportId';
    public const IMPORT_TYPES = [
        ImporterModel::IMPORT_TYPE_ORDERS,
        ImporterModel::IMPORT_TYPE_REVIEWS,
        ImporterModel::IMPORT_TYPE_WISHLIST,
        ImporterModel::IMPORT_TYPE_CATALOG,
    ];
}
