<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterCurlErrorChecker;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DataObject;

class SingleItemPostProcessor extends DataObject implements ItemPostProcessorInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ImporterResource
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
     * SingleItemPostProcessor constructor.
     * @param Data $helper
     * @param ImporterResource $importerResource
     * @param ImporterCurlErrorChecker $curlErrorChecker
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        Data $helper,
        ImporterResource $importerResource,
        ImporterCurlErrorChecker $curlErrorChecker,
        DateTime $dateTime,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->importerResource = $importerResource;
        $this->curlErrorChecker = $curlErrorChecker;
        $this->dateTime = $dateTime;

        parent::__construct($data);
    }

    /**
     * Handle single import items after sync.
     *
     * @param ImporterModel $item
     * @param \stdClass $result
     * @param string|null $apiMessage
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function handleItemAfterSync($item, $result, $apiMessage = null)
    {
        if ($this->curlErrorChecker->_checkCurlError($item, $this->getClient())) {
            return;
        }

        //api response error
        if (isset($result->message) && !isset($result->id)) {
            $item->setImportStatus(ImporterModel::FAILED)
                ->setMessage($result->message);
        } else {
            $dateTime = $this->dateTime->formatDate(true);
            $item->setImportStatus(ImporterModel::IMPORTED)
                ->setImportFinished($dateTime)
                ->setImportStarted($dateTime)
                ->setMessage($apiMessage ?: '');
        }
        $this->importerResource->save($item);
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
