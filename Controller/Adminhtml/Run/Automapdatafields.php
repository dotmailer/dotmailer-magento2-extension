<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Connector\DataFieldAutoMapperFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;

class Automapdatafields extends Action implements HttpGetActionInterface
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
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $data;

    /**
     * @var DataFieldAutoMapperFactory
     */
    private $dataFieldAutoMapperFactory;

    /**
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Backend\App\Action\Context $context
     * @param DataFieldAutoMapperFactory $dataFieldAutoMapperFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\App\Action\Context $context,
        DataFieldAutoMapperFactory $dataFieldAutoMapperFactory
    ) {
        $this->data           = $data;
        $this->messageManager = $context->getMessageManager();
        $this->dataFieldAutoMapperFactory = $dataFieldAutoMapperFactory;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website', 0);
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit', [
            'section' => 'connector_developer_settings',
            'website' => $websiteId
        ]);

        if (!$this->data->isEnabled($websiteId)) {
            $this->messageManager->addNoticeMessage(
                sprintf('Your API connection is not enabled for website %s.
                If you have an account configured at website level, try changing scope.', $websiteId)
            );
            return $this->_redirect($redirectUrl);
        }

        try {
            $dataFieldAutoMapper = $this->dataFieldAutoMapperFactory->create()
                ->run($websiteId);
        } catch (\Exception $e) {
            $this->messageManager
                ->addNoticeMessage('Dotdigital connector API endpoint cannot be empty.');

            return $this->_redirect($redirectUrl);
        }

        if ($errors = $dataFieldAutoMapper->getMappingErrors()) {
            $this->messageManager
                ->addNoticeMessage('There were some errors mapping data fields. Please check the connector log.');
        } else {
            $this->messageManager
                ->addSuccessMessage('All data fields created and mapped to your Dotdigital account.');
        }

        $this->_redirect($redirectUrl);
    }
}
