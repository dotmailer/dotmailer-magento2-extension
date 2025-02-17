<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\DataFieldTranslator;
use Magento\Framework\TranslateInterface;
use PHPUnit\Framework\TestCase;

class DataFieldTranslatorTest extends TestCase
{
    /**
     * @var DataFieldTranslator
     */
    private $dataFieldTranslator;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var Account|\PHPUnit\Framework\MockObject\MockObject
     */
    private $accountMock;

    /**
     * @var TranslateInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translateInterfaceMock;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->accountMock = $this->createMock(Account::class);
        $this->translateInterfaceMock = $this->createMock(TranslateInterface::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->dataFieldTranslator = new DataFieldTranslator(
            $this->helperMock,
            $this->loggerMock,
            $this->accountMock,
            $this->translateInterfaceMock
        );
    }

    public function testTranslateReturnsNameWhenLocaleIsEnglish(): void
    {
        $name = 'testField';
        $websiteId = 1;
        $locale = 'en_US';

        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);
        $this->clientMock->method('getAccountInfo')->willReturn(new \stdClass());
        $this->accountMock->method('getAccountLocale')->willReturn($locale);

        $result = $this->dataFieldTranslator->translate($name, $websiteId);

        $this->assertEquals($name, $result);
    }

    public function testTranslateReturnsTranslatedNameWhenValid(): void
    {
        $name = 'testField';
        $translatedName = 'testFieldTranslated';
        $websiteId = 1;
        $locale = 'fr_FR';

        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);
        $this->clientMock->method('getAccountInfo')->willReturn(new \stdClass());
        $this->accountMock->method('getAccountLocale')->willReturn($locale);
        $this->translateInterfaceMock->method('setLocale')->willReturnSelf();
        $this->translateInterfaceMock->method('getData')->willReturn([$name => $translatedName]);
        $this->clientMock->method('getDataFields')->willReturn([['name' => $translatedName]]);

        $result = $this->dataFieldTranslator->translate($name, $websiteId);

        $this->assertEquals($translatedName, $result);
    }

    public function testTranslateReturnsNameWhenTranslationFails(): void
    {
        $name = 'testField';
        $websiteId = 1;
        $locale = 'fr_FR';

        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);
        $this->clientMock->method('getAccountInfo')->willReturn(new \stdClass());
        $this->accountMock->method('getAccountLocale')->willReturn($locale);
        $this->translateInterfaceMock->method('setLocale')->willReturnSelf();
        $this->translateInterfaceMock->method('getData')->willReturn([]);

        $this->clientMock->expects($this->never())
            ->method('getDataFields');

        $result = $this->dataFieldTranslator->translate($name, $websiteId);

        $this->assertEquals($name, $result);
    }

    public function testTranslateLogsExceptionAndReturnsName(): void
    {
        $name = 'testField';
        $websiteId = 1;

        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);
        $this->clientMock->method('getAccountInfo')->willReturn(new \stdClass());
        $this->accountMock->method('getAccountLocale')->willThrowException(new \Exception('Test exception'));

        $this->loggerMock->expects($this->once())->method('debug');

        $result = $this->dataFieldTranslator->translate($name, $websiteId);

        $this->assertEquals($name, $result);
    }
}
