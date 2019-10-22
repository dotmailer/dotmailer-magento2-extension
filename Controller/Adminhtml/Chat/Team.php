<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Chat;

use Magento\Backend\App\Action;
use Dotdigitalgroup\Email\Model\Chat\Config;

class Team extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::iframe';

    /**
     * @var Config
     */
    private $config;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param Config $config
     */
    public function __construct(
        Action\Context $context,
        Config $config
    ) {
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
