<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

use Dotdigitalgroup\Email\Model\Chat\Config;
use Dotdigitalgroup\Email\Model\Chat\Profile\UpdateChatProfile;
use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;

class Emailcapture extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var UpdateChatProfile
     */
    private $chatProfile;

    /**
     * @var CookieReaderInterface
     */
    private $cookieReader;

    /**
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\App\Action\Context $context
     * @param UpdateChatProfile $chatProfile
     * @param CookieReaderInterface $cookieReader
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        UpdateChatProfile $chatProfile,
        CookieReaderInterface $cookieReader
    ) {
        $this->helper = $data;
        $this->quoteResource = $quoteResource;
        $this->checkoutSession = $session;
        $this->chatProfile = $chatProfile;
        $this->cookieReader = $cookieReader;
        parent::__construct($context);
    }

    /**
     * Easy email capture for Newsletter and Checkout.
     *
     * @return null
     */
    public function execute()
    {
        $email = $this->getRequest()->getParam('email');

        if ($email && $quote = $this->checkoutSession->getQuote()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            if ($quote->hasItems() && !$quote->getCustomerEmail()) {
                try {
                    $quote->setCustomerEmail($email);
                    $this->quoteResource->save($quote);
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }
            }

            // if a chat profile ID is present, update chat profile data
            if ($chatProfileId = $this->cookieReader->getCookie(Config::COOKIE_CHAT_PROFILE, null)) {
                $this->chatProfile->update($chatProfileId, $email);
            }
        }
    }
}
