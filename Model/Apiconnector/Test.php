<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Test
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;

    /**
     * Test constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_helper = $data;
    }

    /**
     * Validate apiuser on save.
     *
     * @param string $apiUsername
     * @param string $apiPassword
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
                $this->_helper->log('VALIDATION ERROR :  '
                    . $accountInfo->message);

                return false;
            }

            return $accountInfo;
        }

        return false;
    }
}
