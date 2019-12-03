<?php

namespace Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment;

class Rules
{
    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    private $rulesFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * Rules constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->rulesFactory = $rulesFactory;
        $this->helper = $data;
    }

    /**
     * Apply rules to sales collection
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote\Collection|\Magento\Sales\Model\ResourceModel\Order\Collection $collection
     * @param int $storeId
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection|\Magento\Sales\Model\ResourceModel\Order\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function apply($collection, $storeId)
    {
        $ruleModel = $this->rulesFactory->create();
        $websiteId = $this->helper->storeManager->getStore($storeId)
            ->getWebsiteId();
        return $ruleModel->process(
            $collection,
            \Dotdigitalgroup\Email\Model\Rules::ABANDONED,
            $websiteId
        );
    }
}
