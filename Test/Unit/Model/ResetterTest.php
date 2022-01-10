<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model;

use Dotdigitalgroup\Email\Model\Catalog;
use Dotdigitalgroup\Email\Model\Order;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Review;
use Dotdigitalgroup\Email\Model\Wishlist;
use Dotdigitalgroup\Email\Model\Subscriber;
use Dotdigitalgroup\Email\Model\Resetter;

use PHPUnit\Framework\TestCase;

class ResetterTest extends TestCase
{
    /**
     * @var Catalog|\PHPUnit\Framework\MockObject\MockObject
     */
    private $catalogMock;

    /**
     * @var Order|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderMock;

    /**
     * @var Contact|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactMock;

    /**
     * @var Review|\PHPUnit\Framework\MockObject\MockObject
     */
    private $reviewMock;

    /**
     * @var Wishlist|\PHPUnit\Framework\MockObject\MockObject
     */
    private $wishlistMock;

    /**
     * @var Resetter
     */
    private $resetter;

    /**
     * @var Subscriber|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriberMock;

    protected function setUp(): void
    {
        $this->catalogMock = $this->createMock(Catalog::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->contactMock = $this->createMock(Contact::class);
        $this->reviewMock = $this->createMock(Review::class);
        $this->wishlistMock = $this->createMock(Wishlist::class);
        $this->subscriberMock = $this->createMock(Subscriber::class);

        $this->resetter = new Resetter(
            [
                'catalog' => $this->catalogMock,
                'order' => $this->orderMock,
                'review' => $this->reviewMock,
                'wishlist' =>$this->wishlistMock,
                'contact' => $this->contactMock,
                'subscriber' => $this->subscriberMock
            ]
        );
    }

    public function testThatGivenCatalogResetTypeDataWillReset()
    {
        $this->catalogMock->expects($this->once())
            ->method('reset');

        $this->resetter->reset("2011-01-01", "2012-01-01", "catalog");
    }

    public function testThatGivenOrderResetTypeDataWillReset()
    {
        $this->orderMock->expects($this->once())
            ->method('reset');

        $this->resetter->reset("2011-01-01", "2012-01-01", "order");
    }

    public function testThatGivenContactResetTypeDataWillReset()
    {
        $this->contactMock->expects($this->once())
            ->method('reset');

        $this->resetter->reset("2011-01-01", "2012-01-01", "contact");
    }

    public function testThatGivenReviewResetTypeDataWillReset()
    {
        $this->reviewMock->expects($this->once())
            ->method('reset');

        $this->resetter->reset("2011-01-01", "2012-01-01", "review");
    }

    public function testThatGivenWishlistResetTypeDataWillReset()
    {
        $this->wishlistMock->expects($this->once())
            ->method('reset');

        $this->resetter->reset("2011-01-01", "2012-01-01", "wishlist");
    }

    public function testThatGivenSubscriberResetTypeDataWillReset()
    {
        $this->subscriberMock->expects($this->once())
            ->method('reset');

        $this->resetter->reset("2011-01-01", "2012-01-01", "subscriber");
    }

    public function testThatGivenInvalidResetTypeExceptionWillBeThrown()
    {
        $this->expectExceptionMessage("Invalid reset type.");

        $this->resetter->reset("2011-01-01", "2012-01-01", "chazType");
    }
}
