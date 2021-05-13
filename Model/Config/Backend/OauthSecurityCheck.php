<?php

namespace Dotdigitalgroup\Email\Model\Config\Backend;

class OauthSecurityCheck extends \Magento\Framework\App\Config\Value
{
    /**
     * @return void
     */
    public function beforeSave()
    {
        // @codingStandardsIgnoreLine
        $url = parse_url($this->getValue());
        if (!empty($this->getValue())
            && (!filter_var($this->getValue(), FILTER_VALIDATE_URL) || $url['scheme'] !== 'https')
        ) {
            $this->_dataSaveAllowed = false;

            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please provide a secure custom OAUTH domain.')
            );
        }
    }
}
