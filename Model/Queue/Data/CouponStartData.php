<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Data;

class CouponStartData
{
    /**
     * @var int
     */
    private $job_id;

    /**
     * @var int
     */
    private $website_id;

    /**
     * @var string
     */
    private $filter_type;

    /**
     * @var int
     */
    private $filter_id;

    /**
     * @var string|null
     */
    private $code_format;

    /**
     * @var string|null
     */
    private $code_prefix;

    /**
     * @var string|null
     */
    private $code_suffix;

    /**
     * @var int|null
     */
    private $code_length;

    /**
     * @var int|null
     */
    private $code_dash;

    /**
     * @var string
     */
    private $data_field;

    /**
     * @var int
     */
    private $batch_size;

    /**
     * @var string|null
     */
    private $expires_at;

    /**
     * Get job ID.
     *
     * @return int
     */
    public function getJobId(): int
    {
        return $this->job_id;
    }

    /**
     * Set job ID.
     *
     * @param int $jobId
     * @return void
     */
    public function setJobId(int $jobId): void
    {
        $this->job_id = $jobId;
    }

    /**
     * Get website ID.
     *
     * @return int
     */
    public function getWebsiteId(): int
    {
        return $this->website_id;
    }

    /**
     * Set website ID.
     *
     * @param int $websiteId
     * @return void
     */
    public function setWebsiteId(int $websiteId): void
    {
        $this->website_id = $websiteId;
    }

    /**
     * Get filter type.
     *
     * @return string
     */
    public function getFilterType(): string
    {
        return $this->filter_type;
    }

    /**
     * Set filter type.
     *
     * @param string $filterType
     * @return void
     */
    public function setFilterType(string $filterType): void
    {
        $this->filter_type = $filterType;
    }

    /**
     * Get filter ID.
     *
     * @return int
     */
    public function getFilterId(): int
    {
        return $this->filter_id;
    }

    /**
     * Set filter ID.
     *
     * @param int $filterId
     * @return void
     */
    public function setFilterId(int $filterId): void
    {
        $this->filter_id = $filterId;
    }

    /**
     * Get code format.
     *
     * @return string|null
     */
    public function getCodeFormat(): ?string
    {
        return $this->code_format;
    }

    /**
     * Set code format.
     *
     * @param string|null $codeFormat
     * @return void
     */
    public function setCodeFormat(?string $codeFormat): void
    {
        $this->code_format = $codeFormat;
    }

    /**
     * Get code prefix.
     *
     * @return string|null
     */
    public function getCodePrefix(): ?string
    {
        return $this->code_prefix;
    }

    /**
     * Set code prefix.
     *
     * @param string|null $codePrefix
     * @return void
     */
    public function setCodePrefix(?string $codePrefix): void
    {
        $this->code_prefix = $codePrefix;
    }

    /**
     * Get code suffix.
     *
     * @return string|null
     */
    public function getCodeSuffix(): ?string
    {
        return $this->code_suffix;
    }

    /**
     * Set code suffix.
     *
     * @param string|null $codeSuffix
     * @return void
     */
    public function setCodeSuffix(?string $codeSuffix): void
    {
        $this->code_suffix = $codeSuffix;
    }

    /**
     * Get code length.
     *
     * @return int|null
     */
    public function getCodeLength(): ?int
    {
        return $this->code_length;
    }

    /**
     * Set code length.
     *
     * @param int|null $codeLength
     * @return void
     */
    public function setCodeLength(?int $codeLength): void
    {
        $this->code_length = $codeLength;
    }

    /**
     * Get code dash interval.
     *
     * @return int|null
     */
    public function getCodeDash(): ?int
    {
        return $this->code_dash;
    }

    /**
     * Set code dash interval.
     *
     * @param int|null $codeDash
     * @return void
     */
    public function setCodeDash(?int $codeDash): void
    {
        $this->code_dash = $codeDash;
    }

    /**
     * Get data field.
     *
     * @return string
     */
    public function getDataField(): string
    {
        return $this->data_field;
    }

    /**
     * Set data field.
     *
     * @param string $dataField
     * @return void
     */
    public function setDataField(string $dataField): void
    {
        $this->data_field = $dataField;
    }

    /**
     * Get batch size.
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batch_size;
    }

    /**
     * Set batch size.
     *
     * @param int $batch_size
     * @return void
     */
    public function setBatchSize(int $batch_size): void
    {
        $this->batch_size = $batch_size;
    }

    /**
     * Get expires at.
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->expires_at;
    }

    /**
     * Set expires at.
     *
     * @param string|null $expiresAt
     * @return void
     */
    public function setExpiresAt(?string $expiresAt): void
    {
        $this->expires_at = $expiresAt;
    }
}
