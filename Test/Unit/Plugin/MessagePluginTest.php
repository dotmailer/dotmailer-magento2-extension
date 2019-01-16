<?php

namespace Dotdigitalgroup\Email\Test\Unit\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Plugin\MessagePlugin;
use Magento\Email\Model\ResourceModel\Template;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class MessagePluginTest extends TestCase
{
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
    private $templateResourceModelMock;

    /**
     * @var TemplateFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templateModelMock;

    /**
     * @var MessagePlugin
     */
    private $plugin;

    /**
     * @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageMock;


    /**
     * @return void
     */
    protected function setUp()
    {
        $this->messageMock               = $this->createMock(MessageInterface::class);
        $this->registryMock              = $this->createMock(Registry::class);
        $this->transactionalHelperMock   = $this->createMock(Transactional::class);
        $this->templateResourceModelMock = $this->createMock(Template::class);
        $this->templateModelMock         = $this->createMock(TemplateFactory::class);
        $this->plugin                    = new MessagePlugin(
            $this->registryMock,
            $this->transactionalHelperMock,
            $this->templateResourceModelMock,
            $this->templateModelMock
        );
    }

    public function testNoActionTakenIfNotFromTemplateRoute()
    {
        $storeId = 1;
        $this->mockRegistry(null, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);
        $this->templateModelMock->expects($this->never())
                                ->method('create');

        $this->plugin->afterSetBody($this->messageMock, null);
    }

    public function testNoActionTakenIfDotmailerSMTPIsDisabled()
    {
        $storeId = 1;
        $this->mockRegistry(123456, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, false);
        $this->templateModelMock->expects($this->never())
                                ->method('create');

        $this->plugin->afterSetBody($this->messageMock, null);
    }

    public function testFromAddressNotSetWhenNotADotmailerTemplate()
    {
        $templateId = 123456;
        $storeId    = 1;
        $this->mockRegistry($templateId, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);
        $this->mockTemplateCollectionToReturnTemplate(false, $templateId, '', '');

        $this->messageMock->expects($this->never())
                          ->method('setFrom');

        $this->plugin->afterSetBody($this->messageMock, null);
    }

    public function testFromSetWhenDotmailerTemplateAndDotmailerSmtpIsEnabled()
    {
        $templateId  = 123456;
        $storeId     = 1;
        $senderEmail = 'test@dotmailer.com';
        $senderName  = 'dotmailer';
        $this->mockRegistry($templateId, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);
        $this->mockTemplateCollectionToReturnTemplate(true, $templateId, $senderEmail, $senderName);

        $this->messageMock->expects($this->once())
                          ->method('setFrom')
                          ->with($senderEmail, $senderName);

        $this->plugin->afterSetBody($this->messageMock, null);
    }

    public function testFromClearedWhenZendMail()
    {
        $templateId  = 123456;
        $storeId     = 1;
        $senderEmail = 'test@dotmailer.com';
        $senderName  = 'dotmailer';
        $this->mockRegistry($templateId, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);
        $this->mockTemplateCollectionToReturnTemplate(true, $templateId, $senderEmail, $senderName);

        $this->messageMock = $this->createMock(Magento22MailClassTestDouble::class);
        $this->messageMock->expects($this->once())
                          ->method('clearFrom');
        $this->messageMock->expects($this->once())
                          ->method('setFrom')
                          ->with($senderEmail, $senderName);

        $this->plugin->afterSetBody($this->messageMock, null);
    }

    private function mockRegistry($templateId, $storeId)
    {
        $this->registryMock->method('registry')
                           ->withConsecutive(
                               ['dotmailer_current_template_id'],
                               ['transportBuilderPluginStoreId']
                           )
                           ->willReturnOnConsecutiveCalls($templateId, $storeId);
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
        $templateCode      = 'dm_template_code';
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
        $this->templateModelMock->method('create')
                                ->willReturn($templateModelMock);

        $this->templateResourceModelMock->expects($this->once())
                                        ->method('load')
                                        ->with($templateModelMock, $this->stringContains($templateId));

        $this->transactionalHelperMock->method('isDotmailerTemplate')
                                      ->willReturn($isDotmailerTemplate);

        return $templateModelMock;
    }
}
