<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

class CatalogService
{
    /**
     * @var bool
     */
    private $isCatalogUpdated = false;

    /**
     *
     */
    public function setIsCatalogUpdated()
    {
        $this->isCatalogUpdated = true;
    }

    /**
     * @return bool
     */
    public function isCatalogUpdated()
    {
        return $this->isCatalogUpdated;
    }
}
