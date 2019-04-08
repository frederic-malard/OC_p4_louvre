<?php

namespace App\Validator\Constraints;

use App\Service\Holidays;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManager;
use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraint;
use App\Validator\Constraints\ForbiddenDays;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ForbiddenDaysValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint/*, ReservationRepository $repository*/)
    {
        if (!$constraint instanceof ForbiddenDays) {
            throw new UnexpectedTypeException($constraint, ForbiddenDays::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        /*if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');

            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }*/

        $year = intval(strftime('%Y', $value->getTimestamp()));
        $holidays = (new Holidays())->getHolidays($year);

        if (in_array(date('D', $value->getTimestamp()), ['Tue', 'Sun']) || in_array($value->getTimestamp(), $holidays)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}