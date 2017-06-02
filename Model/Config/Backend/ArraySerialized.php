<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend;

class ArraySerialized extends \Dotdigitalgroup\Email\Model\Config\Backend\Serialized
{
    /**
     * @return \Dotdigitalgroup\Email\Model\Config\Backend\Serialized
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
