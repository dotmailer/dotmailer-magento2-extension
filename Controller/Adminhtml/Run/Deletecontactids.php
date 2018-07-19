<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Deletecontactids extends \Magento\Backend\App\AbstractAction
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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contact;

    /**
     * Deletecontactids constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contact
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contact,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->contact = $contact;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null
     */
    public function execute()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_developer_settings']);

        $result = $this->contact->deleteContactIds();

        $this->messageManager->addSuccessMessage('Contact id\'s reseted ' . $result);

        $this->_redirect($redirectUrl);
    }
}
