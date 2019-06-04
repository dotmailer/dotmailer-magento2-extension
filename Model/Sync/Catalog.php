<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Sync account TD for catalog.
 */
class Catalog implements SyncInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var mixed
     */
    private $start;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $catalogResourceFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory
     */
    private $catalogSyncFactory;

    /**
     * Catalog constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogResourceFactory
     * @param \Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory $catalogSyncFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogResourceFactory,
        Catalog\CatalogSyncFactory $catalogSyncFactory
    ) {
        $this->helper = $helper;
        $this->catalogResourceFactory = $catalogResourceFactory;
        $this->catalogSyncFactory = $catalogSyncFactory;
    }

    /**
     * Catalog sync.
     *
     * @param \DateTime|null $from
     * @return array
     */
    public function sync(\DateTime $from = null)
    {
        $response    = ['success' => true, 'message' => 'Done.'];
        $this->start = microtime(true);

        $countProducts = $this->syncCatalog();

        if ($countProducts) {
            $message = '----------- Catalog sync ----------- : ' .
                gmdate('H:i:s', microtime(true) - $this->start) .
                ', Total synced = ' . $countProducts;
            $this->helper->log($message);
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Sync product catalogs
     *
     * @return int
     */
    public function syncCatalog()
    {
        try {
            //remove product with product id set and no product
            $this->catalogResourceFactory->create()
                ->removeOrphanProducts();

            return $this->catalogSyncFactory->create()->sync();

        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
