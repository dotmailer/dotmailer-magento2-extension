<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Email;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Email\DotdigitalSenderResolver;
use Dotdigitalgroup\Email\Model\Email\Template;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
use Dotdigitalgroup\Email\Model\Email\TemplateService;
use Magento\Email\Model\Template\SenderResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Email\Logger\Logger;

class DotdigitalSenderResolverTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registryMock;

    /**
     * @var Transactional|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionalHelperMock;

    /**
     * @var Template|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templateModelMock;

    /**
     * @var TemplateFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templateFactoryMock;

    /**
     * @var DotdigitalSenderResolver
     */
    private $dotdigitalSenderResolver;

    /**
     * @var SenderResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $senderResolverMock;

    /**
     * @var TemplateService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templateServiceMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @return void
     */
    protected function setUp() :void
    {
        $this->senderResolverMock = $this->createMock(SenderResolver::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->transactionalHelperMock = $this->createMock(Transactional::class);
        $this->templateModelMock = $this->createMock(Template::class);
        $this->templateFactoryMock = $this->createMock(TemplateFactory::class);
        $this->templateServiceMock = $this->createMock(TemplateService::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->dotdigitalSenderResolver = new DotdigitalSenderResolver(
            $this->scopeConfigMock,
            $this->registryMock,
            $this->templateFactoryMock,
            $this->transactionalHelperMock,
            $this->templateServiceMock,
            $this->loggerMock
        );
    }

    public function testNoActionTakenIfNotFromTemplateRoute()
    {
        $storeId = 1;
        $templateId = null;

        $this->mockTemplateService($templateId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $this->templateFactoryMock->expects($this->never())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->expects($this->never())
            ->method('loadTemplate');

        $this->dotdigitalSenderResolver->resolve($this->mockMagentoSender(), null);
    }

    public function testNoActionTakenIfSMTPIsDisabled()
    {
        $storeId = 1;
        $templateId = 123456;

        $this->mockTemplateService($templateId);
        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, false);

        $this->templateFactoryMock->expects($this->never())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->expects($this->never())
            ->method('loadTemplate');

        $this->dotdigitalSenderResolver->resolve($this->mockMagentoSender(), null);
    }

    public function testFromValuesNotSetWhenNotAnECTemplate()
    {
        $storeId = 1;
        $templateId = 123456;

        $this->mockTemplateService($templateId);
        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);
        $this->mockTemplateCollectionToReturnTemplate(false, $templateId, '', '');

        $result = $this->dotdigitalSenderResolver->resolve($this->mockMagentoSender(), null);

        $this->assertEquals($result, $this->mockMagentoSender());
    }

    public function testFromValuesSetWhenDotmailerTemplateAndSmtpIsEnabled()
    {
        $templateId = 123456;
        $storeId = 1;
        $DDGSender = $this->mockDDGSender();

        $this->mockTemplateService($templateId);
        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $this->mockTemplateCollectionToReturnTemplate(true, $templateId, $DDGSender['email'], $DDGSender['name']);

        $result = $this->dotdigitalSenderResolver->resolve($this->mockMagentoSender(), null);

        $this->assertEquals($result, $DDGSender);
    }

    private function mockTemplateService($templateId)
    {
        $this->templateServiceMock->expects($this->once())
            ->method('getTemplateId')
            ->willReturn($templateId);
    }

    private function mockRegistry($storeId)
    {
        $this->registryMock->method('registry')
            ->withConsecutive(
                [$this->equalTo('transportBuilderPluginStoreId')]
            )
            ->willReturnOnConsecutiveCalls($storeId);
    }

    private function mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, $value)
    {
        $this->transactionalHelperMock->method('isEnabled')
            ->with($storeId)
            ->willReturn($value);
    }

    private function mockTemplateCollectionToReturnTemplate(
        $isDotmailerTemplate,
        $templateId,
        $senderEmail,
        $senderName
    ) {
        $templateCode = 'dm_template_code';
        $templateModelMock = $this->createMock(\Magento\Email\Model\Template::class);
        $templateModelMock->method('__call')
            ->withConsecutive(
                [$this->equalTo('getTemplateCode')],
                [$this->equalTo('getTemplateSenderEmail')],
                [$this->equalTo('getTemplateSenderName')]
            )
            ->willReturnOnConsecutiveCalls(
                $templateCode,
                $senderEmail,
                $senderName
            );

        $this->templateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->method('loadTemplate')
            ->with($templateId)
            ->willReturn($templateModelMock);

        $this->transactionalHelperMock->method('isDotmailerTemplate')
            ->willReturn($isDotmailerTemplate);

        return $templateModelMock;
    }

    private function mockMagentoSender()
    {
        return [
            'name' => "Owner",
            'email' => "owner@magento.com"
        ];
    }

    private function mockDDGSender()
    {
        return [
            'name' => "Chaz Kangeroo",
            'email' => "chaz@kangeroo.com"
        ];
    }
}
