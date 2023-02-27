<?php

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory;

class OrderHistoryChecker
{
    /**
     * @var OrderSearchResultInterfaceFactory
     */
    private $orderSearchResultInterfaceFactory;

    /**
     * @param OrderSearchResultInterfaceFactory $orderSearchResultInterfaceFactory
     */
    public function __construct(
        OrderSearchResultInterfaceFactory $orderSearchResultInterfaceFactory
    ) {
        $this->orderSearchResultInterfaceFactory = $orderSearchResultInterfaceFactory;
    }

    /**
     * Check emails exist in sales order table.
     *
     * This method has some extra logic to return emails in sales,
     * preserving the original key.
     *
     * @param array $emails
     *
     * @return array
     */
    public function checkInSales(array $emails): array
    {
        /** @var \Magento\Framework\Data\Collection\AbstractDb $orderSearchResultsInterface */
        $orderSearchResultsInterface = $this->orderSearchResultInterfaceFactory->create();
        $results = $orderSearchResultsInterface
            ->addFieldToFilter('customer_email', ['in' => $emails])
            ->getColumnValues('customer_email');

        $inSales = [];
        foreach ($results as $inSalesEmail) {
            if (($key = array_search($inSalesEmail, $emails)) !== false) {
                $inSales[$key] = $inSalesEmail;
            }
        }

        return $inSales;
    }
}
