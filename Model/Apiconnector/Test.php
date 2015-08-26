<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Test extends \Dotdigitalgroup\Email\Model\Apiconnector\Client
{

	public function __construct()
	{

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
        if ($apiUsername && $apiPassword) {
                $this->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);

            $accountInfo = $this->getAccountInfo();
            if (isset($accountInfo->message)) {
                Mage::getSingleton('adminhtml/session')->addError($accountInfo->message);
                Mage::helper('ddg')->log('VALIDATION ERROR :  ' . $accountInfo->message);
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
	        //default result
            $message = 'Credentials Valid.';
	        //set the api credentials to the rest client
            $this->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);
	        //account info api request
            $response = $this->getAccountInfo();
	        //get the repsonse error message and invalidate the request
            if (isset($response->message)) {
                $message = 'API Username And Password Do Not Match!';
            }
            return $message;
        }
        return false;
    }
}
