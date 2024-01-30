<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Backend;

use Magento\Framework\App\Config\Value;

class TextValueTrimmer extends Value
{
    /**
     * Before save.
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $trimmedValue = trim($value);
        $this->setValue($trimmedValue);

        return parent::beforeSave();
    }
}
