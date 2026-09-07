<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Traits;

use Dotdigitalgroup\Email\Traits\FilterAndRankDotdigitalRecord;
use PHPUnit\Framework\TestCase;

class FilterAndRankDotdigitalRecordTest extends TestCase
{
    /**
     * @var object
     */
    private object $subject;

    protected function setUp(): void
    {
        $this->subject = new class {
            use FilterAndRankDotdigitalRecord;
        };
    }

    public function testGetMinRankReturnsThirty(): void
    {
        $this->assertSame(30, $this->subject->getMinRank());
    }

    public function testFilterAndRankRecordsWithEmptyQueryReturnsAllRecordsWithScoreHundred(): void
    {
        $records = [
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
            ['name' => 'Gamma'],
        ];

        $result = $this->subject->filterAndRankRecords($records, '');

        $this->assertCount(3, $result);
        foreach ($result as $item) {
            $this->assertSame(100, $item['score']);
        }
    }

    public function testExactMatchReceivesScoreHundred(): void
    {
        $records = [
            ['name' => 'Newsletter'],
            ['name' => 'Order'],
        ];

        $result = $this->subject->filterAndRankRecords($records, 'newsletter');

        $this->assertSame(100, $result[0]['score']);
        $this->assertSame(['name' => 'Newsletter'], $result[0]['record']);
    }

    public function testSubstringMatchReceivesScoreHundred(): void
    {
        $records = [
            ['name' => 'Order Confirmation'],
            ['name' => 'Wishlist'],
        ];

        $result = $this->subject->filterAndRankRecords($records, 'order');

        $this->assertSame(100, $result[0]['score']);
        $this->assertSame(['name' => 'Order Confirmation'], $result[0]['record']);
    }

    public function testBestMatchIsSortedFirst(): void
    {
        $records = [
            ['name' => 'Abc'],
            ['name' => 'Newsletter'],
        ];

        $result = $this->subject->filterAndRankRecords($records, 'newsletter');

        $this->assertSame(100, $result[0]['score']);
        $this->assertSame(['name' => 'Newsletter'], $result[0]['record']);
    }

    public function testHigherSimilarityScoresRankFirst(): void
    {
        $records = [
            ['name' => 'Apricot'],
            ['name' => 'Apple Pie'],
        ];

        $result = $this->subject->filterAndRankRecords($records, 'apple');

        $this->assertSame(['name' => 'Apple Pie'], $result[0]['record']);
    }

    public function testRecordsBelowMinRankAreExcluded(): void
    {
        $records = [
            ['name' => 'Xyz'],
            ['name' => 'Zzz'],
            ['name' => 'Newsletter'],
        ];

        $result = $this->subject->filterAndRankRecords($records, 'newsletter');

        $this->assertCount(1, $result);
        $this->assertSame(['name' => 'Newsletter'], $result[0]['record']);
    }

    public function testEmptyRecordsArrayReturnsEmptyResult(): void
    {
        $result = $this->subject->filterAndRankRecords([], 'query');

        $this->assertSame([], $result);
    }

    public function testGetRankableRecordsReturnsSameArray(): void
    {
        $records = [['name' => 'Foo'], ['name' => 'Bar']];

        $reflection = new \ReflectionMethod($this->subject, 'getRankableRecords');

        $result = $reflection->invoke($this->subject, $records);

        $this->assertSame($records, $result);
    }

    public function testGetRankableValueReturnsLowercasedName(): void
    {
        $record = ['name' => 'Hello World'];

        $reflection = new \ReflectionMethod($this->subject, 'getRankableValue');

        $result = $reflection->invoke($this->subject, $record);

        $this->assertSame('hello world', $result);
    }

    public function testMatchingIsCaseInsensitive(): void
    {
        $records = [
            ['name' => 'NEWSLETTER'],
        ];

        $result = $this->subject->filterAndRankRecords($records, 'newsletter');

        $this->assertCount(1, $result);
        $this->assertSame(100, $result[0]['score']);
    }

    public function testCustomRankableValueIsUsedWhenOverridden(): void
    {
        $customSubject = new class {
            use FilterAndRankDotdigitalRecord;

            protected function getRankableValue(array $record): string
            {
                return strtolower($record['label']);
            }
        };

        $records = [
            ['label' => 'Welcome Email'],
            ['label' => 'Order Shipped'],
        ];

        $result = $customSubject->filterAndRankRecords($records, 'welcome');

        $this->assertCount(1, $result);
        $this->assertSame(['label' => 'Welcome Email'], $result[0]['record']);
    }
}
