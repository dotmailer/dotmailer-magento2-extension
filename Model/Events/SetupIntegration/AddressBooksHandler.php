<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;

class AddressBooksHandler extends AbstractSetupIntegrationHandler
{
    /**
     * @var IntegrationSetup
     */
    private $integrationSetup;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param Context $context
     * @param IntegrationSetup $integrationSetup
     */
    public function __construct(
        Logger $logger,
        Context $context,
        IntegrationSetup $integrationSetup
    ) {
        $this->request = $context->getRequest();
        $this->logger = $logger;
        $this->integrationSetup = $integrationSetup;
    }

    /**
     * Event Process
     *
     * @return string
     */
    public function update():string
    {
        try {
            $websiteId = $this->request->getParam('website', 0);
            $addressBooksStatus = $this->integrationSetup->createAddressBooks($websiteId);
        } catch (\Exception $exception) {
            $this->logger->debug('Error when creating lists', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Error when creating lists - please check the Log Viewer",
            ]);
        }

        $message = $addressBooksStatus ?
            "Lists created" :
            "List setup failed - please check the Log Viewer";

        return $this->encode([
            'success' => $addressBooksStatus,
            'data' => $message,
        ]);
    }
}
