<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
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
     * @var $importerResource
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
     * @param Importer $importerResource
     * @param ImporterCurlErrorChecker $curlErrorChecker
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Importer $importerResource,
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
     * @param $item
     * @param $result
     * @param string|null $apiMessage
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function handleItemAfterSync($item, $result, $apiMessage = null)
    {
        if ($this->curlErrorChecker->_checkCurlError($item, $this->getClient())) {
            return;
        }

        //api response error
        if (isset($result->message) || ! $result) {
            $message = (isset($result->message)) ? $result->message : ItemPostProcessorInterface::ERROR_UNKNOWN;
            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::FAILED)
                ->setMessage($message);
        } else {
            $dateTime = $this->dateTime->formatDate(true);
            $item->setImportStatus(\Dotdigitalgroup\Email\Model\Importer::IMPORTED)
                ->setImportFinished($dateTime)
                ->setImportStarted($dateTime)
                ->setMessage($apiMessage ?: '');
        }
        $this->importerResource->save($item);
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        return $this->_getData('client');
    }
}
