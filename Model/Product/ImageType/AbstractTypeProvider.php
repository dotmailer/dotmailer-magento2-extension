<?php

namespace Dotdigitalgroup\Email\Model\Product\ImageType;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

abstract class AbstractTypeProvider
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var array
     */
    private $type = [];

    /**
     * @var string
     */
    protected $defaultId;

    /**
     * @var string
     */
    protected $defaultRole;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * AbstractRoleProvider constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeRepositoryInterface $attributeRepository,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->attributeRepository = $attributeRepository;
        $this->serializer = $serializer;
    }

    /**
     * @param int $websiteId
     * @return array
     */
    public function getImageType($websiteId)
    {
        if (empty($this->type)) {
            $this->setImageType($websiteId);
        }

        return $this->type;
    }

    /**
     * @param int $websiteId
     */
    private function setImageType($websiteId)
    {
        $imageType = $this->scopeConfig->getValue(
            $this->getConfigpath(),
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        if ($imageType === '0') {
            $this->type['id'] = $this->getDefaultId();
            $this->type['role'] = $this->getDefaultRole();
            return;
        }

        try {
            $this->type = $this->serializer->unserialize($imageType);
        } catch (\InvalidArgumentException $e) {
            $this->type['id'] = $this->getDefaultId();
            $this->type['role'] = $this->getDefaultRole();
        }
    }

    /**
     * @return string
     */
    private function getDefaultId()
    {
        return $this->defaultId;
    }

    /**
     * @return string
     */
    private function getDefaultRole()
    {
        return $this->defaultRole;
    }

    abstract protected function getConfigpath();
}
