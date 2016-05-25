<?php

namespace Dotdigitalgroup\Email\Controller;

class Response extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;

    /**
     * Response constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Action\Context $context

    ) {
        $this->_helper = $data;
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function authenticate()
    {
        //authenticate ip address
        $authIp = $this->_helper->authIpAddress();
        if (!$authIp) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You are not authorised to view content of this page.')
            );
        }

        //authenticate
        $auth = $this->_helper->auth($this->getRequest()->getParam('code'));
        if (!$auth) {
            $this->sendResponse();
            return;
        }
    }

    public function execute()
    {

    }

    /**
     * Send empty response.
     */
    protected function sendResponse()
    {
        try {
            $this->getResponse()
                ->setHttpResponseCode(204)
                ->setHeader('Pragma', 'public', true)
                ->setHeader(
                    'Cache-Control',
                    'must-revalidate, post-check=0, pre-check=0', true
                )
                ->setHeader('Content-type', 'text/html; charset=UTF-8', true);
            $this->getResponse()->sendHeaders();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
        }
    }

    /**
     * Check for non empty content
     * @param      $output
     * @param bool $flag
     */
    protected function checkContentNotEmpty($output, $flag = true)
    {
        try {
            if (strlen($output) < 3 && $flag == false) {
                $this->sendResponse();
            } elseif ($flag && !strpos($output, '<table') !== false) {
                $this->sendResponse();
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
        }
    }
}
