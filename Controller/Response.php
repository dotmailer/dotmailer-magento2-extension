<?php

namespace Dotdigitalgroup\Email\Controller;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Response extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Escaper
     */
    public $escaper;

    /**
     * Response constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->helper = $data;
        $this->escaper = $escaper;
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return mixed
     */
    public function authenticate()
    {
        $authIp = $this->helper->isAllowed();
        $code = $this->getRequest()->getParam('code');
        $auth = $this->helper->auth($code);

        if (! $authIp || ! $auth) {
            return $this->sendResponse();
        }

        return true;
    }

    /**
     *
     */
    public function execute()
    {
    }

    /**
     * @return mixed
     */
    public function sendResponse()
    {
        try {
            $this->getResponse()
                ->setHttpResponseCode(204)
                ->setHeader('Pragma', 'public', true)
                ->setHeader(
                    'Cache-Control',
                    'must-revalidate, post-check=0, pre-check=0',
                    true
                )
                ->setHeader('Content-type', 'text/html; charset=UTF-8', true);
            return $this->getResponse()->sendHeaders();
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
