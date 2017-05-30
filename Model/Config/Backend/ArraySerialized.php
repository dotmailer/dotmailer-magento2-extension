<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend;

class ArraySerialized extends \Dotdigitalgroup\Email\Model\Config\Backend\Serialized
{
    /**
     * Unset array element with '__empty' key
     *
     * @return $this
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
