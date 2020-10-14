<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend\ImageTypes;

use Dotdigitalgroup\Email\Model\Product\ImageType\ImageTypeService;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;

class ImageRoleProcessor extends \Magento\Framework\App\Config\Value
{
    /**
     * @var ImageTypeService
     */
    private $imageTypeService;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ImageTypeService $imageTypeService
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\Serialize\SerializerInterface|null $serializer
     * @param array $data
     */
    public function __construct(
        ImageTypeService $imageTypeService,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Serialize\SerializerInterface $serializer = null,
        array $data = []
    ) {
        $this->imageTypeService = $imageTypeService;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
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
     * @throws \Magento\Framework\Exception\LocalizedException
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

        return parent::beforeSave();
    }

    /**
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
        $this->setValue(empty($unserialized) ? false : $unserialized['id']);
    }

    /**
     * @param $imageId
     * @return string
     */
    private function getImageRoleById($imageId)
    {
        $viewImages = $this->imageTypeService->getViewImages();
        return $viewImages[$imageId]['type'];
    }

    /**
     * The native isValueChanged() method always returns true,
     * because the value is presented as a string but stored as serialized JSON.
     *
     * @return bool
     */
    private function _isValueChanged()
    {
        try {
            $oldValue = $this->serializer->unserialize($this->getOldValue());
            $oldValueId = isset($oldValue['id']) ? $oldValue['id'] : $oldValue;
        } catch (\InvalidArgumentException $e) {
            $oldValueId = $this->getOldValue();
        }

        return $this->getValue() !== (string) $oldValueId;
    }
}
