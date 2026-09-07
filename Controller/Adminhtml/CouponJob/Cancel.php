<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob as CouponJobController;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobCancellerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Cancel a single bulk coupon job.
 *
 * Only reachable from the grid's row action, which is rendered exclusively for
 * pending or processing jobs. The status check is repeated in JobCanceller so a
 * hand-crafted request cannot cancel a job that has already finished.
 */
class Cancel extends CouponJobController implements HttpPostActionInterface
{
    /**
     * @var JobCancellerInterface
     */
    private $jobCanceller;

    /**
     * @param Context $context
     * @param JobCancellerInterface $jobCanceller
     */
    public function __construct(
        Context $context,
        JobCancellerInterface $jobCanceller
    ) {
        parent::__construct($context);
        $this->jobCanceller = $jobCanceller;
    }

    /**
     * Cancel the requested job.
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $couponJobId = (int) $this->getRequest()->getParam('id');

        if ($couponJobId < 1) {
            $this->messageManager->addErrorMessage(__('Please select a coupon job to cancel.'));
            return $resultRedirect->setPath('*/*/index');
        }

        if ($this->jobCanceller->cancel($couponJobId)) {
            $this->messageManager->addSuccessMessage(
                __('Coupon job #%1 has been cancelled.', $couponJobId)
            );
        } else {
            $this->messageManager->addErrorMessage(
                __('Coupon job #%1 can no longer be cancelled.', $couponJobId)
            );
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
