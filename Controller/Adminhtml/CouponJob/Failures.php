<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Streamed, paged JSON feed of a coupon job's failure detail.
 *
 * Backs the "View" failure modals in the coupon job grid. Reads the per-job
 * NDJSON log file (imports.log / messages.log) a page at a time via
 * {@see FailureLogInterface}, so arbitrarily large failure sets are safe to serve.
 */
class Failures extends Action
{
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::coupon_job';

    private const DEFAULT_PAGE_SIZE = 10;
    private const MAX_PAGE_SIZE = 10;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var FailureLogInterface
     */
    private $failureLog;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param FailureLogInterface $failureLog
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        FailureLogInterface $failureLog,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->failureLog = $failureLog;
        $this->logger = $logger;
    }

    /**
     * Return one page of failure detail for the requested job and category.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->jsonFactory->create();
        $request = $this->getRequest();

        $couponJobId = (int) $request->getParam('id', 0);
        $category = (string) $request->getParam('category', '');
        $page = max(1, (int) $request->getParam('page', 1));
        $pageSize = (int) $request->getParam('pageSize', self::DEFAULT_PAGE_SIZE);
        $pageSize = max(1, min($pageSize, self::MAX_PAGE_SIZE));

        if ($couponJobId < 1
            || !in_array(
                $category,
                [FailureLogInterface::CATEGORY_IMPORTS, FailureLogInterface::CATEGORY_MESSAGES],
                true
            )
        ) {
            return $result->setData([
                'items' => [],
                'totalRecords' => 0,
                'error' => (string) __('Invalid request.'),
            ]);
        }

        try {
            $total = $this->failureLog->count($couponJobId, $category);
            $items = $this->failureLog->read($couponJobId, $category, ($page - 1) * $pageSize, $pageSize);

            return $result->setData([
                'items' => $items,
                'totalRecords' => $total,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'CouponJob Failures controller: job %d, category %s: %s',
                    $couponJobId,
                    $category,
                    $e->getMessage()
                )
            );
            return $result->setData([
                'items' => [],
                'totalRecords' => 0,
                'error' => (string) __('Unable to load failure detail.'),
            ]);
        }
    }
}
