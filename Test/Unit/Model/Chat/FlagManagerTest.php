<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Chat;

use Dotdigitalgroup\Email\Model\Chat\EmailFlagManager;
use Magento\Framework\FlagFactory;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\Flag;
use \PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Class FlagManagerTest
 *
 * Ported from Magento 2.2+ so we can support earlier Magento releases
 */
class FlagManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FlagFactory|Mock
     */
    private $flagFactoryMock;

    /**
     * @var Flag|Mock
     */
    private $flagMock;

    /**
     * @var FlagResource|Mock
     */
    private $flagResourceMock;

    /**
     * @var EmailFlagManager
     */
    private $flagManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->flagFactoryMock = $this->getMockBuilder(FlagFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flagResourceMock = $this->getMockBuilder(FlagResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flagMock = $this->getMockBuilder(Flag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flagManager = new EmailFlagManager(
            $this->flagResourceMock,
            $this->flagFactoryMock
        );
    }

    public function testGetFlagData()
    {
        $flagCode = 'flag';
        $this->setupFlagObject($flagCode);
        $this->flagMock->expects($this->once())
            ->method('getFlagData')
            ->willReturn(10);
        $this->assertEquals($this->flagManager->fetch($flagCode), 10);
    }

    public function testSaveFlag()
    {
        $flagCode = 'flag';
        $this->setupFlagObject($flagCode);
        $this->flagMock->expects($this->once())
            ->method('setFlagData')
            ->with(10);
        $this->flagResourceMock->expects($this->once())
            ->method('save')
            ->with($this->flagMock);
        $this->assertTrue(
            $this->flagManager->save($flagCode, 10)
        );
    }

    /**
     * @dataProvider flagExistDataProvider
     *
     * @param bool $isFlagExist
     */
    public function testDeleteFlag($isFlagExist)
    {
        $flagCode = 'flag';
        $this->setupFlagObject($flagCode);
        $this->flagMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($isFlagExist);
        if ($isFlagExist) {
            $this->flagResourceMock
                ->expects($this->once())
                ->method('delete')
                ->with($this->flagMock);
        }
        $this->assertTrue(
            $this->flagManager->delete($flagCode)
        );
    }

    /**
     * @param $flagCode
     */
    private function setupFlagObject($flagCode)
    {
        $this->flagFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['flag_code' => $flagCode]])
            ->willReturn($this->flagMock);
        $this->flagResourceMock->expects($this->once())
            ->method('load')
            ->with($this->flagMock, $flagCode, 'flag_code');
    }

    /**
     * Provide variations of the flag existence.
     *
     * @return array
     */
    public function flagExistDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
