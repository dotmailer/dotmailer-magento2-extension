<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class Catalogreset extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    private $catalogFactory;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * Catalogreset constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory
     * @param \Magento\Backend\App\Action\Context                       $context
     * @param \Dotdigitalgroup\Email\Helper\Data                        $data
     * @param \Magento\Framework\Escaper                                $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->catalogFactory = $catalogFactory;
        $this->messageManager = $context->getMessageManager();
        $this->helper = $data;
        $this->escaper = $escaper;
        parent::__construct($context);
    }

    /**
     * Refresh suppressed contacts.
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $from = $this->escaper->escapeHtml($params['from']);
        $to = $this->escaper->escapeHtml($params['to']);
        if ($from && $to) {
            $error = $this->helper->validateDateRange(
                $from,
                $to
            );
            if (is_string($error)) {
                $this->messageManager->addErrorMessage($error);
            } else {
                $this->catalogFactory->create()
                    ->resetCatalog($from, $to);
                $this->messageManager->addSuccessMessage(__('Done.'));
            }
        } else {
            $this->catalogFactory->create()
                ->resetCatalog();
            $this->messageManager->addSuccessMessage(__('Done.'));
        }

        $redirectUrl = $this->getUrl(
            'adminhtml/system_config/edit',
            ['section' => 'dotdigitalgroup_developer_settings']
        );
        $this->_redirect($redirectUrl);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::config');
    }
}
