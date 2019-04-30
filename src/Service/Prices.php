<?php

namespace App\Service;

use App\Entity\Person;
use App\Entity\Reservation;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class Prices
{
    public function priceFullDay($person)
    {
        $age = $person->age();
        $parameters = Yaml::parseFile(getenv('PUBLIC_HOST') . '/config/parameters.yaml');
        if ($age < $parameters['ageChild'])
            return $parameters['priceBaby'];
        elseif ($age < $parameters['ageTeenager'])
            return $parameters['priceChild'];
        elseif ($person->getDiscount())
            return $parameters['priceDiscount'];
        elseif ($age >= $parameters['ageOld'])
            return $parameters['priceOld'];
        else
            return $parameters['priceDefault'];
    }

    public function priceHalfDay($person)
    {
        return $this->priceFullDay($person) / 2;
    }

    public function price($reservation)
    {
        $price = 0;
        foreach($reservation->getPersons() as $person)
        {
            if ($reservation->getHalfDay())
                $price += $this->priceHalfDay($person);
            else
                $price += $this->priceFullDay($person);
        }

        return $price;
    }
}