<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Block\Adminhtml\CouponJob;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddNewJobButton implements ButtonProviderInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        AuthorizationInterface $authorization
    ) {
        $this->urlBuilder    = $urlBuilder;
        $this->storeManager  = $storeManager;
        $this->authorization = $authorization;
    }

    /**
     * Return button config with the default website pre-selected in the URL.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        if (!$this->authorization->isAllowed('Dotdigitalgroup_Email::coupon_job')) {
            return [];
        }

        $defaultWebsiteId = (int) $this->storeManager->getDefaultStoreView()->getWebsiteId();

        return [
            'label'    => __('Add New Job'),
            'class'    => 'primary',
            'on_click' => sprintf("location.href = '%s';", $this->urlBuilder->getUrl(
                'dotdigitalgroup_email/couponjob/form',
                ['website' => $defaultWebsiteId]
            )),
            'sort_order' => 10,
        ];
    }
}
