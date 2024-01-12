<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\AbstractButton;
use Magento\Backend\Block\Template\Context;

class ButtonSync extends AbstractButton
{
    /**
     * @var string
     */
    private $syncType;

    /**
     * @var string
     */
    private $modulePath;

    /**
     * @param Context $context
     * @param string $syncType
     * @param string $modulePath
     * @param array $data
     */
    public function __construct(
        Context $context,
        string $syncType,
        string $modulePath = 'dotdigitalgroup_email',
        array $data = []
    ) {
        $this->modulePath = $modulePath;
        $this->syncType = $syncType;
        parent::__construct($context, $data);
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    protected function getDisabled()
    {
        return false;
    }

    /**
     * Get button label.
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function getButtonLabel()
    {
        return  __('Run Now');
    }

    /**
     * Get button url.
     *
     * @return string
     */
    protected function getButtonUrl()
    {
        $query = [
            '_query' => [
                'sync-type' => $this->syncType
            ]
        ];
        return $this->_urlBuilder->getUrl(
            sprintf('%s/run/sync', $this->modulePath),
            $query
        );
    }
}
