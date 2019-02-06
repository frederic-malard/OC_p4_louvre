<?php

use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    /**
     * verify that a correct mail will be accepted
     */
    public function testCorrectMailAccepted()
    {
        $mail = "fred.malard@wanadoo.fr";
        $reservation = new Reservation();
        $reservation->setMail($mail);
        $this->assertEquals($mail, $reservation->getMail());
    }

    /**
     * verify that an incorrect mail will be refused
     *
     * @return void
     */
    public function testIncorrectMailRefused()
    {
        $mail = "fred.fr";
        $reservation = new Reservation();
        $reservation->setMail($mail);
        $this->assertNotEquals($mail, $reservation->getMail());
    }

    /* delete ? Future dates accepted only if the day is accepted also by the following functions... Complicated and useless
    public function testVisitDayInFutureAccepted()
    {
        $day = new \DateTime(date('d/m/Y', strtotime("+2 month")));
        $reservation = new Reservation();
        $reservation->setVisitDay($day);
        $this->assertGreaterThan(new \DateTime(), $reservation->getVisitDay());
    }*/
    
    public function testVisitDayInPastRefused()
    {
        $day = new \DateTime(date('d/m/Y', strtotime("-2 month")));
        $reservation = new Reservation();
        $reservation->setVisitDay($day);
        $this->assertNull($reservation->getVisitDay());
    }

    public function testTuesdayAndSundayRefused()
    {
        $addedDays = 3;
        $day = new \DateTime(date('d/m/Y', strtotime('+' . $addedDays . ' days')));
        while ($day->format('D') != 'Tue')
        {
            $addedDays++;
            $day = new \DateTime(date('d/m/Y', strtotime('+' . $addedDays . ' days')));
        }

        $reservation = new Reservation();
        $reservation->setVisitDay($day);
        $this->assertNull($reservation->getVisitDay());

        while ($day->format('D') != 'Sun')
        {
            $addedDays++;
            $day = new \DateTime(date('d/m/Y', strtotime('+' . $addedDays . ' days')));
        }

        $reservation->setVisitDay($day);
        $this->assertNull($reservation->getVisitDay());
    }

    public function testPublicHolidaysRefused()
    {
        $reservation = new Reservation();

        $date = new \DateTime();
        $year = $date->format('Y');
        $year++;

        $easterDate = easter_date($year); // pâques
        $easterDay = $easterDate->format('d');
        $easterMonth = $easterDate->format('m');

        $publicHolidays = [
            new \DateTime('01/01/' . $year),
            new \DateTime('01/05/' . $year),
            new \DateTime('08/05/' . $year),
            new \DateTime('14/07/' . $year),
            new \DateTime('15/08/' . $year),
            new \DateTime('01/11/' . $year),
            new \DateTime('11/11/' . $year),
            new \DateTime('25/12/' . $year),
            $easterDay,
            new \DateTime(($easterDay + 38) . '/' . $easterMonth . '/' . $year),
            new \DateTime(($easterDay + 49) . '/' . $easterMonth . '/' . $year)
        ];

        foreach($publicHolidays as $publicHoliday)
        {
            $reservation->setVisitDay($publicHoliday);
            $this->assertNull($reservation->getVisitDay());
        }
    }

    /*public function testMoreThanThousandRefused()
    {

    }*/
}