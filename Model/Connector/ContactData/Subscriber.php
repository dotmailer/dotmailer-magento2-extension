<?php

namespace Dotdigitalgroup\Email\Model\Connector\ContactData;

use Dotdigitalgroup\Email\Model\Connector\ContactData;

class Subscriber extends ContactData
{
    /**
     * Get opt in type.
     *
     * If we are requesting a value for this column, it means 'Need to confirm'
     * is switched on in Magento.
     */
    public function getOptInType()
    {
        return 'Double';
    }
}
