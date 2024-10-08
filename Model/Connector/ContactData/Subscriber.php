<?php

namespace Dotdigitalgroup\Email\Model\Connector\ContactData;

use Dotdigitalgroup\Email\Model\Connector\ContactData;

/**
 * @deprecated This class will be removed.
 * @see \Dotdigitalgroup\Email\Model\Connector\ContactData;
 */
class Subscriber extends ContactData
{
    /**
     * Get opt in type.
     *
     * If we are requesting a value for this column, it means 'Need to confirm'
     * is switched on in Magento.
     *
     * @deprecated OptInType is not a data field.
     * @see \Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder
     */
    public function getOptInType()
    {
        return 'Double';
    }
}
