<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

use Dotdigitalgroup\Email\Model\Events\EventInterface;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\RequestInterface;
use Dotdigitalgroup\Email\Logger\Logger;

abstract class AbstractSetupIntegrationHandler implements EventInterface
{
    /**
     * @var IntegrationSetup
     */
    protected $integrationSetup;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ReinitableConfigInterface
     */
    protected $reinitableConfig;

    /**
     * @param Logger $logger
     * @param Context $context
     * @param IntegrationSetup $integrationSetup
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        Logger $logger,
        Context $context,
        IntegrationSetup $integrationSetup,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->_request = $context->getRequest();
        $this->logger = $logger;
        $this->integrationSetup = $integrationSetup;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * Get Updated Data.
     *
     * @return string
     */
    abstract public function update(): string;

    /**
     * Encoded message for transport
     *
     * @param array $data
     * @return string
     */
    public function encode(array $data):string
    {
        return json_encode($data);
    }
}
