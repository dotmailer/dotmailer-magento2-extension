<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * CouponJob Index Controller
 */
class Index extends \Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dotdigitalgroup_Email::coupon_job');
        $resultPage->addBreadcrumb(__('Dotdigital Bulk Coupons'), __('Dotdigital Bulk Coupons'));
        $resultPage->addBreadcrumb(__('Reports'), __('Reports'));
        $resultPage->getConfig()->getTitle()->prepend(__('Dotdigital Bulk Coupons'));

        return $resultPage;
    }
}
