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
     * @var \Magento\SalesRule\Model\Coupon\CodegeneratorInterfaceFactory
     */
    public $massGeneratorFactory;

    /**
     * @var \Magento\SalesRule\Api\Data\CouponInterfaceFactory
     */
    public $couponFactory;

    /**
     * @var \Magento\SalesRule\Model\Spi\CouponResourceInterface
     */
    public $coupon;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule
     */
    public $ruleResource;

    /**
     * Initialize resource.
     * @return null
     */
    public function _construct()
    {
        $this->_init('email_campaign', 'id');
    }

    /**
     * Campaign constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\SalesRule\Model\ResourceModel\Rule $rulesResource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\SalesRule\Model\Coupon\CodegeneratorInterfaceFactory $massgeneratorFactory
     * @param \Magento\SalesRule\Api\Data\CouponInterfaceFactory $couponFactory
     * @param \Magento\SalesRule\Model\Spi\CouponResourceInterface $coupon
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context       $context,
        \Magento\SalesRule\Model\ResourceModel\Rule             $rulesResource,
        \Magento\Framework\Stdlib\DateTime\DateTime             $dateTime,
        \Magento\SalesRule\Model\Coupon\CodegeneratorInterfaceFactory    $massgeneratorFactory,
        \Magento\SalesRule\Api\Data\CouponInterfaceFactory               $couponFactory,
        \Magento\SalesRule\Model\Spi\CouponResourceInterface             $coupon,
        \Magento\SalesRule\Model\RuleFactory                    $ruleFactory,
        $connectionName = null
    ) {
        $this->datetime = $dateTime;
        $this->ruleFactory          = $ruleFactory;
        $this->coupon               = $coupon;
        $this->couponFactory        = $couponFactory;
        $this->massGeneratorFactory = $massgeneratorFactory;
        $this->ruleResource         = $rulesResource;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Generate coupon.
     *
     * @param int $couponCodeId
     * @param bool $expireDate
     *
     * @return bool
     */
    public function generateCoupon($couponCodeId, $expireDate = false)
    {
        if ($couponCodeId) {
            $rule = $this->ruleFactory->create();
            $this->ruleResource->load($rule, $couponCodeId);

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
     * @param array $ids
     * @param string $message
     *
     * @return null
     */
    public function setMessage($ids, $message)
    {
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
    }

    /**
     * @param int $sendId
     * @param string $message
     *
     * @return null
     */
    public function setMessageWithSendId($sendId, $message)
    {
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
    }

    /**
     * Set sent.
     *
     * @param int $sendId
     *
     * @return null
     */
    public function setSent($sendId)
    {
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
    }

    /**
     * Set processing
     *
     * @param array $ids
     * @param int $sendId
     *
     * @return null
     */
    public function setProcessing($ids, $sendId)
    {
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
    }

    /**
     * Save item
     *
     * @param \Dotdigitalgroup\Email\Model\Campaign $item
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    public function saveItem($item)
    {
        return parent::save($item);
    }
}
