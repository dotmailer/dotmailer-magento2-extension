<?php

namespace Dotdigitalgroup\Email\Model;

class Catalog extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var ResourceModel\Catalog
     */
    public $catalogResource;
    
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    private $catalogCollection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * Catalog constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param ResourceModel\Catalog\CollectionFactory $catalogCollection
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory  $catalogCollection,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->catalogResource = $catalogResource;
        $this->catalogCollection = $catalogCollection;
        $this->dateTime = $dateTime;
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
     * @return null
     */
    public function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Catalog::class);
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
     * Load by product id.
     *
     * @param int $productId
     *
     * @return mixed
     */
    public function loadProductById($productId)
    {
        $collection = $this->catalogCollection->create()
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1);

        return $collection->getFirstItem();
    }
}
