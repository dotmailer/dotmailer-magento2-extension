<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class DataFieldsHandler extends AbstractSetupIntegrationHandler
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
