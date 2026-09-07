<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Plugin;

use DateTime;
use DateTimeZone;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttribute;
use Dotdigitalgroup\Email\Model\DateTimeFactory;
use Dotdigitalgroup\Email\Model\DateTimeZoneFactory;
use Dotdigitalgroup\Email\Plugin\CouponExpiredPlugin;
use Dotdigitalgroup\Email\Test\Unit\Stub\CouponExtensionStubInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponSearchResultInterface;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CouponExpiredPluginTest extends TestCase
{
    /**
     * The value written to email_coupon_attribute.expires_at for a coupon generated
     * with an "expires at" date of 2026-08-31.
     */
    private const STORED_EXPIRES_AT = '2026-08-31 23:59:59';

    /**
     * @var CouponRepositoryInterface|MockObject
     */
    private $couponRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $criteriaBuilderMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $timezoneMock;

    /**
     * @var DateTimeFactory|MockObject
     */
    private $dateTimeFactoryMock;

    /**
     * @var DateTimeZoneFactory|MockObject
     */
    private $dateTimeZoneFactoryMock;

    /**
     * @var Utility|MockObject
     */
    private $utilityMock;

    /**
     * @var CouponExpiredPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->couponRepositoryMock = $this->createMock(CouponRepositoryInterface::class);
        $this->criteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->timezoneMock = $this->createMock(TimezoneInterface::class);
        $this->dateTimeFactoryMock = $this->createMock(DateTimeFactory::class);
        $this->dateTimeZoneFactoryMock = $this->createMock(DateTimeZoneFactory::class);
        $this->utilityMock = $this->createMock(Utility::class);

        $this->criteriaBuilderMock->method('addFilter')
            ->willReturnSelf();
        $this->criteriaBuilderMock->method('create')
            ->willReturn($this->createMock(SearchCriteria::class));

        // The factories are thin wrappers around the native date classes.
        $this->dateTimeZoneFactoryMock->method('create')
            ->willReturnCallback(function (array $data) {
                return new DateTimeZone($data['timezone']);
            });
        $this->dateTimeFactoryMock->method('create')
            ->willReturnCallback(function (array $data) {
                return new DateTime($data['time'], $data['timezone']);
            });

        $this->plugin = new CouponExpiredPlugin(
            $this->couponRepositoryMock,
            $this->criteriaBuilderMock,
            $this->timezoneMock,
            $this->dateTimeFactoryMock,
            $this->dateTimeZoneFactoryMock
        );
    }

    /**
     * A coupon must remain valid until the end of its expiry date in the timezone of the store
     * the shopper is checking out in, not until the end of that date in UTC.
     *
     * @param string $storeTimezone
     * @param string $nowUtc
     * @param bool $expectedExpired
     * @return void
     */
    #[DataProvider('storeTimezoneDataProvider')]
    public function testCouponExpiresAtEndOfDayInTheStoreTimezone(
        string $storeTimezone,
        string $nowUtc,
        bool $expectedExpired
    ) {
        $this->setUpClock($storeTimezone, $nowUtc);

        $ruleMock = $this->buildRuleMock();
        $this->stubCouponLookup(self::STORED_EXPIRES_AT);

        $ruleMock->expects($expectedExpired ? $this->once() : $this->never())
            ->method('setIsValidForAddress');

        $this->assertSame(
            !$expectedExpired,
            $this->plugin->afterCanProcessRule(
                $this->utilityMock,
                true,
                $ruleMock,
                $this->buildAddressMock()
            )
        );
    }

    /**
     * @return array
     */
    public static function storeTimezoneDataProvider(): array
    {
        return [
            // 23:59:59 UTC is 19:59:59 in New York. Before the fix everything from 20:00 local
            // onwards was rejected, losing the shopper the last four hours of the final day.
            'New York, 22:00 local on the expiry date' => [
                'America/New_York',
                '2026-09-01 02:00:00',
                false,
            ],
            'New York, 23:59 local on the expiry date' => [
                'America/New_York',
                '2026-09-01 03:59:00',
                false,
            ],
            'New York, just after local midnight' => [
                'America/New_York',
                '2026-09-01 04:00:30',
                true,
            ],
            // The same rule on a website east of UTC previously granted extra hours.
            'Sydney, 23:00 local on the expiry date' => [
                'Australia/Sydney',
                '2026-08-31 13:00:00',
                false,
            ],
            'Sydney, just after local midnight' => [
                'Australia/Sydney',
                '2026-08-31 14:30:00',
                true,
            ],
            'UTC store, 23:59 local on the expiry date' => [
                'UTC',
                '2026-08-31 23:59:00',
                false,
            ],
            'UTC store, just after local midnight' => [
                'UTC',
                '2026-09-01 00:00:30',
                true,
            ],
        ];
    }

    /**
     * EDC coupon expiries are exact UTC timestamps and must not be moved to the end of the store day.
     *
     * @param string $nowUtc
     * @param bool $expectedExpired
     * @return void
     */
    #[DataProvider('edcExpiryDataProvider')]
    public function testEdcCouponExpiresAtExactStoredUtcTime(string $nowUtc, bool $expectedExpired)
    {
        $this->setUpClock('America/New_York', $nowUtc);

        $ruleMock = $this->buildRuleMock();
        $this->stubCouponLookup('2026-08-31 20:00:00');

        $ruleMock->expects($expectedExpired ? $this->once() : $this->never())
            ->method('setIsValidForAddress');

        $this->assertSame(
            !$expectedExpired,
            $this->plugin->afterCanProcessRule(
                $this->utilityMock,
                true,
                $ruleMock,
                $this->buildAddressMock()
            )
        );
    }

    /**
     * @return array
     */
    public static function edcExpiryDataProvider(): array
    {
        return [
            'one second before the stored UTC time' => ['2026-08-31 19:59:59', false],
            'at the stored UTC time' => ['2026-08-31 20:00:00', false],
            'one second after the stored UTC time' => ['2026-08-31 20:00:01', true],
        ];
    }

    /**
     * A coupon with no expiry date, or an unparseable one, must never be treated as expired.
     *
     * @param string|null $expiresAt
     * @return void
     */
    #[DataProvider('unusableExpiryDataProvider')]
    public function testCouponWithoutAUsableExpiryDateIsNotExpired(?string $expiresAt)
    {
        $this->setUpClock('America/New_York', '2030-01-01 00:00:00');

        $ruleMock = $this->buildRuleMock();
        $this->stubCouponLookup($expiresAt);

        $ruleMock->expects($this->never())
            ->method('setIsValidForAddress');

        $this->assertTrue(
            $this->plugin->afterCanProcessRule(
                $this->utilityMock,
                true,
                $ruleMock,
                $this->buildAddressMock()
            )
        );
    }

    /**
     * @return array
     */
    public static function unusableExpiryDataProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'not a date' => ['not-a-date'],
            'zero date' => ['0000-00-0'],
        ];
    }

    /**
     * @return void
     */
    public function testCouponNotGeneratedByDotdigitalIsIgnored()
    {
        $this->setUpClock('America/New_York', '2030-01-01 00:00:00');

        $ruleMock = $this->buildRuleMock();
        $this->stubCouponLookup(self::STORED_EXPIRES_AT, false);

        $ruleMock->expects($this->never())
            ->method('setIsValidForAddress');

        $this->assertTrue(
            $this->plugin->afterCanProcessRule(
                $this->utilityMock,
                true,
                $ruleMock,
                $this->buildAddressMock()
            )
        );
    }

    /**
     * @return void
     */
    public function testUnknownCouponCodeIsIgnored()
    {
        $searchResultMock = $this->createMock(CouponSearchResultInterface::class);
        $searchResultMock->method('getItems')
            ->willReturn([]);
        $this->couponRepositoryMock->method('getList')
            ->willReturn($searchResultMock);

        $ruleMock = $this->buildRuleMock();
        $ruleMock->expects($this->never())
            ->method('setIsValidForAddress');

        $this->assertTrue(
            $this->plugin->afterCanProcessRule(
                $this->utilityMock,
                true,
                $ruleMock,
                $this->buildAddressMock()
            )
        );
    }

    /**
     * A rule with its own to_date is handled by Magento, so we must not look the coupon up at all.
     *
     * @return void
     */
    public function testRuleWithAToDateIsLeftToMagento()
    {
        $this->couponRepositoryMock->expects($this->never())
            ->method('getList');

        $ruleMock = $this->buildRuleMock('2026-09-30');

        $this->assertTrue(
            $this->plugin->afterCanProcessRule(
                $this->utilityMock,
                true,
                $ruleMock,
                $this->buildAddressMock()
            )
        );
    }

    /**
     * @return void
     */
    public function testAlreadyInvalidRuleIsNotReprocessed()
    {
        $this->couponRepositoryMock->expects($this->never())
            ->method('getList');

        $this->assertFalse(
            $this->plugin->afterCanProcessRule(
                $this->utilityMock,
                false,
                $this->buildRuleMock(),
                $this->buildAddressMock()
            )
        );
    }

    /**
     * Point the timezone service at a store timezone and freeze "now".
     *
     * @param string $storeTimezone
     * @param string $nowUtc
     * @return void
     */
    private function setUpClock(string $storeTimezone, string $nowUtc): void
    {
        $this->timezoneMock->method('getConfigTimezone')
            ->willReturn($storeTimezone);

        // Timezone::date() relabels the given instant to the store timezone, or returns "now"
        // in the store timezone when called with no argument. setTimezone() never shifts the
        // underlying timestamp.
        $this->timezoneMock->method('date')
            ->willReturnCallback(function ($date = null) use ($storeTimezone, $nowUtc) {
                if ($date instanceof DateTime) {
                    return $date->setTimezone(new DateTimeZone($storeTimezone));
                }
                return (new DateTime($nowUtc, new DateTimeZone('UTC')))
                    ->setTimezone(new DateTimeZone($storeTimezone));
            });
    }

    /**
     * @param string|null $expiresAt
     * @param bool $generatedByDotdigital
     * @return void
     */
    private function stubCouponLookup(?string $expiresAt, bool $generatedByDotdigital = true): void
    {
        $couponAttributeMock = $this->createMock(CouponAttribute::class);
        $couponAttributeMock->method('getExpiresAt')
            ->willReturn($expiresAt);

        // CouponAttribute::getExpiresAtDate() parses the stored string as UTC.
        $couponAttributeMock->method('getExpiresAtDate')
            ->willReturnCallback(function () use ($expiresAt) {
                if (empty($expiresAt)) {
                    return null;
                }
                try {
                    return new DateTime($expiresAt, new DateTimeZone('UTC'));
                } catch (\Exception $e) {
                    return null;
                }
            });

        $extensionAttributesMock = $this->createMock(CouponExtensionStubInterface::class);
        $extensionAttributesMock->method('getDdgExtensionAttributes')
            ->willReturn($couponAttributeMock);

        $couponMock = $this->getMockBuilder(Coupon::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getExtensionAttributes'])
            ->getMock();
        $couponMock->method('__call')
            ->willReturnCallback(function (string $method) use ($generatedByDotdigital) {
                return $method === 'getGeneratedByDotmailer' ? (int) $generatedByDotdigital : null;
            });
        $couponMock->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $searchResultMock = $this->createMock(CouponSearchResultInterface::class);
        $searchResultMock->method('getItems')
            ->willReturn([$couponMock]);

        $this->couponRepositoryMock->method('getList')
            ->willReturn($searchResultMock);
    }

    /**
     * @param string|null $toDate
     * @return Rule|MockObject
     */
    private function buildRuleMock(?string $toDate = null)
    {
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getToDate', 'setIsValidForAddress'])
            ->getMock();

        // getCouponType() and getUseAutoGeneration() are magic getters on the model.
        $ruleMock->method('__call')
            ->willReturnCallback(function (string $method) {
                $values = [
                    'getCouponType' => Rule::COUPON_TYPE_SPECIFIC,
                    'getUseAutoGeneration' => 1,
                ];
                return $values[$method] ?? null;
            });
        $ruleMock->method('getToDate')
            ->willReturn($toDate);

        return $ruleMock;
    }

    /**
     * @return Address|MockObject
     */
    private function buildAddressMock()
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $quoteMock->method('__call')
            ->willReturnCallback(function (string $method) {
                return $method === 'getCouponCode' ? 'DDG-TEST-COUPON' : null;
            });

        $addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $addressMock->method('getQuote')
            ->willReturn($quoteMock);

        return $addressMock;
    }
}
