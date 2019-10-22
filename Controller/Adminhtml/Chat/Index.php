<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Chat;

use Magento\Backend\App\Action;
use Dotdigitalgroup\Email\Model\Chat\Config;
use Dotdigitalgroup\Email\Helper\Data;

class Index extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::iframe';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param Data $helper
     * @param Config $config
     */
    public function __construct(
        Action\Context $context,
        Data $helper,
        Config $config
    ) {
        $this->helper = $helper;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        if ($this->helper->isEnabled() && !$this->config->isChatEnabled()) {
            return $this->_redirect('adminhtml/system_config/edit/section/chat_api_credentials');
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
