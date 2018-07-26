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

        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

        if (!$this->helper->isEnabled($website)) {
            return false;
        }

        if ($apiPassword == '******') {
            $apiPassword = $this->helper->getApiPassword($website);
        }

        $client = $this->helper->clientFactory->create();
        if ($apiUsername && $apiPassword) {
            $client->setApiUsername($apiUsername)
                ->setApiPassword($apiPassword);

            $accountInfo = $client->getAccountInfo();
            
            if (isset($accountInfo->message)) {
                $this->helper->log('VALIDATION ERROR :  ' . $accountInfo->message);

                return false;
            }

            // If api endpoint then force save
            if ($apiEndpoint = $this->getApiEndPoint($accountInfo)) {
                $this->helper->saveApiEndpoint($apiEndpoint, $website->getId());
            }
            return $accountInfo;
        }

        return false;
    }

    /**
     * Get api endpoint
     *
     * @param Object|null $accountInfo
     * @return string
     */
    private function getApiEndPoint($accountInfo)
    {
        if (is_object($accountInfo)) {
            //save endpoint for account
            foreach ($accountInfo->properties as $property) {
                if ($property->name == 'ApiEndpoint' && !empty($property->value)) {
                    return $property->value;
                }
            }
        }
    }
}
