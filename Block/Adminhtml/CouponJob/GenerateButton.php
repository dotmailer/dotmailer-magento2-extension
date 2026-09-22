<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Block\Adminhtml\CouponJob;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class GenerateButton implements ButtonProviderInterface
{
    /**
     * Return the Generate button config.
     *
     * The button triggers the UI form's save action which POSTs to the submitUrl.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label'          => __('Review'),
            'class'          => 'action-primary',
            'sort_order'     => 20,
            'id'             => 'save',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
        ];
    }
}
