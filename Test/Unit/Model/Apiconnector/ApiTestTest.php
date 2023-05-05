<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Apiconnector;

use Dotdigitalgroup\Email\Model\Apiconnector\Test;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Magento\Framework\App\Config\Storage\Writer;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use PHPUnit\Framework\TestCase;

class ApiTestTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var ClientFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientFactoryMock;

    /**
     * @var ReinitableConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configInterfaceMock;

    /**
     * @var Account|\PHPUnit\Framework\MockObject\MockObject
     */
    private $accountMock;

    /**
     * @var Writer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $writerMock;

    /**
     * @var Test
     */
    private $apiTest;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->configInterfaceMock = $this->createMock(ReinitableConfigInterface::class);
        $this->accountMock = $this->createMock(Account::class);
        $this->writerMock = $this->createMock(Writer::class);

        $this->apiTest = new Test(
            $this->helperMock,
            $this->clientFactoryMock,
            $this->configInterfaceMock,
            $this->accountMock,
            $this->writerMock
        );
    }

    public function testValidDotmailerEndpoint()
    {
        $validEndpoint = 'https://api.dotdigital.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://api.dotmailer.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r1-api.dotdigital.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r1-api.dotmailer.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r2-api.dotdigital.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r2-api.dotmailer.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r3000000-api.dotdigital.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r3000000-api.dotmailer.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r1-api-stg.dotdigital.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r1-api-vp.dotdigital.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r1-api-foo.dotdigital.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );
    }

    public function testValidApiconnectorEndpoint()
    {
        $validEndpoint = 'https://apiconnector.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );
    }

    public function testApiconnectorEndpointWithSubdomain()
    {
        $validEndpoint = 'https://r1.apiconnector.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r10.apiconnector.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );
    }

    public function testInvalidApiconnectorSubdomain()
    {
        $invalidEndpoint = 'https://r1.r1.apiconnector.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);

        $invalidEndpoint = 'https://www.apiconnector.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }

    public function testInvalidHost()
    {
        $invalidEndpoint = 'http://r1-api.chaz.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }

    public function testInvalidSubdomain()
    {
        $invalidEndpoint = 'http://r1-stg.dotdigital.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);

        $invalidEndpoint = 'http://r1.api.dotdigital.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }

    public function testInvalidScheme()
    {
        $invalidEndpoint = 'http://r1-api.dotdigital.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);

        $invalidEndpoint = 'http://r1-api.dotmailer.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }

    public function testMissingScheme()
    {
        $invalidEndpoint = 'r1-api.dotdigital.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);

        $invalidEndpoint = 'r1-api.dotmailer.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }

    public function testInvalidTrailingSlash()
    {
        $invalidEndpoint = 'https://r1-api.dotdigital.com/';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);

        $invalidEndpoint = 'https://r1-api.dotmailer.com/';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }
}
