<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Test
{

	protected $_helper;
	protected $_objectManager;
	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		\Dotdigitalgroup\Email\Helper\Data $data
	)
	{
		$this->_objectManager = $objectManagerInterface;
		$this->_helper = $data;
	}

    /**
	 * Validate apiuser on save.
	 *
	 * @param $apiUsername
	 * @param $apiPassword
	 *
	 * @return bool|mixed
	 */
    public function validate($apiUsername, $apiPassword)
    {
	    $client = $this->_helper->getWebsiteApiClient();
        if ($apiUsername && $apiPassword) {
                $client->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);

            $accountInfo = $client->getAccountInfo();
            if (isset($accountInfo->message)) {
                $this->_helper->log('VALIDATION ERROR :  ' . $accountInfo->message);
                return false;
            }
            return $accountInfo;
        }
        return false;
    }

    /**
	 * Ajax validate api user.
	 *
	 * @param $apiUsername
	 * @param $apiPassword
	 *
	 * @return bool|string
	 */
    public function ajaxvalidate($apiUsername, $apiPassword)
    {
	    //api username and apipass must be checked
        if ($apiUsername && $apiPassword) {
	        $client = $this->_helper->getWebsiteApiClient();
	        //default result
            $message = 'Credentials Valid.';
	        //set the api credentials to the rest client
            $client->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);
	        //account info api request
            $response = $client->getAccountInfo();
	        //get the repsonse error message and invalidate the request
            if (isset($response->message)) {
                $message = 'API Username And Password Do Not Match!';
            }
            return $message;
        }
        return false;
    }
}
