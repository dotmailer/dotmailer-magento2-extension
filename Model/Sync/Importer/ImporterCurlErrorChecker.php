<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;

class ImporterCurlErrorChecker
{
    /**
     * @var Importer
     */
    private $importerResource;

    /**
     * ImporterCurlErrorChecker constructor.
     * @param Importer $importerResource
     */
    public function __construct(
        Importer $importerResource
    ) {
        $this->importerResource = $importerResource;
    }

    /**
     * Check curl error.
     *
     * @param mixed $item
     * @param Client $client
     * @return bool
     */
    public function _checkCurlError($item, $client)
    {
        //if curl error 28
        $curlError = $client->getCurlError();
        if ($curlError) {
            $item->setMessage($curlError)
                ->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED);
            $this->importerResource->save($item);

            return true;
        }

        return false;
    }
}
