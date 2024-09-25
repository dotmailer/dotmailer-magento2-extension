<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchStrategyFactoryInterface;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender\SenderStrategyInterface;
use Dotdigitalgroup\Email\Model\Importer;
use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;

class SenderStrategyFactory implements BatchStrategyFactoryInterface
{
    /**
     * @var ObjectManagerInterface Magento's object manager for creating instances.
     */
    protected $objectManager;

    /**
     * @var array Mapping of import types to their corresponding strategy class names.
     */
    protected $strategies = [
        Importer::IMPORT_TYPE_CUSTOMER => ContactSenderStrategy::class,
        Importer::IMPORT_TYPE_GUEST => ContactSenderStrategy::class,
        Importer::IMPORT_TYPE_SUBSCRIBERS => ContactSenderStrategy::class,
        Importer::IMPORT_TYPE_CONSENT => ContactSenderStrategy::class,
        Importer::IMPORT_TYPE_ORDERS => OrderSenderStrategy::class,
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
    public function create(string $importType): SenderStrategyInterface
    {
        if (!isset($this->strategies[$importType])) {
            throw new InvalidArgumentException("Unknown sender strategy for type {$importType}");
        }

        $className = $this->strategies[$importType];
        return $this->objectManager->create($className);
    }
}
