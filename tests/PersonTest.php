<?php

use App\Entity\Person;
use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

class PersonTest extends TestCase
{
    public function testPrices()
    {
        $persons = [];
        $fullDay = [];
        $halfDay = [];

        $persons[] = new Person();
        $persons[0]->setDiscount(true);
        $persons[0]->setBirthDate(new \DateTime('10/10/1991'));
        $fullDay[] = $persons[0]->priceFullDay();
        $halfDay[] = $persons[0]->priceHalfDay();
        $this->assertEquals(10, $fullDay[0]);
        $this->assertEquals(5, $halfDay[0]);

        $persons[] = new Person();
        $persons[1]->setDiscount(false);
        $persons[1]->setBirthDate(new \DateTime('-30 years'));
        $fullDay[] = $persons[1]->priceFullDay();
        $halfDay[] = $persons[1]->priceHalfDay();
        $this->assertEquals(16, $fullDay[1]);
        $this->assertEquals(8, $halfDay[1]);

        $persons[] = new Person();
        $persons[2]->setDiscount(true);
        $persons[2]->setBirthDate(new \DateTime('-2 years'));
        $fullDay[] = $persons[2]->priceFullDay();
        $halfDay[] = $persons[2]->priceHalfDay();
        $this->assertEquals(0, $fullDay[2]);
        $this->assertEquals(0, $halfDay[2]);

        $persons[] = new Person();
        $persons[3]->setDiscount(false);
        $persons[3]->setBirthDate(new \DateTime('-6 years'));
        $fullDay[] = $persons[3]->priceFullDay();
        $halfDay[] = $persons[3]->priceHalfDay();
        $this->assertEquals(8, $fullDay[3]);
        $this->assertEquals(4, $halfDay[3]);

        $persons[] = new Person();
        $persons[4]->setDiscount(false);
        $persons[4]->setBirthDate(new \DateTime('-68 years'));
        $fullDay[] = $persons[4]->priceFullDay();
        $halfDay[] = $persons[4]->priceHalfDay();
        $this->assertEquals(12, $fullDay[4]);
        $this->assertEquals(6, $halfDay[4]);

        $persons[] = new Person();
        $persons[5]->setDiscount(true);
        $persons[5]->setBirthDate(new \DateTime('-68 years'));
        $fullDay[] = $persons[5]->priceFullDay();
        $halfDay[] = $persons[5]->priceHalfDay();
        $this->assertEquals(10, $fullDay[5]);
        $this->assertEquals(5, $halfDay[5]);
    }
}