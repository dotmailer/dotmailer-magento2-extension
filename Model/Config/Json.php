<?php

namespace Dotdigitalgroup\Email\Model\Config;

/**
 * Class for serializing data to json string and unserializing json string to data.
 *
 */
class Json
{
    /**
     * @var null|string
     */
    public $jsonError;

    /**
     * @param array|bool|float|int|null|string $data
     * @return string
     */
    public function serialize($data)
    {
        $json = json_encode($data);
        $this->jsonError = null;
        if (json_last_error_msg() != "No error") {
            $this->jsonLastError = json_last_error_msg();
        }
        return $json;
    }

    /**
     * @param string $string
     * @return mixed
     */
    public function unserialize($string)
    {
        $data = json_decode($string, true);
        $this->jsonError = null;
        if (json_last_error_msg() != "No error") {
            $this->jsonLastError = json_last_error_msg();
        }
        return $data;
    }
}
