<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class EnableSyncsHandler extends AbstractSetupIntegrationHandler
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
            $this->integrationSetup->enableSyncs($websiteId);
        } catch (\Exception $exception) {
            $this->logger->debug('Error in enableSyncs', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Error when enabling syncs - please check the Log Viewer",
            ]);
        }
        return $this->encode([
            'success' => true,
            'data' => "Syncs enabled",
        ]);
    }
}
