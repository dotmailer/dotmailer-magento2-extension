<?php

namespace Dotdigitalgroup\Email\Model\Product\ImageType\Context;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Product\ImageType\AbstractTypeProvider;

class AbandonedBrowse extends AbstractTypeProvider
{
    const DEFAULT_ID = 'product_small_image';

    protected $defaultId = self::DEFAULT_ID;

    protected function getConfigPath()
    {
        return Config::XML_PATH_CONNECTOR_IMAGE_TYPES_ABANDONED_BROWSE;
    }
}
