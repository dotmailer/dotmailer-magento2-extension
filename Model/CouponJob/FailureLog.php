<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File as IoFile;

/**
 * Reads and writes per-job failure detail as line-oriented JSON (NDJSON).
 *
 * Failure detail for a CouponJob is stored outside the email_coupon_job.job_details
 * blob, under var/dotdigital-bulk-coupons/:
 *   - {couponJobId}.imports.{importerId}.log
 *                                API per-contact import errors, one file per batch
 *   - {couponJobId}.messages.log
 *                                queue-consumer errors (append-only)
 *
 * Import detail is partitioned by importer (batch) ID and each batch file is
 * rewritten wholesale on every write, so a re-processed batch replaces rather than
 * appends its detail — mirroring the override-on-retry semantics ReportBuilder uses
 * for the counts. Message detail is a single append-only file.
 *
 * One JSON object per line lets the reader stream and page each file without loading
 * the whole set into memory, so arbitrarily large failure sets are safe to read. The
 * authoritative counts live in job_details, so counts remain correct even if these
 * log files are deleted.
 */
class FailureLog implements FailureLogInterface
{
    /**
     * Category for API per-contact import failures (imports/{importerId}.log).
     */
    public const CATEGORY_IMPORTS = 'imports';

    /**
     * Category for queue-consumer message failures (messages.log).
     */
    public const CATEGORY_MESSAGES = 'messages';

    private const LOG_PATH = 'dotdigital-bulk-coupons';

    /**
     * Categories stored as a directory of per-batch files rather than a single file.
     */
    private const PARTITIONED_CATEGORIES = [self::CATEGORY_IMPORTS];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var IoFile
     */
    private $ioFile;

    /**
     * @var WriteInterface|null
     */
    private $varDirectory;

    /**
     * @param Filesystem $filesystem
     * @param Logger $logger
     * @param IoFile $ioFile
     */
    public function __construct(
        Filesystem $filesystem,
        Logger $logger,
        IoFile $ioFile
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->ioFile = $ioFile;
    }

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
    public function append(int $couponJobId, string $category, array $lines): void
    {
        if (empty($lines)) {
            return;
        }

        try {
            $directory = $this->getVarDirectory();
            $relativePath = $this->getFlatFilePath($couponJobId, $category);
            $this->writeLines($directory, $relativePath, $lines, 'a');
        } catch (\Throwable $e) {
            $this->logException('append', $category, $couponJobId, $e);
        }
    }

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
    public function replaceBatch(int $couponJobId, string $category, int $batchKey, array $lines): void
    {
        try {
            $directory = $this->getVarDirectory();
            $relativePath = $this->getBatchFilePath($couponJobId, $category, $batchKey);

            if (empty($lines)) {
                if ($directory->isExist($relativePath)) {
                    $directory->delete($relativePath);
                }
                return;
            }

            // 'w' truncates, so a retried batch overwrites its previous detail.
            $this->writeLines($directory, $relativePath, $lines, 'w');
        } catch (\Throwable $e) {
            $this->logException('replaceBatch', $category, $couponJobId, $e);
        }
    }

