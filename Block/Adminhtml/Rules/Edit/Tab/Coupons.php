<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit\Tab;

class Coupons extends \Magento\Framework\View\Element\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('dotdigital Coupon URL Builder');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('dotdigital Coupon URL Builder');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setCanShow($canShow)
    {
        $this->_data['config']['canShow'] = $canShow;
    }
}
