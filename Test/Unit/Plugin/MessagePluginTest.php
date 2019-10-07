<?php

namespace Dotdigitalgroup\Email\Test\Unit\Plugin;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Plugin\MessagePlugin;
use Dotdigitalgroup\Email\Model\Email\Template;
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
     * @return void
     */
    protected function setUp()
    {
        $this->messageMock               = $this->createMock(MessageInterface::class);
        $this->registryMock              = $this->createMock(Registry::class);
        $this->transactionalHelperMock   = $this->createMock(Transactional::class);
        $this->templateModelMock         = $this->createMock(Template::class);
        $this->plugin                    = new MessagePlugin(
            $this->registryMock,
            $this->transactionalHelperMock,
            $this->templateModelMock
        );
    }

    public function testNoActionTakenIfNotFromTemplateRoute()
    {
        $storeId = 1;
        $this->mockRegistry(null, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, true);

        $result = $this->plugin->beforeSetBody($this->messageMock, null);

        $this->assertNull($result);
    }

    public function testNoActionTakenIfSMTPIsDisabled()
    {
        $storeId = 1;
        $this->mockRegistry(123456, $storeId);
        $this->mockTransactionalHelperToReturnValueForSMTPEnabled($storeId, false);

        $result = $this->plugin->beforeSetBody($this->messageMock, null);

        $this->assertNull($result);
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
}
