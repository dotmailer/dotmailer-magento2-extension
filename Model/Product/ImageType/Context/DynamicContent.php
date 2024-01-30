<?php

namespace Dotdigitalgroup\Email\Model\Product\ImageType\Context;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Product\ImageType\AbstractTypeProvider;

class DynamicContent extends AbstractTypeProvider
{
    /**
     * Get the config path for the image type
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return Config::XML_PATH_CONNECTOR_IMAGE_TYPES_DYNAMIC_CONTENT;
    }
}
