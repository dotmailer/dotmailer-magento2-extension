<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Ui\Component\Listing\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Row actions for the bulk coupon job grid.
 *
 * Actions are emitted per row according to the job's status, so the standard
 * Magento actions column hides anything that does not apply:
 *  - Cancel is offered only while a job is pending or processing.
 *  - Delete is offered only once a job has reached a terminal state.
 *
 * Both actions POST and carry a confirmation, which Magento_Ui/js/grid/columns/actions
 * renders through the standard confirmation modal.
 */
class Actions extends Column
{
    /**
     * Route to the cancel controller.
     */
    private const URL_PATH_CANCEL = 'dotdigitalgroup_email/couponjob/cancel';

    /**
     * Route to the delete controller.
     */
    private const URL_PATH_DELETE = 'dotdigitalgroup_email/couponjob/delete';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare data source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['id'])) {
                continue;
            }

            $jobId = (int) $item['id'];
            $status = (string) ($item['status'] ?? '');
            $actions = [];

            if (in_array($status, CouponJobInterface::CANCELLABLE_STATUSES, true)) {
                $actions['cancel'] = $this->buildCancelAction($jobId);
            }

            if (in_array($status, CouponJobInterface::TERMINAL_STATUSES, true)) {
                $actions['delete'] = $this->buildDeleteAction($jobId);
            }

            $item[$name] = $actions;
        }

        return $dataSource;
    }

    /**
     * Build the cancel row action.
     *
     * @param int $jobId
     * @return array
     */
    private function buildCancelAction(int $jobId): array
    {
        return [
            'href' => $this->urlBuilder->getUrl(self::URL_PATH_CANCEL, ['id' => $jobId]),
            'label' => __('Cancel'),
            'post' => true,
            'confirm' => [
                'title' => __('Cancel coupon job'),
                'message' => __(
                    'Are you sure you want to cancel this job? The system processes coupons in batches.'
                    . ' Cancelling will stop the job from starting any new batches, but the current batch will'
                    . ' finish processing. Coupons already generated and synced to Dotdigital cannot be undone'
                    . ' but can be overwritten.'
                ),
            ],
        ];
    }

    /**
     * Build the delete row action.
     *
     * @param int $jobId
     * @return array
     */
    private function buildDeleteAction(int $jobId): array
    {
        return [
            'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['id' => $jobId]),
            'label' => __('Delete'),
            'post' => true,
            'confirm' => [
                'title' => __('Delete coupon job'),
                'message' => __('Are you sure you want to delete this coupon job? This cannot be undone.'),
            ],
        ];
    }
}
