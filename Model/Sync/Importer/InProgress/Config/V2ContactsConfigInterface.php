<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\InProgress\Config;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;

interface V2ContactsConfigInterface
{
    public const HANDLER = 'v2_contacts';
    public const IMPORT_MODE = ImporterModel::MODE_BULK;
    public const METHOD = 'getContactsImportByImportId';
    public const IMPORT_TYPES = [
        ImporterModel::IMPORT_TYPE_CONTACT,
        ImporterModel::IMPORT_TYPE_CONSENT,
        ImporterModel::IMPORT_TYPE_CUSTOMER,
        ImporterModel::IMPORT_TYPE_GUEST,
        ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
    ];
}
