<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttributeCollection;
use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponGenerator;
use Dotdigitalgroup\Email\Test\Integration\LoadsSaleRule;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Dotdigitalgroup\Email\Test\Integration\RedeemsCoupons;
use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;

include __DIR__ . '/../../_files/salesrule.php';

/**
 * @magentoDbIsolation enabled
 */
class CouponTest extends AbstractController
{
    use MocksApiResponses;
    use RedeemsCoupons;
    use LoadsSaleRule;

    const ROUTE_COUPON_BASE_PATH = 'connector/email/coupon/';

    private $helper;

    /**
     * @var CouponAttributeCollection
     */
    private $couponAttributeCollection;

    public function setUp() :void
    {
        parent::setUp();

        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE => 'chaz',
            Config::XML_PATH_CONNECTOR_IP_RESTRICTION_ADDRESSES => '',
        ], 0, 'default');

        $this->helper = $this->instantiateDataHelper();

        $this->couponAttributeCollection = $this->_objectManager->create(CouponAttributeCollection::class);
    }

    public function testGenerateCoupon()
    {
        $this->dispatch($this->getRoute([
            'id' => 1,
            'expire_days' => 2,
            'code' => $this->helper->getPasscode(),
        ]));

        $response = $this->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getBody());
    }

    public function testUnauthorised()
    {
        $this->dispatch($this->getRoute([
            'id' => 1,
            'expire_days' => 2,
            'code' => 'kangaroo',
        ]));

        $response = $this->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testWithEmail()
    {
        $this->dispatch($this->getRoute([
            'id' => 1,
            'expire_days' => 2,
            'code' => $this->helper->getPasscode(),
            'code_email' => $email = 'chaz@kangaroo.com',
        ]));

        $couponEmail = $this->couponAttributeCollection->getActiveCouponsForEmail(1, $email)
            ->getFirstItem()
            ->toArray();

        $this->assertNotEmpty($couponEmail);
    }

    public function testEmptyResponseIfEmailInvalid()
    {
        $this->dispatch($this->getRoute([
            'id' => 1,
            'expire_days' => 2,
            'code' => $this->helper->getPasscode(),
            'code_email' => '@CHAZ@',
            'code_allow_resend' => 0,
            'code_cancel_send' => 1,
        ]));
        $response = $this->getResponse();

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testTwoHundredAndFourStatusIfUsed()
    {
        $email = 'chaz@kangaroo.com';

        /** @var DotdigitalCouponGenerator $codeGenerator */
        $codeGenerator = ObjectManager::getInstance()->create(DotdigitalCouponGenerator::class);
        $codeGenerator->generateCoupon($this->loadSalesRule(), null, null, null, $email);

        // redeem coupon
        $this->redeemCoupon(1, $email);

        $this->dispatch($this->getRoute([
            'id' => 1,
            'expire_days' => 2,
            'code' => $this->helper->getPasscode(),
            'code_email' => $email,
            'code_allow_resend' => 1,
            'code_cancel_send' => 1,
        ]));
        $response = $this->getResponse();

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @param array $params
     * @return string
     */
    private function getRoute(array $params)
    {
        return self::ROUTE_COUPON_BASE_PATH . implode('/', array_map(function ($param, $key) {
            return sprintf('%s/%s', $key, $param);
        }, $params, array_keys($params)));
    }
}
