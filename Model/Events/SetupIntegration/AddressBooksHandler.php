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
            $this->logger->debug('Error in createAddressBooks', ['exception' => $exception]);
            return $this->encode([
                'success' => false,
                'data' => "Error when creating address books - please check the Log Viewer",
            ]);
        }

        $message = $addressBooksStatus ?
            "Address books created" :
            "Address book setup failed - please check the Log Viewer";

        return $this->encode([
            'success' => $addressBooksStatus,
            'data' => $message,
        ]);
    }
}
