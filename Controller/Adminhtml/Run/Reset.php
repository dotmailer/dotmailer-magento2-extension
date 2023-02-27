<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Resetter;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;

class Reset extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Resetter
     */
    private $resetter;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Reset constructor.
     * @param Context $context
     * @param Resetter $resetter
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Resetter $resetter,
        Data $helper
    ) {
        $this->resetter = $resetter;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $from = $params['from'] ?? null;
        $to = $params['to'] ?? null;
        $resetType = $params['reset-type'];

        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = $this->getUrl(
            'adminhtml/system_config/edit',
            ['section' => 'connector_developer_settings']
        );
        $resultRedirect->setPath($redirectUrl);

        if (($from && $to)) {
            $error = $this->helper->validateDateRange(
                $from,
                $to
            );
            if (is_string($error)) {
                $this->messageManager->addErrorMessage($error);
                return $resultRedirect;
            }
        }

        try {
            $resetCount = $this->resetter->reset($from, $to, $resetType);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect;
        }

        $message = __('Done. Rows reset: %s.');
        $this->messageManager->addSuccessMessage(sprintf($message, $resetCount));
        return $resultRedirect;
    }
}
