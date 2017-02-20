<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Test
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Test constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->helper = $data;
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
        if (!$this->helper->isEnabled()) {
            return false;
        }

        $website = $this->helper->getWebsite();
        $client = $this->helper->getWebsiteApiClient($website);
        if ($apiUsername && $apiPassword) {
            $client->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);

            $accountInfo = $client->getAccountInfo();
            
            if (isset($accountInfo->message)) {
                $this->helper->log('VALIDATION ERROR :  ' . $accountInfo->message);

                return false;
            }

            return $accountInfo;
        }

        return false;
    }
}
