<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Contact;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigital\V3\Utility\Pagination\ParameterCollectionFactory;
use Dotdigital\V3\Utility\PaginatorFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\Connector\DataFieldTranslator;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Dotdigitalgroup\Email\Model\Task\TaskRunInterface;
use Magento\Framework\DataObject;

class PlatformChangeManager extends DataObject implements TaskRunInterface
{
    private const BATCH_SIZE = 1000;

    /**
     * @var ParameterCollectionFactory
     */
    private $parameterCollectionFactory;

    /**
     * @var PaginatorFactory
     */
    private $paginatorFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var AccountHandler
     */
    private $accountHandler;

    /**
     * @var DataFieldTranslator
     */
    private $dataFieldTranslator;

    /**
     * @var CronFromTimeSetter
     */
    private $cronFromTimeSetter;

    /**
     * @var ContactUpdaterPool
     */
    private $contactUpdaterPool;

    /**
     * @param ParameterCollectionFactory $parameterCollectionFactory
     * @param PaginatorFactory $paginatorFactory
     * @param Logger $logger
     * @param ClientFactory $clientFactory
     * @param DataFieldTranslator $dataFieldTranslator
     * @param AccountHandler $accountHandler
     * @param CronFromTimeSetter $cronFromTimeSetter
     * @param ContactUpdaterPool $contactUpdaterPool
     * @param array $data
     */
    public function __construct(
        ParameterCollectionFactory $parameterCollectionFactory,
        PaginatorFactory $paginatorFactory,
        Logger $logger,
        ClientFactory $clientFactory,
        DataFieldTranslator $dataFieldTranslator,
        AccountHandler $accountHandler,
        CronFromTimeSetter $cronFromTimeSetter,
        ContactUpdaterPool $contactUpdaterPool,
        array $data = []
    ) {
        $this->parameterCollectionFactory = $parameterCollectionFactory;
        $this->paginatorFactory = $paginatorFactory;
        $this->logger = $logger;
        $this->clientFactory = $clientFactory;
        $this->dataFieldTranslator = $dataFieldTranslator;
        $this->accountHandler = $accountHandler;
        $this->cronFromTimeSetter = $cronFromTimeSetter;
        $this->contactUpdaterPool = $contactUpdaterPool;
        parent::__construct($data);
    }

    /**
     * Loop through active API users.
     *
     * @param int $batchSize This argument enables unit testing of the while loop.
     *
     * @return void
     */
    public function run(int $batchSize = self::BATCH_SIZE): void
    {
        if ($fromTime = $this->_getData('fromTime')) {
            $this->cronFromTimeSetter->setFromTime($fromTime);
        }

        $activeApiUsers = $this->accountHandler->getAPIUsersForECEnabledWebsites();
        if (!$activeApiUsers) {
            return;
        }

        foreach ($activeApiUsers as $apiUser) {
            try {
                $firstWebsiteId = (int) $apiUser['websites'][0];
                $lastSubscribedDataFieldName = $this->dataFieldTranslator->translate('LASTSUBSCRIBED', $firstWebsiteId);
                $this->batchProcessModifiedContacts($apiUser['websites'], $batchSize, $lastSubscribedDataFieldName);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Loop through modified Dotdigital contacts and hand off to the ContactUpdaterPool.
     *
     * @param array $websiteIds
     * @param int $batchSize
     * @param string $lastSubscribedDataFieldName
     *
     * @return void
     * @throws \Exception
     */
    private function batchProcessModifiedContacts(
        array $websiteIds,
        int $batchSize,
        string $lastSubscribedDataFieldName
    ): void {
        $client = $this->clientFactory->create([
            'data' => [
                'websiteId' => $websiteIds[0]
            ]
        ]);

        $criteria = $this->parameterCollectionFactory->create()
            ->setParam('data-fields', $lastSubscribedDataFieldName)
            ->setParam('include', 'channelProperties')
            ->setParam(
                '~modified',
                sprintf('gte::%s', $this->cronFromTimeSetter->getFromTime())
            )
            ->setParam('limit', $batchSize);

        $pagination = $this->paginatorFactory->create()
            ->setModel(SdkContact::class)
            ->setResource($client->contacts)
            ->setParameters($criteria);

        $loopStart = true;

        do {
            try {
                if ($loopStart) {
                    $apiContacts = $pagination->paginate()->getItems()->all();
                    $loopStart = false;
                } else {
                    $apiContacts = $pagination->next()->getItems()->all();
                }

                if (!is_array($apiContacts)) {
                    break;
                }

                $this->contactUpdaterPool->execute($apiContacts, $websiteIds);
            } catch (\Exception $e) {
                $this->logger->debug((string) $e);
                break;
            }
        } while ($pagination->hasNext());
    }
}
