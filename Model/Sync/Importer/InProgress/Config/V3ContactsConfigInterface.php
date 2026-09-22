<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;

interface V3ContactsConfigInterface
{
    public const HANDLER = 'v3_contacts';
    public const IMPORT_MODE = ImporterModel::MODE_BULK_JSON;
    public const RESOURCE = 'contacts';
    public const METHOD = 'getImportById';
    public const IMPORT_TYPES = [
        ImporterModel::IMPORT_TYPE_CONSENT,
        ImporterModel::IMPORT_TYPE_CUSTOMER,
        ImporterModel::IMPORT_TYPE_GUEST,
        ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
    ];
}
