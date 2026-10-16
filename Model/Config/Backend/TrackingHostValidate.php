<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class TrackingHostValidate extends Value
{
    /**
     * Validate the tracking host before storage.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $host = strtolower(trim((string)$this->getValue()));
        $pattern = '#\A(?:https?://)?(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*'
            . '(?:ddlnk\.net|dotdigital\.internal)\z#';

        if ($host !== '' && !preg_match($pattern, $host)) {
            throw new LocalizedException(
                __('The tracking host must be ddlnk.net, dotdigital.internal, or a subdomain of either domain.')
            );
        }

        $this->setValue($host);

        return parent::beforeSave();
    }
}
