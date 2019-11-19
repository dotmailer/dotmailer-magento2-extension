<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector;

use Dotdigitalgroup\Email\Model\Connector\KeyValidator;
use PHPUnit\Framework\TestCase;

class KeyValidatorTest extends TestCase
{
    protected function setUp()
    {
        $this->separator = '-';
        $this->suffix = '1';

        $this->keyValidatorTest = new KeyValidator();
    }

    public function testKeyWithPermittedPattern()
    {
        $goodKeyName = 'Please add me to the foosball newsletter';
        $goodKeyNameWithNoSpaces = 'Please-add-me-to-the-foosball-newsletter';

        $this->assertEquals(
            $this->keyValidatorTest->cleanLabel($goodKeyName, $this->separator, $this->suffix),
            $goodKeyNameWithNoSpaces
        );
    }

    public function testKeyWithNotPermittedPattern()
    {
        $badKeyName = 'Please add me to the f00sb@ll newsletter!!';
        $badKeyNameWithNoSpaces = 'Please-add-me-to-the-f00sb@ll-newsletter!!';
        $sanitisedBadKeyName = 'Please-add-me-to-the-f00sbll-newsletter-1';

        $this->assertNotEquals(
            $this->keyValidatorTest->cleanlabel($badKeyName, $this->separator, $this->suffix),
            $badKeyNameWithNoSpaces
        );

        $this->assertEquals(
            $this->keyValidatorTest->cleanlabel($badKeyName, $this->separator, $this->suffix),
            $sanitisedBadKeyName
        );
    }
}

