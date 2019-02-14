<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Wishlist extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @return string
     */
    public function getLink()
    {
        return $this->getUrl(
            'dotdigitalgroup_email/wishlist/index'
        );
    }
}
