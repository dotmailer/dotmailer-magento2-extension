<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Traits;

trait FilterAndRankDotdigitalRecord
{
    /**
     * Minimum score for a record to be considered for ranking.
     *
     * @return int
     */
    public function getMinRank()
    {
        return 30;
    }

    /**
     * Filter string-type data fields by fuzzy relevance and sort best-first.
     *
     * Uses similar_text() percentage as the score — naturally handles exact,
     * prefix, substring and typo matches in one pass. Fields scoring below
     * MIN_SCORE are excluded.
     *
     * @param mixed $records Array of arrays or objects to filter and rank.
     * @param string $query Already lowercased.
     * @return array
     * @throws \Exception
     */
    public function filterAndRankRecords($records, string $query): array
    {
        $scored = array_filter(
            array_map(function ($record) use ($query) {
                $rankable = $this->getRankableValue($record);
                if ($query === '') {
                    return ['score' => 100, 'record' => $record];
                }
                if (strpos($rankable, $query) !== false) {
                    return ['score' => 100, 'record' => $record];
                }
                similar_text($query, $rankable, $percent);
                return $percent >= $this->getMinRank()
                    ? ['score' => $percent, 'record' => $record]
                    : null;
            }, $this->getRankableRecords($records))
        );

        usort($scored, fn ($left, $right) => $right['score'] <=> $left['score']);

        return $scored;
    }

    /**
     * Convert records to array
     *
     * @param array $records
     * @return array
     */
    protected function getRankableRecords(array $records): array
    {
        return $records;
    }

    /**
     * Normalize record
     *
     * @param array $record
     * @return string
     */
    protected function getRankableValue(array $record): string
    {
        return strtolower($record['name']);
    }
}
