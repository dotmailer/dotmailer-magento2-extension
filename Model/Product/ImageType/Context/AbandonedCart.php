<?php

namespace Dotdigitalgroup\Email\Model\Product\ImageType\Context;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Product\ImageType\AbstractTypeProvider;

class AbandonedCart extends AbstractTypeProvider
{
    const DEFAULT_ROLE = 'thumbnail';

    protected $defaultRole = self::DEFAULT_ROLE;

    protected function getConfigPath()
    {
        return Config::XML_PATH_CONNECTOR_IMAGE_TYPES_ABANDONED_CART;
    }
}
