<?php

namespace Dotdigitalgroup\Email\Model\Config;

/**
 * Class for serializing data to json string and unserializing json string to data.
 *
 */
class Json
{
    /**
     * @param array|bool|float|int|null|string $data
     * @return string
     */
    public function serialize($data)
    {
        return json_encode($data);
    }

    /**
     * @param string $string
     * @return mixed
     */
    public function unserialize($string)
    {
        return json_decode($string, true);
    }
}
