<?php

use PHPUnit\Framework\TestCase;

require 'public/forExampleTests/uselessFunctionSum.php';

class FunctionTest extends TestCase
{
    public function testAddFunctionDoesReturnCorrectResults()
    {
        $this->assertEquals(4, add(2, 2));
        $this->assertEquals(8, add(5, 3));
    }

    public function testAddFuctionDoesNotReturnIncorrectResults()
    {
        $this->assertNotEquals(8, add(2, 2));
        $this->assertNotEquals(0, add(5, 5));
    }
}