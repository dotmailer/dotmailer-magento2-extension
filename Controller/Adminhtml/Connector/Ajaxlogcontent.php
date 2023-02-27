<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Connector;

use Dotdigitalgroup\Email\Helper\File;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Ajaxlogcontent extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var File
     */
    private $file;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Ajaxlogcontent constructor.
     *
     * @param File $file
     * @param Context $context
     * @param Escaper $escaper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        File $file,
        Context $context,
        Escaper $escaper,
        JsonFactory $resultJsonFactory
    ) {
        $this->file = $file;
        $this->escaper = $escaper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Ajax get log file content.
     *
     * @return Json
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

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            [
                'content' => $content,
                'header' => $header
            ]
        );

        return $resultJson;
    }
}
