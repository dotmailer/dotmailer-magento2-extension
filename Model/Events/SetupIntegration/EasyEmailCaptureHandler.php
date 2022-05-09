<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class EasyEmailCaptureHandler extends AbstractSetupIntegrationHandler
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
            $this->integrationSetup->enableEasyEmailCapture($websiteId);
        } catch (\Exception $exception) {
            $this->logger->debug('Error in enableEasyEmailCapture', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Error when configuring email capture - please check the Log Viewer",
            ]);
        }
        return $this->encode([
            'success' => true,
            'data' => "Easy email capture",
        ]);
    }
}