    /**
     * Count the number of failure lines recorded for a category.
     *
     * Streams each backing file line by line so memory stays flat for very large logs.
     *
     * @param int $couponJobId
     * @param string $category
     * @return int
     */
    public function count(int $couponJobId, string $category): int
    {
        $count = 0;
        foreach ($this->getReadableFiles($couponJobId, $category) as $relativePath) {
            $file = $this->openForRead($relativePath, $category, $couponJobId);
            if ($file === null) {
                continue;
            }
            foreach ($file as $line) {
                if (trim((string) $line) !== '') {
                    $count++;
                }
            }
        }
        return $count;
    }

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
    public function read(int $couponJobId, string $category, int $offset, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $result = [];
        $current = 0;

        foreach ($this->getReadableFiles($couponJobId, $category) as $relativePath) {
            $file = $this->openForRead($relativePath, $category, $couponJobId);
            if ($file === null) {
                continue;
            }
            foreach ($file as $rawLine) {
                $line = trim((string) $rawLine);
                if ($line === '') {
                    continue;
                }
                if ($current++ < $offset) {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (is_array($decoded)) {
                    $result[] = $decoded;
                }
                if (count($result) >= $limit) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Write NDJSON lines to a file with an exclusive lock.
     *
     * @param WriteInterface $directory
     * @param string $relativePath
     * @param array $lines
     * @param string $mode 'a' to append, 'w' to truncate-and-write
     * @return void
     */
    private function writeLines(WriteInterface $directory, string $relativePath, array $lines, string $mode): void
    {
        $payload = '';
        foreach ($lines as $line) {
            $payload .= json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }

        $stream = $directory->openFile($relativePath, $mode);
        try {
            $stream->lock();
            $stream->write($payload);
        } finally {
            $stream->unlock();
            $stream->close();
        }
    }

    /**
     * Resolve the ordered list of var-relative files backing a category.
     *
     * Partitioned categories return every per-batch file in the log directory,
     * sorted by batch key; flat categories return the single file if it exists.
     *
     * @param int $couponJobId
     * @param string $category
     * @return string[]
     */
    private function getReadableFiles(int $couponJobId, string $category): array
    {
        $category = $this->normaliseCategory($category);

        try {
            $directory = $this->getVarDirectory();

            if (!$this->isPartitioned($category)) {
                $flat = $this->getFlatFilePath($couponJobId, $category);
                return $directory->isExist($flat) ? [$flat] : [];
            }

            $dirPath = self::LOG_PATH;
            if (!$directory->isExist($dirPath)) {
                return [];
            }

            $files = array_filter(
                $directory->read($dirPath),
                function ($path) use ($couponJobId, $category) {
                    return preg_match(
                        sprintf(
                            '~(?:^|/)%d\.%s\.\d+\.log$~',
                            $couponJobId,
                            preg_quote($category, '~')
                        ),
                        (string) $path
                    ) === 1;
                }
            );
            usort($files, function ($a, $b) {
                return $this->batchKeyFromPath($a) <=> $this->batchKeyFromPath($b);
            });

            return array_values($files);
        } catch (\Throwable $e) {
            $this->logException('list', $category, $couponJobId, $e);
            return [];
        }
    }

    /**
     * Open a var-relative log file as an iterable SplFileObject, or null if absent.
     *
     * @param string $relativePath
     * @param string $category
     * @param int $couponJobId
     * @return \SplFileObject|null
     */
    private function openForRead(string $relativePath, string $category, int $couponJobId): ?\SplFileObject
    {
        try {
            $directory = $this->getVarDirectory();
            if (!$directory->isExist($relativePath)) {
                return null;
            }

            $file = new \SplFileObject($directory->getAbsolutePath($relativePath), 'r');
            $file->setFlags(\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY);
            return $file;
        } catch (\Throwable $e) {
            $this->logException('read', $category, $couponJobId, $e);
            return null;
        }
    }

    /**
     * Extract the numeric batch key from a per-batch file path for ordering.
     *
     * @param string $path
     * @return int
     */
    private function batchKeyFromPath(string $path): int
    {
        $info = $this->ioFile->getPathInfo($path);
        return (int) (preg_match('/\.(\d+)$/', (string) ($info['filename'] ?? ''), $matches)
            ? $matches[1]
            : 0);
    }

    /**
     * Build the var-relative path to a single per-batch file of a partitioned category.
     *
     * @param int $couponJobId
     * @param string $category
     * @param int $batchKey
     * @return string
     */
    private function getBatchFilePath(int $couponJobId, string $category, int $batchKey): string
    {
        return sprintf(
            '%s/%d.%s.%d.log',
            self::LOG_PATH,
            $couponJobId,
            $this->normaliseCategory($category),
            $batchKey
        );
    }

    /**
     * Build the var-relative path to a flat (non-partitioned) category file.
     *
     * @param int $couponJobId
     * @param string $category
     * @return string
     */
    private function getFlatFilePath(int $couponJobId, string $category): string
    {
        return sprintf(
            '%s/%d.%s.log',
            self::LOG_PATH,
            $couponJobId,
            $this->normaliseCategory($category)
        );
    }

    /**
     * Whether a category is stored as a directory of per-batch files.
     *
     * @param string $category
     * @return bool
     */
    private function isPartitioned(string $category): bool
    {
        return in_array($category, self::PARTITIONED_CATEGORIES, true);
    }

    /**
     * Validate and normalise a category to one of the known values.
     *
     * @param string $category
     * @return string
     */
    private function normaliseCategory(string $category): string
    {
        if (!in_array($category, [self::CATEGORY_IMPORTS, self::CATEGORY_MESSAGES], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown failure log category "%s".', $category));
        }
        return $category;
    }

    /**
     * Log a swallowed failure-log exception.
     *
     * @param string $operation
     * @param string $category
     * @param int $couponJobId
     * @param \Throwable $e
     * @return void
     */
    private function logException(string $operation, string $category, int $couponJobId, \Throwable $e): void
    {
        $this->logger->error(
            sprintf(
                'CouponJob FailureLog: failed to %s %s for job %d: %s',
                $operation,
                $category,
                $couponJobId,
                $e->getMessage()
            )
        );
    }

    /**
     * Lazily resolve the Magento var directory writer.
     *
     * @return WriteInterface
     */
    private function getVarDirectory(): WriteInterface
    {
        if ($this->varDirectory === null) {
            $this->varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        }
        return $this->varDirectory;
    }
}
