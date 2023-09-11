<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend;

class ArraySerialized extends Serialized
{
    /**
     * Unset array element with '__empty' key
     *
     * @return Serialized
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            unset($value['__empty']);
        }
        $this->setValue($value);

        return parent::beforeSave();
    }
}
