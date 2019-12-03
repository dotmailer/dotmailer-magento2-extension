<?php

namespace Dotdigitalgroup\Email\Model\Coupon;

use Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface;
use \Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class CouponAttributeRepository
 */
class CouponAttribute extends AbstractExtensibleModel implements \Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface
{
    protected function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\CouponAttribute::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getCouponId()
    {
        return $this->getData(self::COUPON_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCouponId($coupon_id)
    {
        return $this->setData(self::COUPON_ID, $coupon_id);
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
