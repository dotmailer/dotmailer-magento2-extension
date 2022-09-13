<?php

namespace Dotdigitalgroup\Email\Model\Connector;

abstract class AbstractConnectorModel
{
    /**
     * Class to array.
     *
     * Returns the public properties of the child class.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Class to array (ignoring empty arrays).
     *
     * This can be used in case a particular schema includes keys
     * that have array values without numeric keys. It protects against
     * pushing data that is incompatible with the insight data schema.
     *
     * @return array
     */
    public function toArrayWithEmptyArrayCheck(): array
    {
        $objectProperties = get_object_vars($this);
        foreach ($objectProperties as $key => $value) {
            if (empty($value) && is_array($value)) {
                unset($objectProperties[$key]);
            }
        }
        return $objectProperties;
    }
}
