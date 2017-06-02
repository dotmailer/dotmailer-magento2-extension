<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Campaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime;
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    public $ruleFactory;
    /**
     * @var \Magento\SalesRule\Model\Coupon\MassgeneratorFactory
     */
    public $massGeneratorFactory;
    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    public $couponFactory;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon
     */
    public $coupon;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_campaign', 'id');
    }

    /**
     * Campaign constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context    $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime          $dateTime
     * @param \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $massgeneratorFactory
     * @param \Magento\SalesRule\Model\CouponFactory               $couponFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon        $coupon
     * @param \Magento\SalesRule\Model\RuleFactory                 $ruleFactory
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context       $context,
        \Magento\Framework\Stdlib\DateTime\DateTime             $dateTime,
        \Magento\SalesRule\Model\Coupon\MassgeneratorFactory    $massgeneratorFactory,
        \Magento\SalesRule\Model\CouponFactory                  $couponFactory,
        \Magento\SalesRule\Model\ResourceModel\Coupon           $coupon,
        \Magento\SalesRule\Model\RuleFactory                    $ruleFactory,
        $connectionName = null
    ) {
        $this->datetime = $dateTime;
        $this->ruleFactory          = $ruleFactory;
        $this->coupon               = $coupon;
        $this->couponFactory        = $couponFactory;
        $this->massGeneratorFactory = $massgeneratorFactory;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Generate coupon
     *
     * @param $couponCodeId
     * @param bool $expireDate
     * @return bool
     */
    public function generateCoupon($couponCodeId, $expireDate = false)
    {
        if ($couponCodeId) {
            $rule = $this->ruleFactory->create();
            $rule = $rule->getResource()->load($rule, $couponCodeId);

            $generator = $this->massGeneratorFactory->create();
            $generator->setFormat(
                \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC
            );
            $generator->setRuleId($couponCodeId);
            $generator->setUsesPerCoupon(1);
            $generator->setDash(3);
            $generator->setLength(9);
            $generator->setPrefix('DOT-');
            $generator->setSuffix('');

            //set the generation settings
            $rule->setCouponCodeGenerator($generator);
            $rule->setCouponType(
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
            );

            //generate the coupon
            $coupon = $rule->acquireCoupon();
            $couponCode = $coupon->getCode();

            //save the type of coupon
            /** @var \Magento\SalesRule\Model\Coupon $couponModel */
            $couponModel = $this->couponFactory->create()
                ->loadByCode($couponCode);
            $couponModel->setType(
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
            )->setGeneratedByDotmailer(1);

            if ($expireDate) {
                $couponModel->setExpirationDate($expireDate);
            } elseif ($rule->getToDate()) {
                $couponModel->setExpirationDate($rule->getToDate());
            }

            $this->coupon->save($couponModel);

            return $couponCode;
        }

        return false;
    }

    /**
     * Set error message
     *
     * @param $ids
     * @param $message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setMessage($ids, $message)
    {
        try {
            $ids = implode(", ", $ids);
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                [
                    'message' => $message,
                    'send_status' => \Dotdigitalgroup\Email\Model\Campaign::FAILED,
                    'sent_at' =>  $this->datetime->gmtDate()
                ],
                ["id in (?)" => $ids]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Set error message on given send id
     *
     * @param $sendId
     * @param $message
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setMessageWithSendId($sendId, $message)
    {
        try {
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                [
                    'message' => $message,
                    'send_status' => \Dotdigitalgroup\Email\Model\Campaign::FAILED,
                    'sent_at' => $this->datetime->gmtDate()
                ],
                ['send_id = ?' => $sendId]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Set sent
     *
     * @param $sendId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setSent($sendId)
    {
        try {
            $bind = [
                'send_status' => \Dotdigitalgroup\Email\Model\Campaign::SENT,
                'sent_at' => $this->datetime->gmtDate()
            ];
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                $bind,
                ['send_id = ?' => $sendId]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Set processing
     *
     * @param $ids
     * @param $sendId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setProcessing($ids, $sendId)
    {
        try {
            $ids = implode(', ', $ids);
            $bind = [
                'send_status' => \Dotdigitalgroup\Email\Model\Campaign::PROCESSING,
                'send_id' => $sendId
            ];
            $conn = $this->getConnection();
            $conn->update(
                $this->getMainTable(),
                $bind,
                ["id in (?)" => $ids]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Save item
     *
     * @param $item
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    public function saveItem($item)
    {
        return parent::save($item);
    }
}
