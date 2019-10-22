<?php

namespace Dotdigitalgroup\Email\Test\Unit\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Plugin\MessagePlugin;
use Dotdigitalgroup\Email\Model\Email\Template;
use Dotdigitalgroup\Email\Model\Email\TemplateFactory;
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
     * @var TemplateFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $templateFactoryMock;

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
        $this->templateModelMock = $this->createMock(Template::class);
        $this->templateFactoryMock = $this->createMock(TemplateFactory::class);
        $this->plugin                    = new MessagePlugin(
            $this->registryMock,
            $this->transactionalHelperMock,
            $this->templateFactoryMock
        );
    }

    public function testNoActionTakenIfNotFromTemplateRoute()
    {
        $storeId = 1;
        $templateId = null;

        $this->mockLoadTemplateIdFromRegistry($templateId);
        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $result = $this->plugin->beforeSetBody($this->messageMock, null);

        $this->assertNull($result);
    }

    public function testNoActionTakenIfSMTPIsDisabled()
    {
        $storeId = 1;
        $templateId = 123456;

        $this->mockLoadTemplateIdFromRegistry($templateId);
        $this->mockRegistry($storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, false);

        $result = $this->plugin->beforeSetBody($this->messageMock, null);

        $this->assertNull($result);
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

    private function mockLoadTemplateIdFromRegistry($templateId)
    {
        $this->templateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->templateModelMock);

        $this->templateModelMock->expects($this->once())
            ->method('loadTemplateIdFromRegistry')
            ->willReturn($templateId);
    }
}
