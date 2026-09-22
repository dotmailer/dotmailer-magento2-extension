<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Plugin\Session;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionStartChecker;

/**
 * Prevents a PHP session being started for the EDC coupon endpoint.
 *
 * Magento\Framework\Session\SessionManager::__construct() calls start(), so merely constructing
 * Magento\Customer\Model\Session (which the frontend Customer ContextPlugin does on every request)
 * is enough to open a session, read from session storage, write it back and emit a Set-Cookie.
 *
 * The coupon endpoint is a machine-to-machine call made by email clients / image proxies. Every
 * request is a distinct "visitor", so every request creates a brand new, immediately orphaned
 * session record.
 *
 * When this checker returns false, SessionManager::start() also skips storage->init(), which means
 * session reads on this path return empty and session writes stay process-local for the duration
 * of the request. That is acceptable *only* because the coupon response is entirely
 * session-independent - nothing in Block\Coupon, DotdigitalCouponRequestProcessor or
 * DotdigitalCouponGenerator reads or writes session data.
 *
 * Scope is deliberately limited to the exact route connector/email/coupon. The 'connector' front
 * name also serves Ajax\Emailcapture, Customer\Index, Customer\Newsletter, Email\Basket,
 * Email\Getbasket, Email\Callback and Email\Accountcallback, several of which do depend on session
 * state - disabling sessions for connector/* would be a breaking change.
 *
 * Modelled on Magento\Paypal\Plugin\TransparentSessionChecker.
 */
class EdcSessionStartChecker
{
    /**
     * Matches the complete route-segment sequence connector/email/coupon, allowing an optional
     * store-code / base-path prefix and Magento path-style parameters after the action name,
     * e.g. /uk/connector/email/coupon/id/1/code/passcode.
     *
     * Deliberately not a bare strpos() - that would also match connector/email/coupon-extra
     * and connector/email/couponing.
     */
    private const EDC_COUPON_PATH_PATTERN = '#(?:^|/)connector/email/coupon(?:/|$)#i';

    /**
     * @var Http
     */
    private $request;

    /**
     * @param Http $request
     */
    public function __construct(Http $request)
    {
        $this->request = $request;
    }

    /**
     * Skip session start for the EDC coupon endpoint.
     *
     * @param SessionStartChecker $subject
     * @param bool $result
     * @return bool
     * phpcs:disable Magento2.CodeAnalysis.UnusedSubjectVariable
     */
    public function afterCheck(SessionStartChecker $subject, bool $result): bool
    {
        // Preserve the CLI-SAPI / already-negative guard from the subject.
        if (!$result) {
            return false;
        }

        $pathInfo = (string) $this->request->getPathInfo();

        if ($pathInfo === '') {
            return $result;
        }

        return preg_match(self::EDC_COUPON_PATH_PATTERN, trim($pathInfo)) !== 1;
    }
}
