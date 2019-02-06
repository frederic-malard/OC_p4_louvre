<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Person;
use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('FR-fr');

        for ($i=0 ; $i<mt_rand(4, 7) ; $i++)
        {
            $reservation = new Reservation();

            $halfDay = [true, false];
            $random = 'azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN1234567890';

            $visitDay = $faker->dateTimeInInterval("now", "+8 months");
            $halfDay =  $halfDay[mt_rand(0, 1)];
            $random = substr(str_shuffle($random), 0, 20);
            
            $reservation->setMail($faker->email())
                        ->setVisitDay($visitDay)
                        ->setHalfDay($halfDay)
                        ->setRandom($random);

            for ($j=0 ; $j<mt_rand(1, 10) ; $j++)
            {
                $person = new Person();

                $birthDate = $faker->dateTimeBetween("-90 years", "now");
                $discount = [true, false];

                $person->setName($faker->lastName())
                       ->setFirstName($faker->firstName())
                       ->setCountry($faker->country())
                       ->setBirthDate($birthDate)
                       ->setDiscount($discount[mt_rand(0, 1)]);

                $person->addReservation($reservation);
                $reservation->addPerson($person);

                $manager->persist($person);
            }

            $manager->persist($reservation);
        }

        $manager->flush();
    }
}
