<?php

use PHPUnit\Framework\TestCase;

/**
 * Just a example test, to train and learn how to use phpunit
 */

class ExampleTest extends TestCase
{
    /**
     * Verifying that 2+2 is 4. This got no interest, but just train me. The interest is to verify an assertion that I know to be true, really is considered as true.
     */
    public function testAddingTwoPlusTwoResultsInFour()
    {
        $this->assertEquals(4, 2+2);
    }
}