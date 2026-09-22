<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Context;

/**
 * Immutable context for a single in-progress import group.
 */
class InProgressImportContext
{
    /**
     * @var string
     */
    private $handlerCode;

    /**
     * @var string
     */
    private $importMode;

    /**
     * @var string[]
     */
    private $importTypes;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string|null
     */
    private $resource;

    /**
     * @param string $handlerCode
     * @param string $importMode
     * @param string[] $importTypes
     * @param string $method
     * @param string|null $resource
     */
    public function __construct(
        string $handlerCode,
        string $importMode,
        array $importTypes,
        string $method,
        ?string $resource = null
    ) {
        $this->handlerCode = $handlerCode;
        $this->importMode = $importMode;
        $this->importTypes = $importTypes;
        $this->method = $method;
        $this->resource = $resource;
    }

    /**
     * Get handler code.
     *
     * @return string
     */
    public function getHandlerCode(): string
    {
        return $this->handlerCode;
    }

    /**
     * Get import mode.
     *
     * @return string
     */
    public function getImportMode(): string
    {
        return $this->importMode;
    }

    /**
     * Get import types.
     *
     * @return string[]
     */
    public function getImportTypes(): array
    {
        return $this->importTypes;
    }

    /**
     * Get method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get resource.
     *
     * @return string|null
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }
}
