<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Wishlist as WishlistResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;

class Wishlist extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var WishlistResource
     */
    private $wishlistResource;

    /**
     * Wishlist constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param WishlistResource $wishlistResource
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        WishlistResource $wishlistResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime = $dateTime;
        $this->wishlistResource = $wishlistResource;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Constructor.
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Wishlist::class);
    }

    /**
     * Prepare data to be saved to database.
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->isObjectNew() && !$this->getCreatedAt()) {
            $this->setCreatedAt($this->dateTime->formatDate(true));
        }
        $this->setUpdatedAt($this->dateTime->formatDate(true));

        return $this;
    }

    /**
     * Reset.
     *
     * @param string|null $from
     * @param string|null $to
     * @return int
     */
    public function reset(?string $from = null, ?string $to = null)
    {
        return $this->wishlistResource->resetWishlists($from, $to);
    }
}
