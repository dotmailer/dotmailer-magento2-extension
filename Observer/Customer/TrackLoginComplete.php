<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Flags the customer session so the Dotdigital tag can track a completed
 * login on the next page render.
 *
 * The `customer_account_loginpost` controller is a POST-only redirect
 * target and never renders a layout, so `getFullActionName()` can't be used
 * to detect a successful login. Instead, this observer listens to the
 * `customer_login` backend event (fired only on a successful login) and
 * sets a one-shot flag that `DotdigitalTagView` consumes on the following
 * page render (the customer dashboard).
 */
class TrackLoginComplete implements ObserverInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(Session $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->customerSession->setDotdigitalTrackLoginComplete(true);
    }
}
