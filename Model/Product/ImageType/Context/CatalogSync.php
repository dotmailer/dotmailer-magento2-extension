<?php

namespace Dotdigitalgroup\Email\Model\Product\ImageType\Context;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Product\ImageType\AbstractTypeProvider;

class CatalogSync extends AbstractTypeProvider
{
    const DEFAULT_ROLE = 'small_image';

    protected $defaultRole = self::DEFAULT_ROLE;

    protected function getConfigPath()
    {
        return Config::XML_PATH_CONNECTOR_IMAGE_TYPES_CATALOG_SYNC;
    }
}
