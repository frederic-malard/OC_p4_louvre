<?php

namespace App\Validator\Constraints;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraint;
use App\Validator\Constraints\ThousandOrLess;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ThousandOrLessValidator extends ConstraintValidator
{
    private function getReservationRepository()
    {
        return $this->getDoctrine()->getRepository(Reservation::class);
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

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');

            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }

        if ($this->getReservationRepository()->countVisitorsOnDate($value) >= 1000) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}