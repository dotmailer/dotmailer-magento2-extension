<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class ProductsHandler extends AbstractSetupIntegrationHandler
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
            $sendStatus = $this->integrationSetup->sendProducts($websiteId);
        } catch (\Exception $exception) {
            $this->logger->debug('Error in sendProducts', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Error when sending orders - please check the Log Viewer",
            ]);
        }

        $message = $sendStatus ?
            "Products preload" :
            "Products preload failed - please check the Log Viewer";

        return $this->encode([
            'success' => $sendStatus,
            'data' => $message
        ]);
    }
}
