<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Contracts;

/**
 * Interface for reading and writing per-job failure detail as line-oriented JSON (NDJSON).
 */
interface FailureLogInterface
{
    /**
     * Category for API per-contact import failures (imports/{importerId}.log).
     */
    public const CATEGORY_IMPORTS = 'imports';

    /**
     * Category for queue-consumer message failures (messages.log).
     */
    public const CATEGORY_MESSAGES = 'messages';

    /**
     * Append failure detail lines to a flat (non-partitioned) category log.
     *
     * Used for queue-consumer messages, which are distinct events rather than
     * retryable batches. Best-effort: any failure to write is logged and swallowed
     * so that reporting never breaks the import flow.
     *
     * @param int $couponJobId
     * @param string $category self::CATEGORY_* constant
     * @param array $lines List of associative failure-detail arrays
     * @return void
     */
    public function append(int $couponJobId, string $category, array $lines): void;

    /**
     * Replace the failure detail recorded for a single batch of a partitioned category.
     *
     * The batch's file is rewritten wholesale (truncate + write) so a re-processed
     * batch replaces rather than appends its detail. An empty $lines clears any
     * previously recorded detail for the batch.
     *
     * @param int $couponJobId
     * @param string $category self::CATEGORY_* constant (must be partitioned)
     * @param int $batchKey The email_importer.id of the batch
     * @param array $lines List of associative failure-detail arrays
     * @return void
     */
    public function replaceBatch(int $couponJobId, string $category, int $batchKey, array $lines): void;

    /**
     * Count the number of failure lines recorded for a category.
     *
     * Streams each backing file line by line so memory stays flat for very large logs.
     *
     * @param int $couponJobId
     * @param string $category
     * @return int
     */
    public function count(int $couponJobId, string $category): int;

    /**
     * Read a page of failure lines for a category.
     *
     * Streams across the category's backing files (in stable order) skipping to the
     * requested offset without holding a whole file in memory. Malformed lines are
     * skipped.
     *
     * @param int $couponJobId
     * @param string $category
     * @param int $offset Zero-based line offset to start reading from
     * @param int $limit Maximum number of decoded lines to return
     * @return array<int, array<string, mixed>>
     */
    public function read(int $couponJobId, string $category, int $offset, int $limit): array;
}
