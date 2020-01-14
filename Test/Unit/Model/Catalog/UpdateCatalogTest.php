<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog;

use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\CatalogFactory;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog as Update;
use Dotdigitalgroup\Email\Model\Catalog as ModelCatalog;

class UpdateCatalogTest extends TestCase
{

    /**
     * @var Catalog
     */
    private $catalogResourceMock;

    /**
     * @var CatalogFactory
     */
    private $catalogFactoryMock;

    /**
     * @var Update
     */
    private $updateCatalog;

    /**
     * @var ModelCatalog
     */
    private $catalogMock;

    protected function setUp()
    {
        $this->catalogResourceMock = $this->createMock(Catalog::class);
        $this->catalogFactoryMock = $this->createMock(CatalogFactory::class);
        $this->catalogMock = $this->getMockBuilder(ModelCatalog::class)
                                    ->setMethods(['loadProductById','getId','getProcessed','setProductId'])
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->updateCatalog = new Update(
            $this->catalogResourceMock,
            $this->catalogFactoryMock
        );
    }

    public function testThatIfProductExistsNewEntryNeverCreated()
    {
        $this->catalogFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->catalogMock);

        $this->catalogMock->expects($this->once())
            ->method('loadProductById')
            ->with(2)
            ->willReturn($this->catalogMock);

        $this->catalogMock->expects($this->once())
            ->method('getId')
            ->willReturn(2455);

        $this->catalogMock->expects($this->once())
            ->method('getProcessed')
            ->willReturn(1);

        $this->catalogResourceMock->expects($this->once())
            ->method('save')
            ->with($this->catalogMock);

        $this->catalogMock->expects($this->never())
            ->method('setProductId');

        $this->updateCatalog->execute(2);
    }

    public function testIfProductExistsNewEntryCreated()
    {
        $this->catalogFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->catalogMock);

        $this->catalogMock->expects($this->once())
            ->method('loadProductById')
            ->with(2)
            ->willReturn($this->catalogMock);

        $this->catalogMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->catalogMock->expects($this->once())
            ->method('setProductId');

        $this->updateCatalog->execute(2);
    }
}
