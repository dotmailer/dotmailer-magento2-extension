<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Apiconnector;

use Dotdigitalgroup\Email\Model\Apiconnector\Test;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use PHPUnit\Framework\TestCase;

class ApiTestTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var ReinitableConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configInterfaceMock;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->configInterfaceMock = $this->createMock(ReinitableConfigInterface::class);

        $this->apiTest = new Test(
            $this->helperMock,
            $this->configInterfaceMock
        );
    }

    public function testValidDotmailerEndpoint()
    {
        $validEndpoint = 'https://api.dotmailer.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r1-api.dotmailer.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r2-api.dotmailer.com';
        $this->assertTrue(
            $this->apiTest->validateEndpoint($validEndpoint)
        );

        $validEndpoint = 'https://r3000000-api.dotmailer.com';
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

    public function testInvalidScheme()
    {
        $invalidEndpoint = 'http://r1-api.dotmailer.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }

    public function testMissingScheme()
    {
        $invalidEndpoint = 'r1-api.dotmailer.com';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }

    public function testInvalidTrailingSlash()
    {
        $invalidEndpoint = 'https://r1-api.dotmailer.com/';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->apiTest->validateEndpoint($invalidEndpoint);
    }
}
