<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchStrategyFactoryInterface;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record\RecordImportedStrategyInterface;
use Dotdigitalgroup\Email\Model\Importer;
use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;

class RecordImportedStrategyFactory implements BatchStrategyFactoryInterface
{
    /**
     * @var ObjectManagerInterface Magento's object manager for creating instances.
     */
    private $objectManager;

    /**
     * @var array Mapping of import types to their corresponding strategy class names.
     */
    private $strategies = [
        Importer::IMPORT_TYPE_CUSTOMER => ContactImportedStrategy::class,
        Importer::IMPORT_TYPE_GUEST => ContactImportedStrategy::class,
        Importer::IMPORT_TYPE_SUBSCRIBERS => SubscriberImportedStrategy::class,
        Importer::IMPORT_TYPE_ORDERS => OrderImportedStrategy::class,
    ];

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Injected object manager for class instantiation.
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @inheritDoc
     */
    public function create(string $importType): RecordImportedStrategyInterface
    {
        if (!isset($this->strategies[$importType])) {
            throw new InvalidArgumentException("Unknown record imported strategy for type {$importType}");
        }

        $className = $this->strategies[$importType];
        return $this->objectManager->create($className);
    }
}
