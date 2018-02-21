<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Reviewsreset extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review
     */
    private $review;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * Reviewsreset constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review $review
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Review $review,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->review  = $review;
        $this->messageManager = $context->getMessageManager();
        $this->helper         = $data;
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     *
     * @return null
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $from = $params['from'];
        $to = $params['to'];
        if ($from && $to) {
            $error = $this->helper->validateDateRange(
                $from,
                $to
            );
            if (is_string($error)) {
                $this->messageManager->addErrorMessage($error);
            } else {
                $this->review->resetReviews($from, $to);
                $this->messageManager->addSuccessMessage(__('Done.'));
            }
        } else {
            $this->review->resetReviews();
            $this->messageManager->addSuccessMessage(__('Done.'));
        }

        $redirectUrl = $this->getUrl(
            'adminhtml/system_config/edit',
            ['section' => 'connector_developer_settings']
        );
        $this->_redirect($redirectUrl);
    }
}
