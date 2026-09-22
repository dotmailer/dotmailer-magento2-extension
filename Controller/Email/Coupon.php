<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Controller\ExternalDynamicContentController;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Coupon extends ExternalDynamicContentController
{
    /**
     * Execute, then apply a non-storable cache contract to whatever is returned.
     *
     * Coupon responses are personalised per contact and must never be stored by an intermediary
     * (corporate proxy, email image proxy, CDN or browser cache). The base controller emits
     * 'Pragma: public' with a must-revalidate policy on 401/204, and no cache contract at all on
     * the 200 layout response. This override applies a uniform no-store policy to all three.
     *
     * Kept route-local on purpose - the other EDC endpoints are out of scope.
     *
     * @return ResponseInterface|ResultInterface|void
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = parent::execute();

        if ($result === null) {
            return $result;
        }

        return $this->setNonStorableHeaders($result);
    }

    /**
     * Apply no-store cache headers.
     *
     * Both ResponseInterface (Http) and Magento\Framework\View\Result\Layout expose setHeader().
     *
     * @param ResponseInterface|ResultInterface $result
     * @return ResponseInterface|ResultInterface
     */
    private function setNonStorableHeaders($result)
    {
        if (!method_exists($result, 'setHeader')) {
            return $result;
        }

        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)
            ->setHeader('Pragma', 'no-cache', true);

        return $result;
    }
}
