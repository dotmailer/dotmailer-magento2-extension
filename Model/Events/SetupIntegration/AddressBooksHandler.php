<?php

namespace Dotdigitalgroup\Email\Model\Events\SetupIntegration;

class AddressBooksHandler extends AbstractSetupIntegrationHandler
{
    /**
     * Event Process
     *
     * @return string
     */
    public function update():string
    {
        try {
            $websiteId = $this->_request->getParam('website', 0);
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
