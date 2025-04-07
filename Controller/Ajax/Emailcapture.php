<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

use Dotdigitalgroup\Email\Api\Logger\LoggerInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;

class Emailcapture implements HttpPostActionInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Emailcapture constructor.
     *
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $cartRepository
     * @param Session $session
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        LoggerInterface $logger,
        CartRepositoryInterface $cartRepository,
        Session $session,
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        $this->logger = $logger;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $session;
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Easy email capture for Newsletter and Checkout.
     *
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $email = $this->request->getParam('email');
        $resultJson = $this->resultJsonFactory->create();

        $responseCode = '400';

        if ($email && $quote = $this->checkoutSession->getQuote()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $resultJson->setHttpResponseCode($responseCode);
                return $resultJson;
            }

            $responseCode = '200';

            if ($quote->hasItems() && $quote->getCustomerEmail() !== $email) {
                try {
                    $quote->setCustomerEmail($email);
                    $this->cartRepository->save($quote);
                } catch (\Exception $e) {
                    $this->logger->error('Error saving quote: ' . $e->getMessage());
                }
            }
        }

        $resultJson->setHttpResponseCode($responseCode);
        return $resultJson;
    }
}
