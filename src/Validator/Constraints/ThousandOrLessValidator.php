<?php

namespace App\Validator\Constraints;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManager;
use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraint;
use App\Validator\Constraints\ThousandOrLess;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ThousandOrLessValidator extends ConstraintValidator
{
    private $em;

    const LOUVRE_CAPACITY = 1000;

    private function getReservationRepository()
    {
        return $this->getDoctrine()->getRepository(Reservation::class);
    }  

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ThousandOrLess) {
            throw new UnexpectedTypeException($constraint, ThousandOrLess::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if ($this->em->getRepository(Reservation::class)->countVisitorsOnDate($value->getVisitDay()) > Self::LOUVRE_CAPACITY - count($value->getPersons()))
        {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}