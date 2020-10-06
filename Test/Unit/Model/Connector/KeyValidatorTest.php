<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector;

use Dotdigitalgroup\Email\Model\Connector\KeyValidator;
use PHPUnit\Framework\TestCase;

class KeyValidatorTest extends TestCase
{
    private $keyValidatorTest;
    private $spaceReplacer;
    private $characterReplacer;
    private $suffix;

    protected function setUp() :void
    {
        $this->spaceReplacer = '-';
        $this->characterReplacer = '';
        $this->suffix = '1';

        $this->keyValidatorTest = new KeyValidator();
    }

    public function testKeyWithPermittedPattern()
    {
        $goodKeyName = 'Please add me to the foosball newsletter';
        $goodKeyNameWithNoSpaces = 'Please-add-me-to-the-foosball-newsletter';

        $this->assertEquals(
            $this->keyValidatorTest->cleanLabel(
                $goodKeyName,
                $this->spaceReplacer,
                $this->characterReplacer,
                $this->suffix
            ),
            $goodKeyNameWithNoSpaces
        );
    }

    public function testKeyWithNotPermittedPattern()
    {
        $badKeyName = 'Please add me to the f00sb@ll newsletter!!';
        $badKeyNameWithNoSpaces = 'Please-add-me-to-the-f00sb@ll-newsletter!!';
        $sanitisedBadKeyName = 'Please-add-me-to-the-f00sbll-newsletter1';

        $this->assertNotEquals(
            $this->keyValidatorTest->cleanlabel(
                $badKeyName,
                $this->spaceReplacer,
                $this->characterReplacer,
                $this->suffix
            ),
            $badKeyNameWithNoSpaces
        );

        $this->assertEquals(
            $this->keyValidatorTest->cleanlabel(
                $badKeyName,
                $this->spaceReplacer,
                $this->characterReplacer,
                $this->suffix
            ),
            $sanitisedBadKeyName
        );
    }
}
