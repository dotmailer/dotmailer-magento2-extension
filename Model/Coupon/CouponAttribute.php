<?php

namespace Dotdigitalgroup\Email\Model\Coupon;

use DateTime;
use Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface;
use Dotdigitalgroup\Email\Model\DateTimeFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class CouponAttributeRepository
 */
class CouponAttribute extends AbstractExtensibleModel implements CouponAttributeInterface
{
    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @param DateTimeFactory $dateTimeFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        DateTimeFactory $dateTimeFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTimeFactory = $dateTimeFactory;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\CouponAttribute::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getCouponId()
    {
        return $this->getData(self::SALESRULE_COUPON_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCouponId($coupon_id)
    {
        return $this->setData(self::SALESRULE_COUPON_ID, $coupon_id);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiresAt()
    {
        return $this->getData(self::EXPIRES_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setExpiresAt(string $expiresAt)
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    /**
     * @return DateTime|null
     */
    public function getExpiresAtDate()
    {
        $expiresAt = $this->getExpiresAt();
        if ($expiresAt) {
            return $this->dateTimeFactory->create(['time' => $expiresAt])
                ->getUtcDate();
        }
        return null;
    }

    /**
     * @param CouponAttributeInterface $couponAttribute
     * @return CouponAttribute
     */
    public function setExtensionAttributes(CouponAttributeInterface $couponAttribute)
    {
        return $this->_setExtensionAttributes($couponAttribute);
    }

    /**
     * @return \Magento\Framework\Api\ExtensionAttributesInterface
     */
    public function getExtensionAttributes()
    {
        return parent::_getExtensionAttributes();
    }
}
