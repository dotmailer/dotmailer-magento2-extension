<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;

class Ajaxlogcontent extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    private $file;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * Ajaxlogcontent constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\File $file,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->file = $file;
        $this->jsonHelper = $jsonHelper;
        $this->escaper = $escaper;
        parent::__construct($context);
    }

    /**
     * Ajax get log file content.
     *
     * @return null
     */
    public function execute()
    {
        $logFile = $this->getRequest()->getParam('log');
        switch ($logFile) {
            case "connector":
                $header = 'Marketing Automation Log';
                break;
            case "system":
                $header = 'Magento System Log';
                break;
            case "exception":
                $header = 'Magento Exception Log';
                break;
            case "debug":
                $header = 'Magento Debug Log';
                break;
            default:
                $header = 'Marketing Automation Log';
        }
        $content = nl2br($this->escaper->escapeHtml($this->file->getLogFileContent($logFile)));
        $response = [
            'content' => $content,
            'header' => $header
        ];
        $this->getResponse()->representJson($this->jsonHelper->jsonEncode($response));
    }
}
