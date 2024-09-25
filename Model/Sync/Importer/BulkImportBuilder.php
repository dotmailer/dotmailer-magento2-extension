<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use InvalidArgumentException;

class BulkImportBuilder
{
    /**
     * @var array
     */
    private $config = [
        'model' => null,
        'mode' => ImporterModel::MODE_BULK,
        'type' => [],
        'limit' => Importer::TOTAL_IMPORT_SYNC_LIMIT
    ];

    /**
     * Set the model for the import configuration.
     *
     * @param AbstractItemSyncer $model
     * @return $this
     */
    public function setModel($model): BulkImportBuilder
    {
        $this->config['model'] = $model;
        return $this;
    }

    /**
     * Set the mode for the import configuration.
     *
     * @param string $mode The mode to be set.
     * @return $this
     */
    public function setMode(string $mode): BulkImportBuilder
    {
        $this->config['mode'] = $mode;
        return $this;
    }

    /**
     * Set the type for the import configuration.
     *
     * @param array $type The type to be set.
     * @return $this
     */
    public function setType(array $type): BulkImportBuilder
    {
        $this->config['type'] = $type;
        return $this;
    }

    /**
     * Set the limit for the import configuration.
     *
     * @param int $limit The limit to be set.
     * @return $this
     */
    public function setLimit(int $limit):BulkImportBuilder
    {
        $this->config['limit'] = $limit;
        return $this;
    }

    /**
     * Build and return the final configuration array.
     *
     * @return array The built configuration array.
     */
    public function build(): array
    {
        if (empty($this->config['model'])) {
            throw new InvalidArgumentException('Model is required for Bulk Import config set');
        }

        if (empty($this->config['type'])) {
            throw new InvalidArgumentException('Type is required for Bulk Import config set');
        }

        return $this->config;
    }
}
