<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\CouponJob;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as BackendPage;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Form extends Action
{
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::coupon_job';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory     = $resultPageFactory;
        $this->storeManager          = $storeManager;
    }

    /**
     * Execute method
     *
     * @return BackendPage
     * @throws LocalizedException
     */
    public function execute()
    {
        $websiteId = (int) $this->getRequest()->getParam('website', 0);
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        $this->storeManager->setCurrentStore($website->getDefaultStore());

        /** @var BackendPage $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dotdigitalgroup_Email::coupon_job');
        $resultPage->getConfig()->getTitle()->prepend(__('Coupon Job Form'));
        return $resultPage;
    }
}
