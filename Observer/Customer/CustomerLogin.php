<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Model\Chat\Config;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Dotdigitalgroup\Email\Model\Chat\Profile\UpdateChatProfile;
use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;

class CustomerLogin implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UpdateChatProfile
     */
    private $chatProfile;

    /**
     * @var CookieReaderInterface
     */
    private $cookieReader;

    /**
     * @param RequestInterface $request
     * @param UpdateChatProfile $chatProfile
     * @param CookieReaderInterface $cookieReader
     */
    public function __construct(
        RequestInterface $request,
        UpdateChatProfile $chatProfile,
        CookieReaderInterface $cookieReader
    ) {
        $this->request = $request;
        $this->chatProfile = $chatProfile;
        $this->cookieReader = $cookieReader;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $chatProfileId = $this->cookieReader->getCookie(Config::COOKIE_CHAT_PROFILE, null);
        if ($chatProfileId) {
            $this->chatProfile->update($chatProfileId);
        }
    }
}
