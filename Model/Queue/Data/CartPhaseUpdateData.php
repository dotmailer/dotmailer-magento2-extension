<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

class CartPhaseUpdateData
{
    /**
     * @var int
     */
    private $quoteId;

    /**
     * @var int
     */
    private $storeId;

    /**
     * Set quote id.
     *
     * @param int $id
     *
     * @return void
     */
    public function setQuoteId($id)
    {
        $this->quoteId = $id;
    }

    /**
     * Set store id.
     *
     * @param int $id
     *
     * @return void
     */
    public function setStoreId($id)
    {
        $this->storeId = $id;
    }

    /**
     * Get quote id.
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->quoteId;
    }

    /**
     * Get store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }
}
