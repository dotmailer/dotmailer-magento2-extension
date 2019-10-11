<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Email;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Email\DotdigitalSenderResolver;
use Dotdigitalgroup\Email\Model\Email\Template;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
use Magento\Email\Model\Template\SenderResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

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
     * @return void
     */
    protected function setUp()
    {
        $this->senderResolverMock = $this->createMock(SenderResolver::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->transactionalHelperMock = $this->createMock(Transactional::class);
        $this->templateModelMock = $this->createMock(Template::class);
        $this->templateFactoryMock = $this->createMock(TemplateFactory::class);

        $this->dotdigitalSenderResolver = new DotdigitalSenderResolver(
            $this->scopeConfigMock,
            $this->registryMock,
            $this->templateFactoryMock,
            $this->transactionalHelperMock
        );
    }

    public function testNoActionTakenIfNotFromTemplateRoute()
    {
        $storeId = 1;
        $this->mockRegistry(null, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $this->templateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->expects($this->never())
            ->method('loadTemplate');

        $this->dotdigitalSenderResolver->resolve($this->mockMagentoSender(), null);
    }

    public function testNoActionTakenIfSMTPIsDisabled()
    {
        $storeId = 1;
        $this->mockRegistry(123456, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, false);

        $this->templateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->expects($this->never())
            ->method('loadTemplate');

        $this->dotdigitalSenderResolver->resolve($this->mockMagentoSender(), null);
    }

    public function testFromValuesNotSetWhenNotAnECTemplate()
    {
        $templateId = 123456;
        $storeId = 1;

        $this->templateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->expects($this->once())
            ->method('loadTemplateIdFromRegistry')
            ->willReturn($templateId);

        $this->mockRegistry($templateId, $storeId);
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

        $this->mockRegistry($templateId, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $this->templateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->expects($this->once())
            ->method('loadTemplateIdFromRegistry')
            ->willReturn($templateId);

        $this->mockTemplateCollectionToReturnTemplate(true, $templateId, $DDGSender['email'], $DDGSender['name']);

        $result = $this->dotdigitalSenderResolver->resolve($this->mockMagentoSender(), null);

        $this->assertEquals($result, $DDGSender);
    }

    private function mockRegistry($templateId, $storeId)
    {
        $this->registryMock->method('registry')
            ->withConsecutive(
                [$this->equalTo('transportBuilderPluginStoreId')],
                [$this->equalTo('dotmailer_current_template_id')]
            )
            ->willReturnOnConsecutiveCalls($storeId, $templateId);
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
