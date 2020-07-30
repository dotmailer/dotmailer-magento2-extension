<?php

namespace Dotdigitalgroup\Email\Model\SalesRule;

use Dotdigitalgroup\Email\Model\Coupon\CouponAttributeCollection;
use Dotdigitalgroup\Email\Test\Integration\LoadsSaleRule;
use Dotdigitalgroup\Email\Test\Integration\RedeemsCoupons;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\SalesRule\Helper\Coupon;
use Magento\SalesRule\Model\CouponRepository;
use Magento\SalesRule\Model\ResourceModel\Coupon\Collection as CouponCollection;
use Magento\TestFramework\ObjectManager;

include __DIR__ . '/../../_files/salesrule.php';

/**
 * @magentoDbIsolation enabled
 */
class DotdigitalCouponGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use RedeemsCoupons;
    use LoadsSaleRule;

    /**
     * @var DotdigitalCouponGenerator
     */
    private $codeGenerator;

    /**
     * @var CouponAttributeCollection
     */
    private $couponAttributeCollection;

    /**
     * @var CouponCollection
     */
    private $salesRuleCouponCollection;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var DotdigitalCouponRequestProcessor
     */
    private $couponRequestProcessor;

    public function setUp() :void
    {
        $objectManager = ObjectManager::getInstance();

        $this->couponRequestProcessor = $objectManager->create(DotdigitalCouponRequestProcessor::class);
        $this->codeGenerator = $objectManager->create(DotdigitalCouponGenerator::class);
        $this->couponAttributeCollection = $objectManager->create(CouponAttributeCollection::class);
        $this->salesRuleCouponCollection = $objectManager->create(CouponCollection::class);
        $this->localeDate = $objectManager->create(TimezoneInterface::class);

        $this->loadSalesRule();
    }

    public function testGenerateCoupon()
    {
        $code = $this->codeGenerator->generateCoupon($this->salesRule);

        $this->assertNotEmpty($code);
    }

    public function testNumericCouponCode()
    {
        $code = $this->codeGenerator->generateCoupon($this->salesRule, Coupon::COUPON_FORMAT_NUMERIC);
        // strip default prefix and delimiters
        $code = str_replace(['DOT', '-'], '', $code);

        $this->assertTrue(is_numeric($code));
    }

    public function testAlphabeticalCouponCode()
    {
        $code = $this->codeGenerator->generateCoupon($this->salesRule, Coupon::COUPON_FORMAT_ALPHABETICAL);
        // strip default prefix and delimiters
        $code = str_replace(['DOT', '-'], '', $code);

        $this->assertMatchesRegularExpression('/[A-Za-z]+/', $code);
    }

    public function testCouponPrefix()
    {
        $prefix = 'CHAZ-';
        $code = $this->codeGenerator->generateCoupon(
            $this->salesRule,
            Coupon::COUPON_FORMAT_ALPHABETICAL,
            $prefix
        );

        $this->assertStringStartsWith($prefix, $code);
    }

    public function testCouponSuffix()
    {
        $suffix = '-HANGLE';
        $code = $this->codeGenerator->generateCoupon(
            $this->salesRule,
            Coupon::COUPON_FORMAT_ALPHABETICAL,
            null,
            $suffix
        );

        $this->assertStringEndsWith($suffix, $code);
    }

    public function testProcessRequestParams()
    {
        $code = $this->couponRequestProcessor->processCouponRequest([
            'id' => 1,
        ])->getCouponCode();

        $this->assertNotEmpty($code);
    }

    public function testAlreadyProcessed()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 1,
        ];

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Already processed');

        $this->couponRequestProcessor->processCouponRequest($params)->getCouponCode();
        $this->couponRequestProcessor->processCouponRequest($params)->getCouponCode();
    }

    public function testAllowResend()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 1,
        ];
        $firstCode = $this->couponRequestProcessor->processCouponRequest($params)->getCouponCode();
        $secondCode = ObjectManager::getInstance()->create(DotdigitalCouponRequestProcessor::class)
            ->processCouponRequest($params)
            ->getCouponCode();

        $this->assertEquals($firstCode, $secondCode);
    }

    public function testNotAllowResend()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 0,
        ];
        $firstCode = $this->couponRequestProcessor->processCouponRequest($params)->getCouponCode();
        $secondCode = ObjectManager::getInstance()->create(DotdigitalCouponRequestProcessor::class)
            ->processCouponRequest($params)
            ->getCouponCode();

        $this->assertNotEquals($firstCode, $secondCode);
    }

    public function testRegeneratedIfUnusedCancelSend()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 0,
            'code_cancel_send' => 1,
        ];
        $firstCode = $this->couponRequestProcessor->processCouponRequest($params)->getCouponCode();
        /** @var DotdigitalCouponGenerator $secondCouponGenerator */
        $secondCouponGenerator = ObjectManager::getInstance()->create(DotdigitalCouponRequestProcessor::class)
            ->processCouponRequest($params);

        $this->assertEquals(
            DotdigitalCouponRequestProcessor::STATUS_REGENERATED,
            $secondCouponGenerator->getCouponGeneratorStatus()
        );
        $this->assertNotEquals($firstCode, $secondCouponGenerator->getCouponCode());
    }

    public function testCancelSendIfUsed()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 1,
            'code_cancel_send' => 1,
        ];

        $this->couponRequestProcessor->processCouponRequest($params)->getCouponCode();
        $this->redeemCoupon(1, 'chaz@kangaroo.com');

        /** @var DotdigitalCouponGenerator $secondCouponGenerator */
        $secondCouponGenerator = ObjectManager::getInstance()->create(DotdigitalCouponRequestProcessor::class)
            ->processCouponRequest($params);

        $this->assertEquals(
            DotdigitalCouponRequestProcessor::STATUS_USED_EXPIRED,
            $secondCouponGenerator->getCouponGeneratorStatus()
        );
    }

    public function testRegeneratedIfAllowResendFalseAndCancelSendTrue()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 0,
            'code_cancel_send' => 1,
        ];

        $this->couponRequestProcessor->processCouponRequest($params)->getCouponCode();
        $this->redeemCoupon(1, 'chaz@kangaroo.com');

        /** @var DotdigitalCouponGenerator $secondCouponGenerator */
        $secondCouponGenerator = ObjectManager::getInstance()->create(DotdigitalCouponRequestProcessor::class)
            ->processCouponRequest($params);

        $this->assertEquals(
            DotdigitalCouponRequestProcessor::STATUS_REGENERATED,
            $secondCouponGenerator->getCouponGeneratorStatus()
        );
    }

    public function testCancelSendAfterSalesRuleEndDate()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 1,
            'code_cancel_send' => 1,
        ];

        // initial request
        $this->couponRequestProcessor->processCouponRequest($params);

        $this->expireSalesRule();

        /** @var DotdigitalCouponGenerator $secondCouponGenerator */
        $secondCouponGenerator = ObjectManager::getInstance()->create(DotdigitalCouponRequestProcessor::class)
            ->processCouponRequest($params);

        $this->assertEquals(
            DotdigitalCouponRequestProcessor::STATUS_USED_EXPIRED,
            $secondCouponGenerator->getCouponGeneratorStatus()
        );
    }

    public function testNoCouponGeneratedIfRuleExpired()
    {
        $this->expireSalesRule();

        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 1,
            'code_cancel_send' => 1,
        ];

        $this->couponRequestProcessor->processCouponRequest($params);
        $this->assertEquals(
            DotdigitalCouponRequestProcessor::STATUS_USED_EXPIRED,
            $this->couponRequestProcessor->getCouponGeneratorStatus()
        );
    }

    public function testExpiryDateAddedToCoupon()
    {
        $params = [
            'id' => 1,
            'code_email' => 'chaz@kangaroo.com',
            'code_allow_resend' => 1,
            'code_cancel_send' => 1,
            'code_expires_after' => 7,
        ];

        $code = $this->couponRequestProcessor
            ->processCouponRequest($params)
            ->getCouponCode();

        /** @var CouponRepository $couponRepo */
        $couponRepo = ObjectManager::getInstance()->create(CouponRepository::class);
        $searchCriteria = ObjectManager::getInstance()->create(SearchCriteriaBuilder::class)
            ->addFilter('code', $code)
            ->create();

        $couponResult = $couponRepo->getList($searchCriteria)->getItems();
        $coupon = reset($couponResult);

        $expiresAt = $coupon->getExtensionAttributes()
            ->getDdgExtensionAttributes()
            ->getExpiresAtDate();

        $this->assertNotEmpty($expiresAt);
        $this->assertEquals(7, (new \DateTime('now', new \DateTimeZone('UTC')))->diff($expiresAt)->days);
    }

    /**
     * Expire the sales rule
     *
     * @throws \Exception
     */
    private function expireSalesRule()
    {
        // expire sales rules
        $salesRule = $this->salesRule->setToDate((new \DateTime('yesterday'))->format('Y-m-d'));
        $this->ruleResource->save($salesRule);
    }
}
