<?php

namespace Dotdigitalgroup\Email\Model\Sync\PendingContact\Type;

interface TypeProviderInterface
{
    public function getCollectionFactory();

    public function getResourceModel();
}
