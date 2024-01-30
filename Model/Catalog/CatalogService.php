<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

class CatalogService
{
    /**
     * @var bool
     */
    private $isCatalogUpdated = false;

    /**
     * Set catalog updated flag.
     */
    public function setIsCatalogUpdated()
    {
        $this->isCatalogUpdated = true;
    }

    /**
     * Get catalog updated flag.
     *
     * @return bool
     */
    public function isCatalogUpdated()
    {
        return $this->isCatalogUpdated;
    }
}
