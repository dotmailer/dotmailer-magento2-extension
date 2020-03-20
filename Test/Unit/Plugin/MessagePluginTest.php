<?php

namespace Dotdigitalgroup\Email\Test\Unit\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Email\Template;
use Dotdigitalgroup\Email\Model\Email\TemplateService;
use Dotdigitalgroup\Email\Plugin\MessagePlugin;
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
     * @var \Zend\Mime\Message|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mimeMessageMock;

    /**
     * @var \Zend\Mime\Part|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mimePartMock;

    /**
     * @var TemplateService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templateServiceMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->messageMock = $this->createMock(MessageInterface::class);
        $this->mimeMessageMock = $this->createMock(\Zend\Mime\Message::class);
        $this->mimePartMock = $this->createMock(\Zend\Mime\Part::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->transactionalHelperMock = $this->createMock(Transactional::class);
        $this->templateModelMock = $this->createMock(Template::class);
        $this->templateServiceMock = $this->createMock(TemplateService::class);

        $this->plugin = new MessagePlugin(
            $this->registryMock,
            $this->transactionalHelperMock,
            $this->templateServiceMock
        );
    }

    public function testNoActionTakenIfNotFromTemplateRoute()
    {
        $storeId = 1;
        $templateId = null;

        $this->mockTemplateService($templateId);
        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $result = $this->plugin->beforeSetBody($this->messageMock, null);

        $this->assertNull($result);
    }

    public function testNoActionTakenIfSMTPIsDisabled()
    {
        $storeId = 1;

        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, false);

        $result = $this->plugin->beforeSetBody($this->messageMock, null);

        $this->assertNull($result);
    }

    public function testMimeMessageCreatedIfBodyIsString()
    {
        $storeId = 1;
        $templateId = 'Test Chaz_176887';
        $body = '<html><body>My message</body></html>';

        $this->mockTemplateService($templateId);
        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $result = $this->plugin->beforeSetBody($this->messageMock, $body);

        $this->assertInstanceOf('\Zend\Mime\Message', $result[0]);
    }

    public function testEncodingSetIfBodyIsMimeMessage()
    {
        $storeId = 1;
        $body = $this->mimeMessageMock;

        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $parts = [
            $this->mimePartMock
        ];
        $this->mimeMessageMock->method('getParts')
            ->willReturn($parts);
        $this->mimePartMock->expects($this->atLeastOnce())
            ->method('setEncoding');

        $result = $this->plugin->beforeSetBody($this->messageMock, $body);

        $this->assertEquals([$this->mimeMessageMock], $result);
    }

    private function mockRegistry($storeId)
    {
        $this->registryMock->method('registry')
            ->with('transportBuilderPluginStoreId')
            ->willReturn($storeId);
    }

    private function mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, $value)
    {
        $this->transactionalHelperMock->method('isEnabled')
            ->with($storeId)
            ->willReturn($value);
    }

    private function mockTemplateService($templateId)
    {
        $this->templateServiceMock->expects($this->once())
            ->method('getTemplateId')
            ->willReturn($templateId);
    }
}
