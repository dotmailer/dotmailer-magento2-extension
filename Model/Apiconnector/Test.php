<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * test class for validation of the api creds.
 */
class Test
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;
    
    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    private $config;

    /**
     * Test constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Config\ReinitableConfigInterface $config
    ) {
        $this->helper = $data;
        $this->config = $config;
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
        //Clear config cache
        $this->config->reinit();

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
