<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Controller\Adminhtml\MassDeleteCsrf;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Delete the selected bulk coupon jobs.
 *
 * Only jobs in a terminal state are deleted. The filter is applied server side
 * because a "Select All" massaction submits an exclusion set rather than a list of
 * ids, so the grid JS cannot vet the selection on its own.
 */
class MassDelete extends MassDeleteCsrf
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::coupon_job';

    /**
     * @param CouponJobResource $collectionResource
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CouponJobResource $collectionResource,
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }

    /**
     * Delete every selected job that has reached a terminal state.
     *
     * The selection is resolved once — Filter::getCollection() re-applies its
     * criteria to the shared data provider on every call — and the terminal-status
     * split is then made in memory.
     *
     * @return Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $deleted = 0;
        $skipped = 0;

        foreach ($collection as $item) {
            if (!in_array((string) $item->getData('status'), CouponJobInterface::TERMINAL_STATUSES, true)) {
                $skipped++;
                continue;
            }

            $this->collectionResource->delete($item);
            $deleted++;
        }

        if ($deleted > 0) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $deleted)
            );
        }

        if ($skipped > 0) {
            $this->messageManager->addNoticeMessage(
                __('%1 record(s) were skipped because they are still running.', $skipped)
            );
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
