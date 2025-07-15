<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;

class DataFieldsHandler extends AbstractSetupIntegrationHandler
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
    public function update(): string
    {
        try {
            $websiteId = (int) $this->request->getParam('website', 0);
            $dataFieldsStatus = $this->integrationSetup->setupDataFields($websiteId);
        } catch (\Exception $exception) {
            $this->logger->debug('Error in setupDataFields', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Error when setting up data fields - please check the Log Viewer",
            ]);
        }

        $message = $dataFieldsStatus ?
            "Data fields mapped" :
            "Data fields setup failed - please check the Log Viewer";

        return $this->encode([
            'success' => $dataFieldsStatus,
            'data' => $message,
        ]);
    }
}
