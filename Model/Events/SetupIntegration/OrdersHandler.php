<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class OrdersHandler extends AbstractSetupIntegrationHandler
{
    /**
     * Event Process
     *
     * @return string
     */
    public function update(): string
    {
        try {
            $websiteId = $this->_request->getParam('website', 0);
            $sendStatus = $this->integrationSetup->sendOrders($websiteId);
        } catch (\Exception $exception) {
            $this->logger->debug('Error in sendOrders', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Error when sending orders - please check the Log Viewer",
            ]);
        }

        $message = $sendStatus ?
            "Orders preload" :
            "Orders preload failed - please check the Log Viewer";

        return $this->encode([
            'success' => $sendStatus,
            'data' => $message
        ]);
    }
}
