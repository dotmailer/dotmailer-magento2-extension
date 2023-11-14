<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterCurlErrorChecker;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DataObject;

class BulkItemPostProcessor extends DataObject implements ItemPostProcessorInterface
{
    /**
     * @var Importer
     */
    private $importerResource;

    /**
     * @var ImporterCurlErrorChecker
     */
    private $curlErrorChecker;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * BulkItemPostProcessor constructor.
     *
     * @param Importer $importerResource
     * @param ImporterCurlErrorChecker $curlErrorChecker
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        Importer $importerResource,
        ImporterCurlErrorChecker $curlErrorChecker,
        DateTime $dateTime,
        array $data = []
    ) {
        $this->importerResource = $importerResource;
        $this->curlErrorChecker = $curlErrorChecker;
        $this->dateTime = $dateTime;

        parent::__construct($data);
    }

    /**
     * Handle item after sync.
     *
     * @param ImporterModel $item
     * @param mixed $result
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function handleItemAfterSync($item, $result)
    {
        if ($this->curlErrorChecker->_checkCurlError($item, $this->getClient())) {
            return;
        }

        if (isset($result->message) && !isset($result->id)) {
            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                ->setMessage($result->message);

            $this->importerResource->save($item);
        } elseif (isset($result->id) && !isset($result->message)) {
            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTING)
                ->setImportId($result->id)
                ->setImportStarted($this->dateTime->formatDate(true))
                ->setMessage('');
            $this->importerResource->save($item);
        } else {
            $message = (isset($result->message)) ? $result->message : ItemPostProcessorInterface::ERROR_UNKNOWN;

            // Requeue imports if import limit has been exceeded
            if (strpos($message, 'ERROR_IMPORT_TOOMANYACTIVEIMPORTS') !== false) {
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::NOT_IMPORTED);
            } else {
                $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED);
            }

            $item->setMessage($message);

            //If result id
            if (isset($result->id)) {
                $item->setImportId($result->id);
            }

            $this->importerResource->save($item);
        }
    }

    /**
     * Get client.
     *
     * @return Client
     */
    private function getClient()
    {
        return $this->_getData('client');
    }
}
