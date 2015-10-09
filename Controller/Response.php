<?php

namespace Dotdigitalgroup\Email\Controller;

class Response extends \Magento\Framework\App\Action\Action
{
	protected $_helper;
	/**
	 * Pass arguments for dependency injection
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 */
	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\App\Action\Context $context

	) {
		$this->_helper = $data;
		parent::__construct( $context );
	}


	public function execute(  )
	{

	}


    protected function authenticate()
    {
	    //@todo enable before going live.
        //authenticate ip address
	    return;
        $authIp = $this->_helper->authIpAddress();
        if (! $authIp) {
            throw new \Magento\Framework\Exception\LocalizedException(__('You are not authorised to view content of this page.'));
        }

        //authenticate
        $auth = $this->_helper->auth($this->getRequest()->getParam('code'));
        if (! $auth) {
            $this->sendResponse();
            exit;
        }
    }

    protected function checkContentNotEmpty($output, $flag = true)
    {
        try{
            if(strlen($output) < 3 && $flag == false)
                $this->sendResponse();
            elseif($flag && !strpos($output, '<table'))
                $this->sendResponse();
        } catch (\Exception $e) {
	        $this->_helper->debug((string)$e, array());
        }
    }

    protected function sendResponse()
    {
        try{
            $this->getResponse()
                ->setHttpResponseCode(204)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', 'text/html; charset=UTF-8', true);
            $this->getResponse()->sendHeaders();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }
    }
}
