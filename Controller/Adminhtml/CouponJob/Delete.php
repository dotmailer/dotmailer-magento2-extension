<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob as CouponJobController;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJobFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Delete a single bulk coupon job.
 *
 * Only jobs in a terminal state may be deleted; the grid hides the row action for
 * anything else, and this check makes that authoritative.
 */
class Delete extends CouponJobController implements HttpPostActionInterface
{
    /**
     * @var CouponJobFactory
     */
    private $couponJobFactory;

    /**
     * @var CouponJobResource
     */
    private $couponJobResource;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Context $context
     * @param CouponJobFactory $couponJobFactory
     * @param CouponJobResource $couponJobResource
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        CouponJobFactory $couponJobFactory,
        CouponJobResource $couponJobResource,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->couponJobFactory = $couponJobFactory;
        $this->couponJobResource = $couponJobResource;
        $this->logger = $logger;
    }

    /**
     * Delete the requested job.
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $couponJobId = (int) $this->getRequest()->getParam('id');

        if ($couponJobId < 1) {
            $this->messageManager->addErrorMessage(__('Please select a coupon job to delete.'));
            return $resultRedirect->setPath('*/*/index');
        }

        /** @var CouponJob $couponJob */
        $couponJob = $this->couponJobFactory->create();
        $this->couponJobResource->load($couponJob, $couponJobId);

        if (!$couponJob->getId()) {
            $this->messageManager->addErrorMessage(__('This coupon job no longer exists.'));
            return $resultRedirect->setPath('*/*/index');
        }

        if (!in_array((string) $couponJob->getData('status'), CouponJobInterface::TERMINAL_STATUSES, true)) {
            $this->messageManager->addErrorMessage(
                __('Coupon job #%1 is still running and cannot be deleted.', $couponJobId)
            );
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->couponJobResource->delete($couponJob);
            $this->messageManager->addSuccessMessage(
                __('Coupon job #%1 has been deleted.', $couponJobId)
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('CouponJob Delete controller: job %d: %s', $couponJobId, $e->getMessage())
            );
            $this->messageManager->addErrorMessage(__('We could not delete this coupon job.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
