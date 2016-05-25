<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Test
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Test constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     * @param \Dotdigitalgroup\Email\Helper\Data        $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_objectManager = $objectManagerInterface;
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
                    .$accountInfo->message);

                return false;
            }

            return $accountInfo;
        }

        return false;
    }
}
