<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend\ImageTypes;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\ImageType\ImageTypeService;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class ImageRoleProcessor extends \Magento\Framework\App\Config\Value
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ImageTypeService
     */
    private $imageTypeService;

    /**
     * @var Catalog
     */
    private $catalogResource;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var bool
     */
    private $isCatalogReset = false;

    /**
     * @param Logger $logger
     * @param ImageTypeService $imageTypeService
     * @param Catalog $catalogResource
     * @param SerializerInterface $serializer
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        ImageTypeService $imageTypeService,
        Catalog $catalogResource,
        SerializerInterface $serializer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->imageTypeService = $imageTypeService;
        $this->catalogResource = $catalogResource;
        $this->serializer = $serializer;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Before save.
     */
    public function beforeSave()
    {
        if (!$this->_isValueChanged()) {
            $this->setValue($this->getOldValue());
            return parent::beforeSave();
        }

        $imageId = $this->getValue();

        if ($imageId !== '0') {
            $newValue = [
                'id' => $imageId,
                'role' => $this->getImageRoleById($imageId)
            ];
            $this->setValue($this->serializer->serialize($newValue));
        }

        if ($this->getData('path') === Config::XML_PATH_CONNECTOR_IMAGE_TYPES_CATALOG_SYNC &&
            !$this->isCatalogReset
        ) {
            $this->catalogResource->resetCatalog();
            $this->isCatalogReset = true;
            $this->logger->info('Catalog sync image type changed, catalog data reset.');
        }

        return parent::beforeSave();
    }

    /**
     * After load.
     *
     * @return void
     */
    protected function _afterLoad()
    {
        try {
            $unserialized = $this->serializer->unserialize($this->getValue());
        } catch (\InvalidArgumentException $e) {
            $this->setValue(false);
            return;
        }
        $this->setValue(empty($unserialized) || !isset($unserialized['id']) ? false : $unserialized['id']);
    }

    /**
     * Get image role by id.
     *
     * @param string $imageId
     * @return string
     */
    private function getImageRoleById($imageId)
    {
        $viewImages = $this->imageTypeService->getViewImages();
        return $viewImages[$imageId]['type'];
    }

    /**
     * Is value changed.
     *
     * The native isValueChanged() method always returns true,
     * because the value is presented as a string but stored as serialized JSON.
     *
     * @return bool
     */
    private function _isValueChanged()
    {
        try {
            $oldValue = $this->serializer->unserialize($this->getOldValue());
            $oldValueId = $oldValue['id'] ?? $oldValue;
        } catch (\InvalidArgumentException $e) {
            $oldValueId = $this->getOldValue();
        }

        return is_array($oldValueId) || $this->getValue() !== (string) $oldValueId;
    }
}
